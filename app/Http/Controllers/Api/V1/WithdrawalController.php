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
use App\Notifications\WithdrawalNotification;
use App\Events\UserWalletUpdated;

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
            $resolved = $this->paystack->resolveAccount($request->account_number, $request->bank_code);
            if (!($resolved['status'] ?? false)) {
                Log::warning('Account resolution failed', ['user_id' => $user->id, 'resolved' => $resolved]);
                return response()->json(['error' => 'Account resolution failed', 'details' => $resolved['message'] ?? $resolved], 400);
            }

            $recipientName = $resolved['data']['account_name'] ?? $request->account_name;

            $storedRecipient = PaystackRecipient::where('user_id', $user->id)
                ->where('account_number', $request->account_number)
                ->where('bank_code', $request->bank_code)
                ->first();

            if ($storedRecipient && $storedRecipient->recipient_code) {
                $recipientCode = $storedRecipient->recipient_code;

                // ✅ Validate recipient before transfer
                if (!$this->paystack->validateRecipient($recipientCode)) {
                    Log::warning('Recipient invalid — recreating', [
                        'user_id' => $user->id,
                        'recipientCode' => $recipientCode,
                    ]);

                    $recipient = $this->paystack->createRecipient(
                        $recipientName,
                        $request->account_number,
                        $request->bank_code
                    );

                    if (!($recipient['status'] ?? false)) {
                        return response()->json([
                            'error' => 'Unable to validate or recreate recipient',
                            'details' => $recipient['message'] ?? 'Recipient recreation failed'
                        ], 400);
                    }

                    $recipientCode = $recipient['data']['recipient_code'];
                    $storedRecipient->update(['recipient_code' => $recipientCode]);
                }
            } else {
                $recipient = $this->paystack->createRecipient(
                    $recipientName,
                    $request->account_number,
                    $request->bank_code
                );

                if (!($recipient['status'] ?? false)) {
                    Log::warning('Failed to create paystack recipient', ['user_id' => $user->id, 'recipient' => $recipient]);
                    $message = $recipient['message'] ?? null;
                    return response()->json(['error' => 'Failed to create recipient', 'details' => $message ?? $recipient], 400);
                }

                $recipientCode = $recipient['data']['recipient_code'];
                PaystackRecipient::create([
                    'user_id' => $user->id,
                    'recipient_code' => $recipientCode,
                    'name' => $recipientName,
                    'account_number' => $request->account_number,
                    'bank_code' => $request->bank_code,
                    'meta' => $recipient['data'] ?? null,
                ]);
            }

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

            $wallet->decrement('balance', $request->amount);
            $user->decrement('balance', $request->amount);

            Transaction::create([
                'user_id'    => $user->id,
                'amount'     => $request->amount,
                'type'       => 'debit',
                'status'     => 'pending',
                'description'=> 'Withdrawal initiated',
            ]);

            // ✅ Proceed with validated recipient
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
                    'category'   => 'withdrawal-payout',
                ]);

                // Notify user and broadcast wallet update
                try {
                    $user->notify(new WithdrawalNotification($request->amount, 'success', $withdrawal->paystack_reference));
                } catch (\Exception $e) {
                    Log::error('Failed to notify user about successful withdrawal', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }

                try {
                    event(new UserWalletUpdated($user->id, $user->balance));
                } catch (\Exception $e) {
                    Log::error('Failed to dispatch UserWalletUpdated after withdrawal success', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            } else {
                $wallet->increment('balance', $request->amount);
                $userBalanceColumn = property_exists($user, 'wallet_balance') ? 'wallet_balance' : 'balance';
                $user->increment($userBalanceColumn, $request->amount);

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

                // Notify user about failed withdrawal
                try {
                    $user->notify(new WithdrawalNotification($request->amount, 'failed', $withdrawal->paystack_reference));
                } catch (\Exception $e) {
                    Log::error('Failed to notify user about failed withdrawal', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }

                return response()->json(['error' => 'Transfer failed'], 400);
            }

            return response()->json([
                'message'    => 'Withdrawal successful',
                'withdrawal' => $withdrawal,
            ]);
        });
    }
}
