<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('initialize_deposits');
    Schema::create('initialize_deposits', function (Blueprint $table) {
        $table->id();
        $table->string('reference')->nullable();
    });
});

it('refuses to add a unique index when historical payment references are duplicated', function () {
    DB::table('initialize_deposits')->insert([
        ['reference' => 'same-reference'],
        ['reference' => 'same-reference'],
    ]);

    $migration = require base_path('database/migrations/2026_10_28_225700_enforce_unique_initialized_payment_references.php');

    expect(fn () => $migration->up())
        ->toThrow(\RuntimeException::class, 'Reconcile them before applying the unique index');
});

it('enforces unique references after a clean migration', function () {
    DB::table('initialize_deposits')->insert(['reference' => 'first-reference']);

    $migration = require base_path('database/migrations/2026_10_28_225700_enforce_unique_initialized_payment_references.php');
    $migration->up();

    expect(fn () => DB::table('initialize_deposits')->insert(['reference' => 'first-reference']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
