<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pool_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_ref')->unique();      // e.g. 800120 (on the trading server)
            $table->string('name')->nullable();
            $table->decimal('capacity', 18, 2)->default(0);   // e.g. 10000
            $table->decimal('balance', 18, 2)->default(0);    // latest balance from API
            $table->decimal('equity', 18, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pool_accounts');
    }
};
