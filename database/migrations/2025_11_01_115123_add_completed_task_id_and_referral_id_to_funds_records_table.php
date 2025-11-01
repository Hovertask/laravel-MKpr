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
        Schema::table('funds_records', function (Blueprint $table) {
            // Add completed_task_id (nullable, foreign key optional)
            $table->unsignedBigInteger('completed_task_id')->nullable()->after('user_id');
            
            // Add referral_id (nullable, foreign key optional)
            $table->unsignedBigInteger('referral_id')->nullable()->after('completed_task_id');

            // Optional: if you want to enforce foreign key relationships
            // (Uncomment these if you have the related tables)
             $table->foreign('completed_task_id')->references('id')->on('completed_tasks')->onDelete('set null');
             $table->foreign('referral_id')->references('id')->on('referrals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funds_records', function (Blueprint $table) {
            // Drop foreign keys first (if they exist)
             $table->dropForeign(['completed_task_id']);
             $table->dropForeign(['referral_id']);

            $table->dropColumn(['completed_task_id', 'referral_id']);
        });
    }
};
