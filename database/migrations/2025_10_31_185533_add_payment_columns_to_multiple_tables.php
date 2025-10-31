<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 🧩 1. Add to 'advertises' table
        Schema::table('advertises', function (Blueprint $table) {
            if (!Schema::hasColumn('advertises', 'status')) {
                $table->string('status')->default('pending')->after('user_id');
            }
            if (!Schema::hasColumn('advertises', 'payment_gateway')) {
                $table->string('payment_gateway')->nullable()->after('status');
            }
        });

        // 🧩 2. Add to 'tasks' table
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'payment_gateway')) {
                $table->string('payment_gateway')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tasks', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('payment_gateway');
            }
        });

        // 🧩 3. Add to 'initialize_deposits' table
        Schema::table('initialize_deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('initialize_deposits', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('trx');
            }
            if (!Schema::hasColumn('initialize_deposits', 'type')) {
                $table->string('type')->nullable()->after('source_id');
            }
        });

        // 🧩 4. Add to 'transactions' table
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'payment_source')) {
                $table->string('payment_source')->nullable()->after('status');
            }
            if (!Schema::hasColumn('transactions', 'reference')) {
                $table->string('reference')->nullable()->after('payment_source');
            }
            if (!Schema::hasColumn('transactions', 'category')) {
                $table->string('category')->nullable()->after('reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertises', function (Blueprint $table) {
            $table->dropColumn(['status', 'payment_gateway']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'payment_method']);
        });

        Schema::table('initialize_deposits', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'type']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_source', 'reference', 'category']);
        });
    }
};
