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
        Schema::table('products', function (Blueprint $table) {
            $table->string('currency')->after('stock')->default('NGN')->nullable();
            $table->decimal('discount', 5, 2)->after('stock')->default(0);
            $table->string('payment_method')->after('stock')->default('wallet')->nullable();
            $table->integer('meet_up_preference')->after('stock')->nullable()->comment('1 = SHIPPING/Delivery Available, 2 = Digital Delivery/Online Services Only, 3 = Shipping/Delivery and Digital Delivery/Online Services Available');
            $table->decimal('delivery_fee', 10, 2)->after('stock')->nullable()->default(0);
            $table->string('estimated_delivery_date')->after('stock')->nullable();
            $table->string('phone_number')->after('stock')->nullable();
            $table->string('email')->after('stock')->nullable();
            $table->string('social_media_link')->after('stock')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['currency', 'discount', 'payment_method', 'meet_up_preference', 'delivery_fee', 'estimated_delivery_date', 'phone_number', 'email', 'social_media_link']);
        });
    }
};
