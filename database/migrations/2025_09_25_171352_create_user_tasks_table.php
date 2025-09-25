<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who participated
            $table->foreignId('advertise_id')->constrained()->onDelete('cascade'); // which advert
            $table->string('proof_link')->nullable(); // link to screenshot / proof
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending'); // admin review
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tasks');
    }
};
