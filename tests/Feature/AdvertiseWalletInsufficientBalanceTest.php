<?php

use App\Models\User;
use App\Repository\AdvertiseRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

it('does not create an advert when the wallet cannot cover its cost', function () {
    Schema::dropIfExists('advertises');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('email')->nullable();
    });
    Schema::create('wallets', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->decimal('balance', 12, 2)->default(0);
        $table->timestamps();
    });
    Schema::create('advertises', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('title')->nullable();
        $table->timestamps();
    });

    $user = new User();
    $user->id = 31;
    $user->exists = true;
    auth()->setUser($user);

    \Illuminate\Support\Facades\DB::table('wallets')->insert([
        'user_id' => 31, 'balance' => 100, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $request = Request::create('/api/advertise', 'POST', [
        'title' => 'Campaign',
        'payment_method' => 'wallet',
        'estimated_cost' => 200,
        'no_of_status_post' => 1,
    ]);

    expect(fn () => app(AdvertiseRepository::class)->create($request->all(), $request))
        ->toThrow(\Exception::class, 'Insufficient wallet balance');

    expect(\Illuminate\Support\Facades\DB::table('advertises')->count())->toBe(0);
    expect((float) \Illuminate\Support\Facades\DB::table('wallets')->value('balance'))->toBe(100.0);
});

it('rolls back the wallet debit if advert insertion fails', function () {
    Schema::dropIfExists('advertises');
    Schema::dropIfExists('wallets');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
    });
    Schema::create('wallets', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->decimal('balance', 12, 2)->default(0);
        $table->timestamps();
    });
    Schema::create('advertises', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('title');
        $table->string('required_integrity_field');
        $table->timestamps();
    });

    $user = new User();
    $user->id = 32;
    $user->exists = true;
    auth()->setUser($user);
    \Illuminate\Support\Facades\DB::table('wallets')->insert([
        'user_id' => 32, 'balance' => 300, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $request = Request::create('/api/advertise', 'POST', [
        'title' => 'Wallet campaign',
        'no_of_status_post' => 1,
        'payment_method' => 'wallet',
        'estimated_cost' => 200,
    ]);

    expect(fn () => app(AdvertiseRepository::class)->create($request->all(), $request))
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(\Illuminate\Support\Facades\DB::table('advertises')->count())->toBe(0);
    expect((float) \Illuminate\Support\Facades\DB::table('wallets')->value('balance'))->toBe(300.0);
});
