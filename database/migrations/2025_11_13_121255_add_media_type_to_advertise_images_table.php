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
        Schema::table('advertise_images', function (Blueprint $table) {
            // Add a new column to specify media type (image, video, etc.)
            $table->string('media_type')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advertise_images', function (Blueprint $table) {
            $table->dropColumn('media_type');
        });
    }
};
