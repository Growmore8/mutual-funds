<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');                          // deposit | withdrawal | profit | fee | adjustment
            $table->decimal('amount', 15, 2);                // +credit / -debit
            $table->string('currency', 10)->default('USD');
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->string('status')->default('completed');  // pending | completed | rejected
            $table->string('description')->nullable();
            $table->morphs('source');                        // optional link (deposit, pnl_allocation, ...)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
