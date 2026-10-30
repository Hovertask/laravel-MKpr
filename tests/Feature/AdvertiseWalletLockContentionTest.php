<?php

use App\Models\User;
use App\Repository\AdvertiseRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('blocks a competing wallet spend while another PostgreSQL transaction holds the wallet lock', function () {
    if (DB::getDriverName() !== 'pgsql') {
        test()->markTestSkipped('Requires PostgreSQL row-level locking');
    }

    Schema::dropIfExists('advertises');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', fn (Blueprint $t) => $t->id());
    Schema::create('wallets', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->decimal('balance', 12, 2);
        $t->timestamps();
    });
    Schema::create('advertises', function (Blueprint $t) {
        $t->id();
        $t->unsignedBigInteger('user_id');
        $t->string('title')->nullable();
        $t->timestamps();
    });

    $user = new User();
    $user->id = 107;
    $user->exists = true;
    auth()->setUser($user);

    DB::table('wallets')->insert([
        'user_id' => 107, 'balance' => 100, 'created_at' => now(), 'updated_at' => now(),
    ]);

    // Separate PostgreSQL sessions model two independent requests.
    $original = config('database.default');
    config()->set('database.connections.wallet_competitor', config('database.connections.pgsql'));
    $holder = DB::connection('pgsql');
    $competitor = DB::connection('wallet_competitor');

    try {
        $holder->beginTransaction();
        $holder->table('wallets')->where('user_id', 107)->lockForUpdate()->first();

        config()->set('database.default', 'wallet_competitor');
        $competitor->statement("SET lock_timeout = '300ms'");

        $request = Request::create('/api/advertise', 'POST', [
            'title' => 'Competing campaign',
            'payment_method' => 'wallet',
            'estimated_cost' => 80,
            'no_of_status_post' => 1,
        ]);

        // A real row lock must stop the competing request before Advertise::create.
        expect(fn () => app(AdvertiseRepository::class)->create($request->all(), $request))
            ->toThrow(\Illuminate\Database\QueryException::class);
    } finally {
        config()->set('database.default', $original);
        if ($holder->transactionLevel() > 0) {
            $holder->rollBack();
        }
        DB::purge('wallet_competitor');
    }

    expect(DB::table('advertises')->count())->toBe(0);
    expect((int) DB::table('wallets')->value('balance'))->toBe(100);
});
