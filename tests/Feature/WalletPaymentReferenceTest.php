<?php

use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Http;

it('rejects a Paystack verification response for a different reference', function () {
    config()->set('services.paystack.secret_key', 'test-secret');

    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => 'success',
                'reference' => 'another-payment',
                'amount' => 100000,
                'metadata' => ['user_id' => 99, 'payment_category' => 'deposit'],
            ],
        ], 200),
    ]);

    expect(fn () => app(WalletRepository::class)->verifyPayment('requested-payment'))
        ->toThrow(\Exception::class, 'Payment reference mismatch');

    Http::assertSentCount(1);
});
