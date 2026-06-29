<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Index the hot columns so the open-orders query stops scanning the whole (bloated)
     * spot_orders table. The bloat itself is cleared automatically by the updated seeder
     * (it now DELETEs house maker orders each cycle instead of leaving them cancelled).
     * Index-only + idempotent so it can't deadlock with the live price cron.
     */
    public function up(): void
    {
        $existing = collect(DB::select('SHOW INDEX FROM spot_orders'))->pluck('Key_name')->unique();

        if (! $existing->contains('spot_orders_user_status_idx')) {
            DB::statement('CREATE INDEX spot_orders_user_status_idx ON spot_orders (user_id, status)');
        }
        if (! $existing->contains('spot_orders_inst_maker_idx')) {
            DB::statement('CREATE INDEX spot_orders_inst_maker_idx ON spot_orders (instrument_id, is_maker)');
        }
        if (! $existing->contains('spot_orders_inst_side_status_idx')) {
            DB::statement('CREATE INDEX spot_orders_inst_side_status_idx ON spot_orders (instrument_id, side, status)');
        }
    }

    public function down(): void
    {
        foreach (['spot_orders_user_status_idx', 'spot_orders_inst_maker_idx', 'spot_orders_inst_side_status_idx'] as $idx) {
            try {
                DB::statement("DROP INDEX {$idx} ON spot_orders");
            } catch (\Throwable $e) {
            }
        }
    }
};
