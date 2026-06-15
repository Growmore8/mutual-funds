<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // e.g. Bank Wire, USDT (TRC20)
            $table->string('type')->default('bank');      // bank | crypto | card | ewallet
            $table->string('currency')->nullable();       // USD, USDT, etc.
            $table->text('instructions')->nullable();     // how to pay
            $table->json('details')->nullable();          // account no / wallet address fields
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
