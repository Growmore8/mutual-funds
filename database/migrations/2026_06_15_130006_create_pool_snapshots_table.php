<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pool_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_account_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->decimal('closing_balance', 18, 2)->default(0);
            $table->decimal('pnl', 18, 2)->default(0);          // profit/loss for the day (from API)
            $table->decimal('pnl_pct', 8, 4)->default(0);
            $table->boolean('distributed')->default(false);     // has PnL been allocated to clients?
            $table->json('raw')->nullable();                    // raw API payload
            $table->timestamps();
            $table->unique(['pool_account_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_snapshots');
    }
};
