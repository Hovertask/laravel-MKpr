<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config()->set('services.paystack.secret_key', 'webhook-test-secret');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->integer('balance')->default(0);
        $table->timestamps();
    });
    Schema::create('wallets', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('balance')->default(0);
        $table->timestamps();
    });
    Schema::create('withdrawals', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('amount');
        $table->string('trx');
        $table->string('paystack_reference')->nullable();
        $table->string('status')->default('pending');
        $table->timestamps();
    });
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->integer('amount');
        $table->string('type');
        $table->string('status');
        $table->string('description')->nullable();
        $table->timestamps();
    });

    DB::table('users')->insert(['id' => 1, 'balance' => 0]);
    DB::table('wallets')->insert(['user_id' => 1, 'balance' => 0]);
    DB::table('withdrawals')->insert([
        'user_id' => 1, 'amount' => 500, 'trx' => 'transfer-123',
        'status' => 'pending',
    ]);
});

function signedPaystackTransfer(string $event): \Illuminate\Testing\TestResponse
{
    $json = json_encode([
        'event' => $event,
        'data' => ['reference' => 'transfer-123'],
    ], JSON_THROW_ON_ERROR);

    return test()->call('POST', '/api/webhook/paystack', [], [], [], [
        'HTTP_CONTENT_TYPE' => 'application/json',
        'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $json, 'webhook-test-secret'),
    ], $json);
}

it('refunds a failed transfer once when the same webhook is delivered twice', function () {
    signedPaystackTransfer('transfer.failed')->assertOk();
    signedPaystackTransfer('transfer.failed')->assertOk();

    expect(DB::table('wallets')->value('balance'))->toBe(500)
        ->and(DB::table('users')->value('balance'))->toBe(500)
        ->and(DB::table('withdrawals')->value('status'))->toBe('failed')
        ->and(DB::table('transactions')->count())->toBe(1);
});

it('records a successful transfer once when the same webhook is delivered twice', function () {
    signedPaystackTransfer('transfer.success')->assertOk();
    signedPaystackTransfer('transfer.success')->assertOk();

    expect(DB::table('wallets')->value('balance'))->toBe(0)
        ->and(DB::table('users')->value('balance'))->toBe(0)
        ->and(DB::table('withdrawals')->value('status'))->toBe('success')
        ->and(DB::table('transactions')->count())->toBe(1);
});

it('does not match an unknown transfer solely by amount', function () {
    $json = json_encode([
        'event' => 'transfer.failed',
        'data' => ['reference' => 'unknown-123', 'amount' => 50000],
    ], JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha512', $json, 'webhook-test-secret');

    $this->call('POST', '/api/webhook/paystack', [], [], [], [
        'HTTP_CONTENT_TYPE' => 'application/json',
        'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
    ], $json)->assertOk();

    expect(DB::table('withdrawals')->value('status'))->toBe('pending')
        ->and(DB::table('wallets')->value('balance'))->toBe(0)
        ->and(DB::table('transactions')->count())->toBe(0);
});
