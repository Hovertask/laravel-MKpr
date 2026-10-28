<?php

use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Http;

it('rejects missing payment amount or owner metadata before settlement', function ($data) {
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => array_merge(['status' => 'success', 'reference' => 'ref-1'], $data),
        ]),
    ]);

    expect(fn () => app(WalletRepository::class)->verifyPayment('ref-1'))
        ->toThrow(\Exception::class, 'Invalid payment verification data');
})->with([
    'missing amount' => [['metadata' => ['user_id' => 3]]],
    'zero amount' => [['amount' => 0, 'metadata' => ['user_id' => 3]]],
    'missing user' => [['amount' => 50000, 'metadata' => []]],
]);
