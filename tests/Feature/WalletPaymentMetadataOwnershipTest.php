<?php

use App\Models\User;
use App\Repository\WalletRepository;
use Illuminate\Support\Facades\Http;

it('does not let custom metadata overwrite trusted payment ownership', function () {
    $user = User::factory()->create();
    config()->set('services.paystack.secret_key', 'test-secret');

    Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
        'status' => true,
        'data' => ['authorization_url' => 'https://example.test/pay'],
    ], 200)]);

    app(WalletRepository::class)->initializePayment($user->id, 1000, 'task', 7, [
        'user_id' => 999999,
        'payment_category' => 'deposit',
        'source_id' => 123,
        'description' => 'overridden',
        'custom_note' => 'kept',
    ]);

    Http::assertSent(function ($request) use ($user) {
        $data = $request->data();
        return ($data['metadata']['user_id'] ?? null) === $user->id
            && ($data['metadata']['payment_category'] ?? null) === 'task'
            && ($data['metadata']['source_id'] ?? null) === 7
            && ($data['metadata']['custom_note'] ?? null) === 'kept';
    });
});
