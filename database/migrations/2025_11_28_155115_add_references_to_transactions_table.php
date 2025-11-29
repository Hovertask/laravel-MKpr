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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('platform_reference')->nullable()->after('reference');
            $table->string('gateway_reference')->nullable()->after('platform_reference');
            $table->string('parent_reference')->nullable()->after('gateway_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn([
                'platform_reference',
                'gateway_reference',
                'parent_reference',
            ]);
        });
    }
};
