<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Links a 'profit' transaction back to the PnL allocation that created it,
            // so deleting a PnL record can cleanly remove its client-side payouts.
            $table->foreignId('pnl_allocation_id')->nullable()->after('source_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pnl_allocation_id');
        });
    }
};
