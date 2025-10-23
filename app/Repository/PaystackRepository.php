<?php

namespace App\Repository;

use Illuminate\Support\Facades\Http;

class PaystackRepository
{
    protected $secret;
    protected $baseUrl;

    public function __construct()
    {
        $this->secret = config('services.paystack.secret_key');
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
    }

    private function headers()
    {
        return [
            'Authorization' => 'Bearer ' . $this->secret,
            'Accept' => 'application/json',
        ];
    }

    public function createRecipient($name, $accountNumber, $bankCode)
    {
        try {
            $response = Http::withHeaders($this->headers())->post("{$this->baseUrl}/transferrecipient", [
                'type'           => 'nuban',
                'name'           => $name,
                'account_number' => $accountNumber,
                'bank_code'      => $bankCode,
                'currency'       => 'NGN',
            ]);

            $data = $response->json();

            if (!$response->successful() || empty($data) || !($data['status'] ?? false)) {
                \Illuminate\Support\Facades\Log::error('Paystack createRecipient failed', [
                    'http_status' => $response->status(),
                    'response' => $data,
                    'payload' => compact('name', 'accountNumber', 'bankCode'),
                ]);
            }

            return $data ?? ['status' => false, 'message' => 'No response from Paystack'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Paystack createRecipient exception', ['message' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function initiateTransfer($recipientCode, $amount, $reason = 'Wallet Withdrawal')
    {
        try {
            $response = Http::withHeaders($this->headers())->post("{$this->baseUrl}/transfer", [
                'source'    => 'balance',
                'amount'    => $amount * 100, // kobo
                'recipient' => $recipientCode,
                'reason'    => $reason,
            ]);

            $data = $response->json();

            if (!$response->successful() || empty($data) || !($data['status'] ?? false)) {
                \Illuminate\Support\Facades\Log::error('Paystack initiateTransfer failed', [
                    'http_status' => $response->status(),
                    'response' => $data,
                    'payload' => compact('recipientCode', 'amount', 'reason'),
                ]);
            }

            return $data ?? ['status' => false, 'message' => 'No response from Paystack'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Paystack initiateTransfer exception', ['message' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get list of banks for the given country (defaults to NG)
     */
    public function listBanks($country = 'NG')
    {
        try {
            $response = Http::withHeaders($this->headers())->get("{$this->baseUrl}/bank", [
                'country' => strtolower($country),
            ]);

            $data = $response->json();

            if (!$response->successful() || empty($data) || !($data['status'] ?? false)) {
                \Illuminate\Support\Facades\Log::error('Paystack listBanks failed', [
                    'http_status' => $response->status(),
                    'response' => $data,
                ]);
            }

            return $data ?? ['status' => false, 'message' => 'No response from Paystack'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Paystack listBanks exception', ['message' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Resolve an account number & bank code to an account name
     */
    public function resolveAccount($accountNumber, $bankCode)
    {
        try {
            $response = Http::withHeaders($this->headers())->get("{$this->baseUrl}/bank/resolve", [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

            $data = $response->json();

            if (!$response->successful() || empty($data) || !($data['status'] ?? false)) {
                \Illuminate\Support\Facades\Log::warning('Paystack resolveAccount failed', [
                    'http_status' => $response->status(),
                    'response' => $data,
                    'payload' => compact('accountNumber', 'bankCode'),
                ]);
            }

            return $data ?? ['status' => false, 'message' => 'No response from Paystack'];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Paystack resolveAccount exception', ['message' => $e->getMessage()]);
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
