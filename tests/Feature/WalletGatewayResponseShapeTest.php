<?php

use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Http;

it('rejects malformed or unsuccessful gateway responses without accessing absent fields', function ($payload, $httpStatus) {
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response($payload, $httpStatus)]);

    expect(fn () => app(WalletRepository::class)->verifyPayment('ref-1'))
        ->toThrow(\Exception::class);
})->with([
    'missing status' => [['data' => []], 200],
    'missing data' => [['status' => true], 200],
    'invalid data shape' => [['status' => true, 'data' => 'invalid'], 200],
    'unsuccessful transaction without reason' => [['status' => true, 'data' => ['status' => 'failed']], 200],
    'upstream error' => [['message' => 'Service unavailable'], 503],
]);
