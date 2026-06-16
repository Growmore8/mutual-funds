<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('info');   // deposit | withdrawal | profit | kyc | message | info
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'read_at']);
        });

        // deposits already has proof_path (slip) + admin_note; just add method + note.
        Schema::table('deposits', function (Blueprint $table) {
            $table->string('method')->nullable()->after('amount');
            $table->text('note')->nullable()->after('reference');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('network')->nullable()->after('type');   // BEP20 | ERC20 | TRC20 | ...
            $table->string('address')->nullable()->after('currency'); // wallet / account / UPI id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::table('deposits', fn (Blueprint $t) => $t->dropColumn(['method', 'note']));
        Schema::table('payment_methods', fn (Blueprint $t) => $t->dropColumn(['network', 'address']));
    }
};
