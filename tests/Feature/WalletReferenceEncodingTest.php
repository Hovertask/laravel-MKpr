<?php

use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Http;

it('encodes special characters in a payment reference sent to Paystack', function () {
    Http::fake([
        'api.paystack.co/*' => Http::response(['status' => false], 400),
    ]);

    expect(fn () => app(WalletRepository::class)->verifyPayment('payment/ref?note=a b'))
        ->toThrow(\Exception::class);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.paystack.co/transaction/verify/payment%2Fref%3Fnote%3Da%20b';
    });
});

it('rejects empty payment references without contacting Paystack', function ($reference) {
    Http::fake();

    expect(fn () => app(WalletRepository::class)->verifyPayment($reference))
        ->toThrow(\Exception::class, 'Payment reference is required');

    Http::assertNothingSent();
})->with(['empty' => [''], 'whitespace' => ['   ']]);
