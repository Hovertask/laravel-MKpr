<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('reseller_conversions', function (Blueprint $table) {
        $table->unsignedBigInteger('reseller_id')->nullable()->after('id');

        // Optional: Add foreign key
        $table->foreign('reseller_id')
              ->references('id')
              ->on('users')
              ->onDelete('set null');
    });
}

public function down()
{
    Schema::table('reseller_conversions', function (Blueprint $table) {
        $table->dropForeign(['reseller_id']);
        $table->dropColumn('reseller_id');
    });
}

};
