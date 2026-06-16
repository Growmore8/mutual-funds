<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Assigned live/pool account ("live ID") for the client.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('pool_account_id')->nullable()->after('account_type_id')
                ->constrained()->nullOnDelete();
        });

        // Requests for an additional account (1st account is free at registration).
        Schema::create('account_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_type_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');   // pending | approved | rejected
            $table->string('reason')->nullable();
            $table->string('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_requests');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pool_account_id');
        });
    }
};
