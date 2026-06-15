<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // e.g. Starter, Growth, Premium, Elite
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('min_deposit', 15, 2)->default(0);
            $table->decimal('max_deposit', 15, 2)->nullable();
            $table->decimal('management_fee_pct', 5, 2)->default(0); // % fee
            $table->decimal('profit_share_pct', 5, 2)->default(100); // client's share of PnL %
            $table->integer('lock_in_months')->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_types');
    }
};
