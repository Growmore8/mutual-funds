<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pnl_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_snapshot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('allocation_date');
            $table->decimal('eligible_capital', 15, 2)->default(0); // client's invested capital that day
            $table->decimal('weight', 12, 8)->default(0);           // share of the pool (capital x days)
            $table->decimal('gross_pnl', 15, 2)->default(0);        // before fees/share
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('net_pnl', 15, 2)->default(0);          // credited to the client
            $table->timestamps();
            $table->unique(['pool_snapshot_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pnl_allocations');
    }
};
