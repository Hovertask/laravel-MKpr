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
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->nullable();
            $table->string('provider_user_id')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('token')->nullable();
            $table->string('token_secret')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('expires_in')->nullable();
            $table->string('username')->nullable();
            $table->string('tiktok_username')->nullable();
            $table->string('instagram_username')->nullable();
            $table->string('facebook_username')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
