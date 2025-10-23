<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Models\Wallet;

class PaystackWebhookController extends Controller
{
    /**
     * Handle incoming Paystack webhooks for transfer events.
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();

        // Verify signature
        $signature = $request->header('X-Paystack-Signature') ?? $request->header('x-paystack-signature');
        $secret = config('services.paystack.secret_key');

        if (! $signature || ! hash_equals(hash_hmac('sha512', $payload, $secret), $signature)) {
            Log::warning('Paystack webhook signature mismatch', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $body = json_decode($payload, true);
        $event = $body['event'] ?? null;
        $data = $body['data'] ?? [];
        $reference = $data['reference'] ?? null;

        Log::info('Paystack webhook received', ['event' => $event, 'reference' => $reference]);

        // find matching withdrawal by paystack_reference or trx
        $withdrawal = null;
        if ($reference) {
            $withdrawal = Withdrawal::where('paystack_reference', $reference)
                ->orWhere('trx', $reference)
                ->first();
        }

        // Fallback: try to find pending withdrawal by amount and user if available (best effort)
        if (! $withdrawal && ! empty($data['amount']) && ! empty($data['recipient'])) {
            $amount = $data['amount'] / 100; // kobo
            $recipient = $data['recipient'];

            // best-effort match: latest pending withdrawal with same amount
            $withdrawal = Withdrawal::where('amount', $amount)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        DB::transaction(function () use ($event, $data, $withdrawal, $reference) {
            if (! $withdrawal) {
                Log::info('Paystack webhook: no matching withdrawal found', ['event' => $event, 'data' => $data]);
                return; // still return 200 to acknowledge
            }

            switch ($event) {
                case 'transfer.success':
                case 'transfer.completed':
                    if ($withdrawal->status === 'success') {
                        return;
                    }

                    $withdrawal->update([
                        'status' => 'success',
                        'paystack_reference' => $reference ?? $withdrawal->paystack_reference,
                    ]);

                    // create success transaction (idempotent via withdrawal status check above)
                    Transaction::create([
                        'user_id' => $withdrawal->user_id,
                        'amount' => $withdrawal->amount,
                        'type' => 'debit',
                        'status' => 'success',
                        'description' => 'Withdrawal successful (Paystack)',
                    ]);
                    break;

                case 'transfer.failed':
                case 'transfer.reversed':
                    if ($withdrawal->status === 'failed') {
                        return;
                    }

                    // Refund wallet and user balance
                    $wallet = Wallet::where('user_id', $withdrawal->user_id)->first();
                    if ($wallet) {
                        $wallet->increment('balance', $withdrawal->amount);
                    }

                    $user = $withdrawal->user;
                    if ($user) {
                        $userBalanceColumn = property_exists($user, 'wallet_balance') ? 'wallet_balance' : 'balance';
                        $user->increment($userBalanceColumn, $withdrawal->amount);
                    }

                    $withdrawal->update([
                        'status' => 'failed',
                        'paystack_reference' => $reference ?? $withdrawal->paystack_reference,
                    ]);

                    Transaction::create([
                        'user_id' => $withdrawal->user_id,
                        'amount' => $withdrawal->amount,
                        'type' => 'debit',
                        'status' => 'failed',
                        'description' => 'Withdrawal failed (Paystack), amount refunded',
                    ]);
                    break;

                default:
                    Log::info('Unhandled Paystack webhook event', ['event' => $event, 'data' => $data]);
                    break;
            }
        });

        return response()->json(['received' => true]);
    }
}
