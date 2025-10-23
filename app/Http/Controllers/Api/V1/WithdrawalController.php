<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Repository\PaystackRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PaystackRecipient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    protected $paystack;

    public function __construct(PaystackRepository $paystack)
    {
        $this->paystack = $paystack;
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:100',
            'account_number' => 'required',
            'bank_code'      => 'required',
            'account_name'   => 'required|string',
        ]);

        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet || $wallet->balance < $request->amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        return DB::transaction(function () use ($user, $wallet, $request) {
            // Step 1: Resolve account to ensure bank_code/account_number are valid
            $resolved = $this->paystack->resolveAccount($request->account_number, $request->bank_code);
            if (!($resolved['status'] ?? false)) {
                \Illuminate\Support\Facades\Log::warning('Account resolution failed', ['user_id' => $user->id, 'resolved' => $resolved]);
                return response()->json(['error' => 'Account resolution failed', 'details' => $resolved['message'] ?? $resolved], 400);
            }

            // Step 2: Create or reuse Paystack recipient (use resolved account_name if available)
            $recipientName = $resolved['data']['account_name'] ?? $request->account_name;

            // Try to find a stored recipient for this user + account + bank
            $storedRecipient = PaystackRecipient::where('user_id', $user->id)
                ->where('account_number', $request->account_number)
                ->where('bank_code', $request->bank_code)
                ->first();

            if ($storedRecipient && $storedRecipient->recipient_code) {
                $recipientCode = $storedRecipient->recipient_code;
            } else {
                $recipient = $this->paystack->createRecipient(
                    $recipientName,
                    $request->account_number,
                    $request->bank_code
                );

                if (!($recipient['status'] ?? false)) {
                    Log::warning('Failed to create paystack recipient', ['user_id' => $user->id, 'recipient' => $recipient]);

                    // If Paystack indicates recipient already exists, try to recover the recipient_code
                    $message = $recipient['message'] ?? null;
                    $maybeRecipientCode = null;

                    // Some Paystack error responses include the existing recipient in data or message
                    if (!empty($recipient['data']['recipient_code'] ?? null)) {
                        $maybeRecipientCode = $recipient['data']['recipient_code'];
                    }

                    // Attempt to find by account_number and bank_code in our DB as a fallback
                    if (!$maybeRecipientCode) {
                        $existing = PaystackRecipient::where('account_number', $request->account_number)
                            ->where('bank_code', $request->bank_code)
                            ->first();
                        if ($existing && $existing->recipient_code) {
                            $maybeRecipientCode = $existing->recipient_code;
                        }
                    }

                    if ($maybeRecipientCode) {
                        $recipientCode = $maybeRecipientCode;
                    } else {
                        return response()->json(['error' => 'Failed to create recipient', 'details' => $message ?? $recipient], 400);
                    }
                } else {
                    $recipientCode = $recipient['data']['recipient_code'];

                    // store recipient for future reuse
                    PaystackRecipient::create([
                        'user_id' => $user->id,
                        'recipient_code' => $recipientCode,
                        'name' => $recipientName,
                        'account_number' => $request->account_number,
                        'bank_code' => $request->bank_code,
                        'meta' => $recipient['data'] ?? null,
                    ]);
                }
            }

            // Step 2: Create withdrawal record
            // Generate a Paystack-compliant transfer reference (we recommend UUID v4)
            $reference = 'wd_' . (string) Str::uuid();

            $withdrawal = Withdrawal::create([
                'user_id'  => $user->id,
                'amount'   => $request->amount,
                'currency' => 'NGN',
                'method'   => 'paystack',
                'trx'      => $reference,
                'status'   => 'pending',
                'paystack_reference' => $reference,
            ]);

            // Step 3: Deduct from wallet
            $wallet->decrement('balance', $request->amount);

            // Also update user balance if you store it
            $user->decrement('balance', $request->amount);

            // Step 4: Log transaction
            Transaction::create([
                'user_id'    => $user->id,
                'amount'     => $request->amount,
                'type'       => 'debit',
                'status'     => 'pending',
                'description'=> 'Withdrawal initiated',
                
            ]);

            // Step 5: Initiate Paystack transfer
            // Initiate transfer and pass our generated reference so Paystack will use it
            $transfer = $this->paystack->initiateTransfer($recipientCode, $request->amount, 'Wallet Withdrawal', $reference);

            if ($transfer['status']) {
                $withdrawal->update([
                    'status'            => 'success',
                    'trx'               => $transfer['data']['reference'] ?? $withdrawal->trx,
                    'paystack_reference' => $transfer['data']['reference'] ?? $reference,
                ]);

                Transaction::create([
                    'user_id'    => $user->id,
                    'amount'     => $request->amount,
                    'type'       => 'debit',
                    'status'     => 'success',
                    'description'=> 'Withdrawal successful to bank',
                ]);
            } else {
                // Refund if failed
                $wallet->increment('balance', $request->amount);
                // ensure correct user balance column is updated; fallback to 'balance'
                $userBalanceColumn = property_exists($user, 'wallet_balance') ? 'wallet_balance' : 'balance';
                $user->increment($userBalanceColumn, $request->amount);

                // store paystack reference (or our reference) for later reconciliation
                $withdrawal->update([
                    'status' => 'failed',
                    'paystack_reference' => $transfer['data']['reference'] ?? $reference,
                ]);

                Transaction::create([
                    'user_id'    => $user->id,
                    'amount'     => $request->amount,
                    'type'       => 'debit',
                    'status'     => 'failed',
                    'description'=> 'Withdrawal failed, amount refunded',
                ]);

                return response()->json(['error' => 'Transfer failed'], 400);
            }

            return response()->json([
                'message'    => 'Withdrawal successful',
                'withdrawal' => $withdrawal,
            ]);
        });
    }
}
