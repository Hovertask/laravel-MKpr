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
        Schema::create('k_y_c_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('public_id')->nullable();
            $table->string('full_name', 100)->nullable();
            $table->string('dob', 50)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('document_type', 80)->nullable();
            $table->string('document_number', 80)->nullable();
            $table->string('document_expiry', 50)->nullable();
            $table->string('document_front_image')->nullable();
            $table->string('document_back_image')->nullable();
            $table->string('user_selfie_image')->nullable();
            $table->string('user_selfie_video')->nullable();
            $table->string('status', 50)->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('k_y_c_s');
    }
};
