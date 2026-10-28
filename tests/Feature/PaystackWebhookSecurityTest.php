<?php

use Illuminate\Support\Facades\DB;

it('rejects a Paystack webhook without a signature', function () {
    config()->set('services.paystack.secret_key', 'test-only-secret');

    $this->postJson('/api/webhook/paystack', [
        'event' => 'transfer.success',
        'data' => ['reference' => 'unknown-transfer'],
    ])->assertForbidden();
});

it('rejects a Paystack webhook with an invalid signature', function () {
    config()->set('services.paystack.secret_key', 'test-only-secret');

    $this->withHeaders(['X-Paystack-Signature' => str_repeat('0', 128)])
        ->postJson('/api/webhook/paystack', [
            'event' => 'transfer.failed',
            'data' => ['reference' => 'unknown-transfer'],
        ])->assertForbidden();
});
