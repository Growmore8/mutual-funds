<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('address')->nullable()->after('country');
        });

        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->string('front_path')->nullable()->after('document_number');
            $table->string('back_path')->nullable()->after('front_path');
            $table->string('file_path')->nullable()->change();
            $table->string('doc_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('address');
        });

        Schema::table('kyc_documents', function (Blueprint $table) {
            $table->dropColumn(['front_path', 'back_path']);
        });
    }
};
