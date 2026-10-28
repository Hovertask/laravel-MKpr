<?php

use App\Repository\WalletRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('initialize_deposits');
    Schema::create('initialize_deposits', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('reference');
        $table->decimal('amount', 10, 2);
        $table->string('status');
        $table->timestamps();
    });
    DB::table('initialize_deposits')->insert([
        'user_id' => 12,
        'reference' => 'pay-123',
        'amount' => 500,
        'status' => 'successful',
    ]);

    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response([
            'status' => true,
            'data' => [
                'status' => 'success',
                'reference' => 'pay-123',
                'amount' => 50000,
                'metadata' => ['user_id' => 12, 'payment_category' => 'deposit'],
            ],
        ]),
    ]);
});

it('rejects verification for an already settled payment', function () {
    expect(fn () => app(WalletRepository::class)->verifyPayment('pay-123'))
        ->toThrow(\Exception::class, 'already processed');

    expect(DB::table('initialize_deposits')->value('status'))->toBe('successful');
});
