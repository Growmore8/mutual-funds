<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->after('email');   // client | admin
            $table->string('phone')->nullable()->after('role');
            $table->string('country')->nullable()->after('phone');
            $table->foreignId('account_type_id')->nullable()->after('country')->constrained()->nullOnDelete();
            $table->string('status')->default('pending')->after('account_type_id'); // pending | active | suspended
            $table->string('kyc_status')->default('not_submitted')->after('status'); // not_submitted | submitted | approved | rejected
            $table->timestamp('otp_verified_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_type_id');
            $table->dropColumn(['role', 'phone', 'country', 'status', 'kyc_status', 'otp_verified_at']);
        });
    }
};
