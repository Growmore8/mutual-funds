<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-client spot trading wallet (separate from the mutual-fund pool).
        Schema::create('spot_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->timestamps();
        });

        Schema::create('spot_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();          // null = house / liquidity maker
            $table->foreignId('instrument_id');
            $table->enum('side', ['buy', 'sell']);
            $table->enum('type', ['market', 'limit'])->default('limit');
            $table->decimal('price', 18, 6)->nullable();        // null for market
            $table->decimal('qty', 18, 6);
            $table->decimal('filled_qty', 18, 6)->default(0);
            $table->enum('status', ['open', 'partial', 'filled', 'cancelled'])->default('open');
            $table->boolean('is_maker')->default(false);
            $table->timestamps();
            $table->index(['instrument_id', 'side', 'status', 'price']);
        });

        Schema::create('spot_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id');
            $table->decimal('price', 18, 6);
            $table->decimal('qty', 18, 6);
            $table->foreignId('buyer_id')->nullable();
            $table->foreignId('seller_id')->nullable();
            $table->foreignId('buy_order_id')->nullable();
            $table->foreignId('sell_order_id')->nullable();
            $table->timestamps();
            $table->index(['instrument_id', 'created_at']);
        });

        Schema::create('spot_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('instrument_id');
            $table->decimal('qty', 18, 6)->default(0);
            $table->decimal('avg_price', 18, 6)->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'instrument_id']);
        });

        Schema::table('spot_instruments', function (Blueprint $table) {
            $table->decimal('last_price', 18, 6)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('spot_instruments', fn (Blueprint $t) => $t->dropColumn('last_price'));
        Schema::dropIfExists('spot_holdings');
        Schema::dropIfExists('spot_trades');
        Schema::dropIfExists('spot_orders');
        Schema::dropIfExists('spot_accounts');
    }
};
