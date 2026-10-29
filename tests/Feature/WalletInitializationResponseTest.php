<?php

use App\Models\User;
use App\Repository\WalletRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('users');
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->timestamp('email_verified_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });
});

it('rejects malformed payment initialization responses', function ($body, $status) {
    $user = User::factory()->create();
    Http::fake(['api.paystack.co/transaction/initialize' => Http::response($body, $status)]);

    expect(fn () => app(WalletRepository::class)->initializePayment($user->id, 1000))
        ->toThrow(\Exception::class, 'Invalid payment initialization response');
})->with([
    'status missing' => [['data' => []], 200],
    'data missing' => [['status' => true], 200],
    'invalid data type' => [['status' => true, 'data' => 'invalid'], 200],
    'gateway rejection' => [['status' => false, 'message' => 'Denied'], 400],
]);
