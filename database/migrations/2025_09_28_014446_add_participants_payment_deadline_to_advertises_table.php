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
        Schema::table('advertises', function (Blueprint $table) {
            $table->unsignedInteger('number_of_participants')->nullable()->after('no_of_status_post');
            $table->decimal('payment_per_task', 10, 2)->nullable()->after('number_of_participants');
            $table->decimal('estimated_cost', 12, 2)->nullable()->after('payment_per_task');
            $table->date('deadline')->nullable()->after('estimated_cost');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertises', function (Blueprint $table) {
            $table->dropColumn([
                'number_of_participants',
                'payment_per_task',
                'estimated_cost',
                'deadline',
            ]);
        });
    }
};
