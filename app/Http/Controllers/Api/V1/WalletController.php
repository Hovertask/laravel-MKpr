<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use Illuminate\Http\Request;
use App\Models\InitializeDeposit;
use App\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repository\IWalletRepository;
use App\Notifications\WalletFundedNotification;
use App\Events\UserWalletUpdated;

class WalletController extends Controller
{
    protected $walletRepository;

    public function __construct(IWalletRepository $walletRepository)
    {
        $this->walletRepository = $walletRepository;
    }

    /**
     * Fund the user's wallet. 
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    
    public function initializePayment(Request $request)
{
    \Log::info('Method:', [request()->method()]);

    $request->validate([
        'type' => 'nullable|string|in:task,advert,deposit,membership',
        'advert_id' => 'nullable|integer', // task_id or advert_id
        'amount' => 'nullable|numeric|min:100',
    ]);

    $userId = Auth::id();
    $type = $request->input('type');
    $recordId = $request->input('advert_id');
    $amount = $request->input('amount');

    try {
        // ✅ Determine amount dynamically
        if ($type === 'task' && $recordId) {
            $task = \App\Models\Task::findOrFail($recordId);
            $amount = $task->task_amount;
        } elseif ($type === 'advert' && $recordId) {
            $advert = \App\Models\Advertise::findOrFail($recordId);
            $amount = $advert->estimated_cost;
        }

        if (!$amount) {
            return response()->json([
                'error' => 'Amount is required if not a task or advert payment.'
            ], 422);
        }

        // ✅ Initialize payment in repository
        $paymentData = $this->walletRepository->initializePayment($userId, $amount, $type, $recordId);
        
        // ✅ Record the transaction
        $transaction = InitializeDeposit::create([
            'user_id' => $userId,
            'reference' => $paymentData['data']['reference'],
            'amount' => $amount,
            'status' => 'pending',
            'trx' => InitializeDeposit::generateTrx(10),
            'type' => InitializeDeposit::resolveTransactionType($type),
            'source_id' => $recordId,
        ]);
 
        Transaction::create([
                'user_id'    => $userId,
                'amount'     => $amount,
                'type' => Transaction::resolveTransactionType($type),
                'status'     => 'pending',
                'description'=> $paymentData['data']['metadata']['description'] ?? 'Wallet funding',
                'reference' => $paymentData['data']['reference'],
                'payment_source' => 'paystack',//gets from config or payment gateway used in future
                'category' => $paymentData['data']['metadata']['$payment_category'] ?? 'Wallet funding',
            ]);


        return response()->json([
            'message' => 'Payment initialized successfully!',
            'data' => $paymentData
        ], 200);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 400);
    }
}

    /**
     * Verify a Paystack payment and fund the wallet.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment($reference)
{
    // Don't rely on the currently authenticated user here — this endpoint may be called
    // by external payment callbacks or from unauthenticated routes. We'll prefer the
    // user id supplied in the payment metadata (set when initializing the payment).
    $user = Auth::user();

    try {
        // If transaction already processed, return a clear response
        if (InitializeDeposit::where('reference', $reference)->where('status', 'successful')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction already processed.'
            ], 400);
        }

        // Call wallet repository to verify with Paystack (or other gateway)
        $paymentData = $this->walletRepository->verifyPayment($reference);
        $amount = $paymentData['data']['amount'] / 100;

        // Prefer user id from payment metadata (safer for webhooks/callbacks)
        $userIdFromMeta = $paymentData['data']['metadata']['user_id'] ?? null;
        if ($userIdFromMeta) {
            $user = \App\Models\User::find($userIdFromMeta);
        }

        if (!isset($paymentData['data'])) {
            throw new \Exception('Invalid response from payment gateway');
        }

        // Update DB record (you might want updateOrCreate or transaction here)
        InitializeDeposit::where('reference', $reference)->update([
            'status'   => 'successful',
            'reference'=> $paymentData['data']['reference'] ?? $reference,
            'token'    => $paymentData['data']['authorization']['authorization_code'] ?? null,
            'method'   => $paymentData['data']['channel'] ?? null,
            'currency' => $paymentData['data']['currency'] ?? null,
            'amount'   => $paymentData['data']['amount'] ?? 0,
        ]);


        Transaction::where('reference', $reference)->update([
                'amount'   => $amount ?? 0,
                'status'     => 'successful',
                'description'=> $paymentData['data']['metadata']['description'] ?? 'Wallet funding',
                'reference' => $paymentData['data']['reference'],
                'category' => $paymentData['data']['metadata']['payment_category'] ?? 'Wallet funding',
            ]);

        // Notify user AFTER DB updates. If we don't have a user (e.g. unauthenticated
        // callback), skip the event but still return success.
        try {
            if ($user) {
                // Reload user to ensure we have fresh balance
                $user->refresh();
                event(new UserWalletUpdated($user->id, $user->balance));
            } else {
                \Log::warning('verifyPayment: user not found for reference', ['reference' => $reference]);
            }
        } catch (\Exception $e) {
            \Log::error('verifyPayment: failed to dispatch UserWalletUpdated', ['error' => $e->getMessage(), 'reference' => $reference]);
        }


        // Return consistent success shape
        return response()->json([
            'success' => true,
            'message' => 'Payment verified and  successfully!',
            'data'    => $paymentData
        ], 200);
    } catch (\Exception $e) {
        // If something fails, mark tx failed (best effort)
        InitializeDeposit::where('reference', $reference)->update(['status' => 'failed']);
        Transaction::where('reference', $reference)->update(['status' => 'failed']);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}


    /**
     * Get the user's wallet balance.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalance()
    {
        $userId = Auth::id();

        try {
            $balance = $this->walletRepository->getBalance($userId);
            return response()->json(['balance' => $balance], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
