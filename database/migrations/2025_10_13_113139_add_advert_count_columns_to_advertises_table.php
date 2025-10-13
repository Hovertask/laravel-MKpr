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
            // Add total number of participants/expected completions
            $table->integer('task_count_total')->default(0)->after('payment_per_task');

            // Add number of remaining participants/tasks
            $table->integer('task_count_remaining')->default(0)->after('task_count_total');

            // Add priority (low, medium, high) with default low
            $table->string('priority')->default('low')->after('task_count_remaining');

            // Add completed status, default false
            $table->boolean('completed')->default(false)->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertises', function (Blueprint $table) {
            $table->dropColumn(['task_count_total', 'task_count_remaining', 'priority', 'completed']);
        });
    }
};
