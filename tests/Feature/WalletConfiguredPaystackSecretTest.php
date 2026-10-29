<?php

use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Http;

it('uses the configured Paystack secret for verification requests', function () {
    config()->set('services.paystack.secret_key', 'configured-test-secret');

    Http::fake([
        'api.paystack.co/*' => Http::response(['status' => false], 400),
    ]);

    expect(fn () => app(WalletRepository::class)->verifyPayment('known-ref'))
        ->toThrow(\Exception::class);

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer configured-test-secret');
    });
});
