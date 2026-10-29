<?php

use Illuminate\Support\Facades\Mail;

it('does not expose a public email-sending diagnostic endpoint', function () {
    Mail::fake();

    $this->get('/api/test-mail')->assertNotFound();

    Mail::assertNothingOutgoing();
});

it('requires authentication before disclosing wallet balance', function () {
    $this->getJson('/api/v1/wallet/balance')->assertUnauthorized();
});

it('requires authentication before initializing a wallet payment', function () {
    $this->postJson('/api/v1/wallet/initialize-payment', ['amount' => 1000])
        ->assertUnauthorized();
});
