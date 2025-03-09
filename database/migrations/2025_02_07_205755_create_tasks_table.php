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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('description');
            $table->string('platforms')->nullable();
            $table->integer('task_amount')->nullable();
            $table->integer('task_type')->nullable();
            $table->integer('task_count_total')->nullable();
            $table->integer('task_count_remaining')->nullable();
            $table->string('priority')->nullable()->default('low');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
