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
        Schema::create('manual_social_account_linkings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('facebook_profile')->nullable();
            $table->string('facebook_uname')->nullable();
            $table->string('tiktok_profile')->nullable();
            $table->string('tiktok_uname')->nullable();
            $table->string('instagram_profile')->nullable();
            $table->string('instagram_uname')->nullable();
            $table->string('x_profile')->nullable();
            $table->string('x_uname')->nullable();
            $table->timestamps();
        });

          Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_account_linked')->after('bank_name')->default(false)->nullable();
            $table->boolean('is_facebook_account_linked')->after('bank_name')->default(false)->nullable();
            $table->boolean('is_x_account_linked')->after('bank_name')->default(false)->nullable();
            $table->boolean('is_instagram_account_linked')->after('bank_name')->default(false)->nullable();
            $table->boolean('is_tiktok_account_linked')->after('bank_name')->default(false)->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_account_linked');
            $table->dropColumn('is_facebook_account_linked');
            $table->dropColumn('is_x_account_linked');
            $table->dropColumn('is_instagram_account_linked');
            $table->dropColumn('is_tiktok_account_linked');
        });
        Schema::dropIfExists('manual_social_account_linkings');
    }
};
