<?php

use App\Models\User;
use App\Repository\AdvertiseRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('does not create a wallet-funded advert or ledger debit when the wallet is underfunded', function () {
    Schema::dropIfExists('advertises');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', fn (Blueprint $t) => $t->id());
    Schema::create('wallets', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->decimal('balance', 12, 2)->default(0);
        $t->timestamps();
    });
    Schema::create('advertises', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('title')->nullable();
        $t->timestamps();
    });

    $user = new User();
    $user->id = 100;
    $user->exists = true;
    auth()->setUser($user);

    DB::table('wallets')->insert([
        'user_id' => 100, 'balance' => 50, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $request = Request::create('/api/advertise', 'POST', [
        'payment_method' => 'wallet', 'title' => 'Must not persist',
        'estimated_cost' => 80, 'no_of_status_post' => 1,
    ]);

    expect(fn () => app(AdvertiseRepository::class)->create($request->all(), $request))
        ->toThrow(\Exception::class, 'Insufficient wallet balance');
    expect(DB::table('advertises')->count())->toBe(0);
    expect((int) DB::table('wallets')->value('balance'))->toBe(50);
});
