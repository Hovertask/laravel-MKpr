<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('paystack_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('recipient_code')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('account_number')->nullable()->index();
            $table->string('bank_code')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'account_number', 'bank_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('paystack_recipients');
    }
};
