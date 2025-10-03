<?php

namespace App\Repositories;

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
        return Http::withHeaders($this->headers())->post("{$this->baseUrl}/transferrecipient", [
            'type'           => 'nuban',
            'name'           => $name,
            'account_number' => $accountNumber,
            'bank_code'      => $bankCode,
            'currency'       => 'NGN',
        ])->json();
    }

    public function initiateTransfer($recipientCode, $amount, $reason = 'Wallet Withdrawal')
    {
        return Http::withHeaders($this->headers())->post("{$this->baseUrl}/transfer", [
            'source'    => 'balance',
            'amount'    => $amount * 100, // kobo
            'recipient' => $recipientCode,
            'reason'    => $reason,
        ])->json();
    }
}
