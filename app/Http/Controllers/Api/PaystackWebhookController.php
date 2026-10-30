<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        DB::transaction(function () use ($event, $data, $reference) {
            // Lock and re-read the record inside the transaction to prevent two
            // simultaneous webhook deliveries from applying the same outcome twice.
            $withdrawal = $reference
                ? Withdrawal::where(function ($query) use ($reference) {
                    $query->where('paystack_reference', $reference)
                        ->orWhere('trx', $reference);
                })->lockForUpdate()->first()
                : null;

            if (! $withdrawal) {
                Log::info('Paystack webhook: no matching withdrawal found', [
                    'event' => $event,
                    'reference' => $reference,
                ]);

                return;
            }

            // Only an awaiting transfer may be settled or refunded.
            // A previously settled transfer needs separate reversal reconciliation.
            if ($withdrawal->status !== 'pending') {
                Log::info('Paystack webhook: withdrawal already settled', [
                    'reference' => $reference,
                    'status' => $withdrawal->status,
                ]);

                return;
            }

            switch ($event) {
                case 'transfer.success':
                case 'transfer.completed':
                    if ($withdrawal->status !== 'pending') {
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
                    if ($withdrawal->status !== 'pending') {
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
