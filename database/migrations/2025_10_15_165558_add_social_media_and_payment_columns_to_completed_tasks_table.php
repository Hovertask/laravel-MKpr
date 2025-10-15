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
        Schema::table('completed_tasks', function (Blueprint $table) {
            // Add new columns
            $table->string('social_media_url')->nullable()->after('advert_id');
            $table->decimal('payment_per_task', 10, 2)->default(0)->after('social_media_url');
            $table->string('title')->nullable()->after('payment_per_task');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('completed_tasks', function (Blueprint $table) {
            $table->dropColumn(['social_media_url', 'payment_per_task', 'title']);
        });
    }
};
