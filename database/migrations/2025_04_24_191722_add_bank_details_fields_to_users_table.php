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
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name')->after('remember_token')->nullable();
            $table->string('account_name')->after('remember_token')->nullable();
            $table->string('account_number')->after('remember_token')->nullable();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('advertises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 200)->nullable();
            $table->json('platforms')->nullable();
            $table->integer('no_of_status_post')->nullable();
            $table->string('gender', 10)->nullable();
            $table->json('location')->nullable();
            $table->json('religion')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('admin_approval_status', 20)->nullable()->default('pending');
            $table->string('status', 20)->nullable()->default('active');
            $table->timestamps();
        });

        Schema::create('advertise_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advertise_id')->constrained()->onDelete('cascade');
            $table->string('public_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('video_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_name', 'account_number']);

            Schema::dropIfExists('transactions');

            Schema::dropIfExists('advertises');

            Schema::dropIfExists('advertise_images');
        });
    }
};
