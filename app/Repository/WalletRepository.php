<?php

namespace App\Repository;

use Exception;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class WalletRepository implements IWalletRepository
{
    protected $paystackSecretKey;

    public function __construct()
    {
        $this->paystackSecretKey = env('PAYSTACK_SECRET_KEY');
    }

    public function initializePayment(int $userId, float $amount)
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

    public function verifyPayment(string $reference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->paystackSecretKey,
                'Content-Type' => 'application/json',
            ])->get("https://api.paystack.co/transaction/verify/{$reference}");

            $responseData = $response->json();

            //dd($responseData);

            if (!$response->successful() || !$responseData['status']) {
                throw new Exception("Failed to verify payment: " . ($responseData['message'] ?? 'Unknown error'));
            }

            if ($responseData['data']['status'] !== 'success') {
                throw new Exception("Payment not successful: " . $responseData['data']['gateway_response']);
            }

            $userId = $responseData['data']['metadata']['user_id'];
            $amount = $responseData['data']['amount'] / 100;

            // Fund the user's wallet
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0]
            );

            $wallet->balance += $amount;
            $wallet->save();

            return $responseData;
        } catch (Exception $e) {
            throw new Exception("Failed to verify payment: " . $e->getMessage());
        }
    }

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