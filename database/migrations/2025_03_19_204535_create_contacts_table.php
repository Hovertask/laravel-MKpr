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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('whatsapp_number')->nullable();
            $table->string('display_name')->nullable();
            $table->string('listing_type')->nullable();
            $table->string('how_you_want_your_profile_listed')->nullable();
            $table->string('how_long_you_want_your_profile_listed')->nullable();
            $table->string('gender')->nullable();
            $table->string('where_you_want_your_contacts_from')->nullable();
            $table->string('display_picture')->nullable();
            $table->string('contact_type')->nullable()->comment('contact, group');
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
