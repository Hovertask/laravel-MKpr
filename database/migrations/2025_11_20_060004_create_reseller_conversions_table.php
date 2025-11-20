<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reseller_conversions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');
            $table->string('reseller_code');   // reseller unique token
            $table->string('visitor_cookie');  // unique cookie id
            $table->string('ip')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->unique(['reseller_code', 'visitor_cookie']); // prevent duplicate conversion forever
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_conversions');
    }
};
