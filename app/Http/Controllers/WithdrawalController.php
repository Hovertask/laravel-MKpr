<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Repositories\PaystackRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            // Step 1: Create Paystack recipient
            $recipient = $this->paystack->createRecipient(
                $request->account_name,
                $request->account_number,
                $request->bank_code
            );

            if (!$recipient['status']) {
                return response()->json(['error' => 'Failed to create recipient'], 400);
            }

            $recipientCode = $recipient['data']['recipient_code'];

            // Step 2: Create withdrawal record
            $withdrawal = Withdrawal::create([
                'user_id'  => $user->id,
                'amount'   => $request->amount,
                'currency' => 'NGN',
                'method'   => 'paystack',
                'trx'      => uniqid('wd_'),
                'status'   => 'pending',
            ]);

            // Step 3: Deduct from wallet
            $wallet->decrement('balance', $request->amount);

            // Also update user balance if you store it
            $user->decrement('wallet_balance', $request->amount);

            // Step 4: Log transaction
            Transaction::create([
                'user_id'    => $user->id,
                'amount'     => $request->amount,
                'type'       => 'debit',
                'status'     => 'pending',
                'description'=> 'Withdrawal initiated',
            ]);

            // Step 5: Initiate Paystack transfer
            $transfer = $this->paystack->initiateTransfer($recipientCode, $request->amount, 'Wallet Withdrawal');

            if ($transfer['status']) {
                $withdrawal->update([
                    'status'    => 'success',
                    'trx'       => $transfer['data']['reference'] ?? $withdrawal->trx,
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
                $user->increment('wallet_balance', $request->amount);

                $withdrawal->update(['status' => 'failed']);

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
