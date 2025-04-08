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
            $table->json('file_path')->nullable()->after('gender');
            $table->json('video_path')->nullable()->after('gender');
            $table->string('category')->after('due_date')->nullable();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('video_path')->nullable()->after('images');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('file_path');
            $table->dropColumn('video_path');
            $table->dropColumn('category');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });
    }
};
