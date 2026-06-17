<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');               // crypto | bank | upi
            $table->string('label')->nullable();  // short display label
            $table->json('details')->nullable();  // type-specific fields
            $table->timestamps();
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->foreignId('withdrawal_method_id')->nullable()->after('method')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_method_id');
        });
        Schema::dropIfExists('withdrawal_methods');
    }
};
