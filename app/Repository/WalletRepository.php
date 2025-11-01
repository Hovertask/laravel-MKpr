<?php

namespace App\Repository;

use Exception;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Referral;
use App\Models\InitializeDeposit;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WalletRepository implements IWalletRepository
{
    protected $paystackSecretKey;

    public function __construct()
    {
        $this->paystackSecretKey = env('PAYSTACK_SECRET_KEY');
    }


    public function initializePayment(int $userId, float $amount, ?string $type = null, ?int $recordId = null)
{
    try {
        $user = User::findOrFail($userId);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $user->email,
            'amount' => $amount * 100,
            'metadata' => [
                'user_id' => $userId,
                'payment_category' => $type ?? 'deposit',
                'source_id' => $recordId,
                'description' => match ($type) {
                    'task' => 'paid for Task creation',
                    'advert' => 'paid for Advert creation',
                    'membership' => 'paid for Membership',
                    default => 'Wallet Deposit',
                },
            ],
        ]);

        $responseData = $response->json();

        if (!$response->successful() || !$responseData['status']) {
            throw new Exception("Failed to initialize payment: " . ($responseData['message'] ?? 'Unknown error'));
        }

        return $responseData;
    } catch (Exception $e) {
        throw new Exception("Failed to initialize payment: " . $e->getMessage());
    }
}

    // public function verifyPayment(string $reference)
    // {
    //     try {
    //         $response = Http::withHeaders([
    //             'Authorization' => 'Bearer ' . $this->paystackSecretKey,
    //             'Content-Type' => 'application/json',
    //         ])->get("https://api.paystack.co/transaction/verify/{$reference}");

    //         $responseData = $response->json();

    //         //dd($responseData);

    //         if (!$response->successful() || !$responseData['status']) {
    //             throw new Exception("Failed to verify payment: " . ($responseData['message'] ?? 'Unknown error'));
    //         }

    //         if ($responseData['data']['status'] !== 'success') {
    //             throw new Exception("Payment not successful: " . $responseData['data']['gateway_response']);
    //         }

    //         $userId = $responseData['data']['metadata']['user_id'];
    //         $amount = $responseData['data']['amount'] / 100;

    //         $user = User::find($userId);
    //         // Fund the user's wallet
    //         $wallet = Wallet::firstOrCreate(
    //             ['user_id' => $userId],
    //             ['balance' => 0]
    //         );

    //         DB::beginTransaction();

    //         $wallet->balance += $amount;
    //         $wallet->save();

    //         //we are passing the balance to users table because the FE said he needs, however wallet balance is same as user->getBalance
    //         $user->balance += $amount;
    //         $user->save();

    //         DB::commit();
    //         return $responseData;

    //     } catch (Exception $e) {
    //         throw new Exception("Failed to verify payment: " . $e->getMessage());
    //     }
    // }

 public function verifyPayment(string $reference)
{
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->paystackSecretKey,
            'Content-Type' => 'application/json',
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        $responseData = $response->json();

        if (!$response->successful() || !$responseData['status']) {
            throw new Exception("Failed to verify payment: " . ($responseData['message'] ?? 'Unknown error'));
        }

        if ($responseData['data']['status'] !== 'success') {
            throw new Exception("Payment not successful: " . $responseData['data']['gateway_response']);
        }

        // ✅ Extract metadata info
        $metadata = $responseData['data']['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;
        $paymentCategory = $metadata['payment_category'] ?? 'deposit';
        $sourceId = $metadata['source_id'] ?? null;

        $amount = $responseData['data']['amount'] / 100;
        $reference = $responseData['data']['reference'];

        // ✅ Get Super Administrator ID dynamically
        $superAdminId = \DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.fname', 'superadministrator')
            ->orWhere('roles.lname', 'superadmin')
            ->value('users.id');

            // ✅ Log for debugging
\Log::info('Super Administrator ID fetched for platform earnings:', ['super_admin_id' => $superAdminId]);

 
        DB::beginTransaction();   

        // ✅ If payment is for a task or advert, mark as paid
        if ($paymentCategory === 'task' && $sourceId) {
            $task = \App\Models\Task::find($sourceId);
            if ($task) {
                $task->status = 'success';
                $task->save();
            }
        } elseif ($paymentCategory === 'advert' && $sourceId) {
            $advert = \App\Models\Advertise::find($sourceId);
            if ($advert) {
                $advert->status = 'success';
                $advert->save();
            }
        } elseif ($paymentCategory === 'deposit') {
            // ✅ Simple wallet deposit
            $user = User::findOrFail($userId);
            $wallet = Wallet::lockForUpdate()->firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0]
            );

            $wallet->balance += $amount;
            $wallet->save();

            $user->balance += $amount;
            $user->save();

        } else {
            // ✅ Membership / Referral Flow
            $user = User::findOrFail($userId);
            $wallet = Wallet::lockForUpdate()->firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0]
            );

            $platformFee = 600;
            $membershipBonus = $amount - $platformFee;

            if ($membershipBonus <= 0) {
                throw new Exception("Amount after platform fee must be greater than zero.");
            }

            // ✅ Log platform earnings for super admin
            Transaction::create([
                'user_id'     => $superAdminId,
                'amount'      => $platformFee,
                'type'        => 'credit',
                'status'      => 'success',
                'description' => 'Platform Earnings',
                'reference'   => $responseData['data']['reference'],
                'payment_source' => 'system',
                'category'    => 'platform_fee',
            ]);

            // ✅ Handle referral logic
            $referral = Referral::where('referee_id', $user->id)->first();

            if (!$user->is_member && $referral && $referral->reward_status == 'pending') {
                $referrer = User::find($referral->referrer_id);

                if ($referrer && $referrer->is_member) {
                    $referralAmount = 500;

                    // Update referrer's wallet
                    $referrerWallet = Wallet::firstOrCreate(
                        ['user_id' => $referrer->id],
                        ['balance' => 0]
                    );
                    $referrerWallet->balance += $referralAmount;
                    $referrerWallet->save();

                    // Update referrer's user balance
                    $referrer->balance += $referralAmount;
                    $referrer->save();

                    // Update referral record
                    $referral->reward_status = 'paid';
                    $referral->amount = $referralAmount;
                    $referral->save();

                    // Record transaction for referral reward
                    Transaction::create([
                        'user_id'     => $referrer->id,
                        'amount'      => $referralAmount,
                        'type'        => 'credit',
                        'status'      => 'success',
                        'description' => 'Referrer reward',
                        'reference'   => $responseData['data']['reference'],
                        'payment_source' => 'system',
                        'category'    => 'referral_commission',
                    ]);

                    // Credit membership bonus to user
                    $wallet->balance += $membershipBonus;
                    $wallet->save();

                    $user->balance += $membershipBonus;
                    $user->is_member = true;
                    $user->save();

                    Transaction::create([
                        'user_id'     => $userId,
                        'amount'      => $membershipBonus,
                        'type'        => 'credit',
                        'status'      => 'success',
                        'description' => 'Membership bonus credited to user wallet',
                        'reference'   => $responseData['data']['reference'],
                        'payment_source' => 'system',
                        'category'    => 'membership_bonus',
                    ]);
                }
            } else {
                // No referral — full membership bonus to user
                $wallet->balance += $membershipBonus;
                $wallet->save();

                $user->balance += $membershipBonus;
                $user->is_member = true;
                $user->save();

                Transaction::create([
                    'user_id'     => $userId,
                    'amount'      => $membershipBonus,
                    'type'        => 'credit',
                    'status'      => 'success',
                    'description' => 'Membership bonus credited to user wallet',
                    'reference'   => $responseData['data']['reference'],
                    'payment_source' => 'system',
                    'category'    => 'membership_bonus',
                ]);
            }
        }

        DB::commit();
        return $responseData;

    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Payment verification failed: ' . $e->getMessage());
        throw new Exception("Failed to verify payment: " . $e->getMessage());
    }
}




    // public function verifyPayment(string $reference)
    // {
        
    //         try {
    //             $response = Http::withHeaders([
    //                 'Authorization' => 'Bearer ' . $this->paystackSecretKey,
    //                 'Content-Type' => 'application/json',
    //             ])->get("https://api.paystack.co/transaction/verify/{$reference}");

    //             $responseData = $response->json();

    //             if (!$response->successful() || !$responseData['status']) {
    //                 throw new Exception("Failed to verify payment: " . ($responseData['message'] ?? 'Unknown error'));
    //             }

    //             if ($responseData['data']['status'] !== 'success') {
    //                 throw new Exception("Payment not successful: " . $responseData['data']['gateway_response']);
    //             }

    //             $userId = $responseData['data']['metadata']['user_id'];
    //             $amount = $responseData['data']['amount'] / 100;
    //             $reference = $responseData['data']['reference'];

    //             DB::beginTransaction();

    //             $user = User::findOrFail($userId);

    //             $wallet = Wallet::lockForUpdate()->firstOrCreate(
    //                 ['user_id' => $userId],
    //                 ['balance' => 0]
    //             );
                

    //             $referred_by = Referral::where('referee_id', $user->id)->first();
    //             //dd($referred_by->referrer_id);
    //             if (!$user->is_member && $referred_by->referrer_id && $referred_by->reward_status == 'pending') {
    //                 //dd('is here');
    //                 $referer = $referred_by->referrer_id;
    //                 $refUser = User::find($referer);


    //                 if ($refUser && $refUser->is_member) {
    //                     $half = $amount / 2;

    //                     $refWallet = Wallet::firstOrCreate(['user_id' => $referer], ['balance' => 0]);
    //                     $refWallet->balance += $half;
    //                     $refWallet->save();

    //                     $refUser->balance += $half;
    //                     $refUser->save();

    //                     $referred_by->reward_status = 'paid';
    //                     $referred_by->save();

    //                     $user->is_member = true;
    //                     $user->save();

    //                     //dd($user->is_member);
    //                 //dd($refUser);
    //                 }
    //             } elseif ($user->is_member) {
    //                 $wallet->balance += $amount;
    //                 $wallet->save();

    //                 $user->balance += $amount;
    //                 $user->save();
    //             }

    //         // Optional: Record transaction
    //         // Transaction::create([
    //         //     'user_id' => $userId,
    //         //     'reference' => $reference,
    //         //     'amount' => $amount,
    //         //     'status' => 'success',
    //         //     'channel' => $responseData['data']['channel'],
    //         //     'paid_at' => $responseData['data']['paid_at'],
    //         // ]);

    //         DB::commit();

    //         return $responseData;

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Payment verification failed: ' . $e->getMessage());
    //         throw new Exception("Failed to verify payment: " . $e->getMessage());
    //     }
    // }


    public function getBalance(int $userId)
    {
        try {
            $wallet = Wallet::where('user_id', $userId)->first();

            if (!$wallet) {
                return 0; // Return 0 if the wallet doesn't exist
            }

            return $wallet->balance;
        } catch (Exception $e) {
            throw new Exception("Failed to fetch wallet balance: " . $e->getMessage());
        }
    }

}