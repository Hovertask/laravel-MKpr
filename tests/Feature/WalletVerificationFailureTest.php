<?php

use App\Repository\IWalletRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('does not mark an unverified deposit failed when the gateway cannot be reached', function () {
    Schema::create('initialize_deposits', function (Blueprint $table) {
        $table->id();
        $table->string('reference');
        $table->string('status');
        $table->timestamps();
    });
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->string('gateway_reference')->nullable();
        $table->string('status');
        $table->timestamps();
    });

    DB::table('initialize_deposits')->insert([
        'reference' => 'gateway-timeout-123',
        'status' => 'pending',
    ]);
    DB::table('transactions')->insert([
        'gateway_reference' => 'gateway-timeout-123',
        'status' => 'pending',
    ]);

    $this->mock(IWalletRepository::class)
        ->shouldReceive('verifyPayment')
        ->once()
        ->with('gateway-timeout-123')
        ->andThrow(new \RuntimeException('Gateway unavailable'));

    $this->getJson('/api/wallet/verify-payment/gateway-timeout-123')
        ->assertStatus(400);

    expect(DB::table('initialize_deposits')->value('status'))->toBe('pending')
        ->and(DB::table('transactions')->value('status'))->toBe('pending');
});
