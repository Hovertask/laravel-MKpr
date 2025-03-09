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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('gender')->after('due_date')->nullable();
            $table->string('location')->after('due_date')->nullable();
            $table->string('no_of_participants')->after('due_date')->nullable();
            $table->string('payment_per_task')->after('due_date')->nullable();
            $table->string('religion')->after('due_date')->nullable();
            $table->string('social_media_url')->after('title')->nullable();
            $table->string('type_of_comment')->after('due_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('gender');
            $table->dropColumn('location');
            $table->dropColumn('no_of_participants');
            $table->dropColumn('payment_per_task');
            $table->dropColumn('religion');
            $table->dropColumn('social_media_url');
            $table->dropColumn('type_of_comment');
        });
    }
};
