<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('announcements')->where('title', 'Refer & Earn')->exists()) {
            DB::table('announcements')->insert([
                'type' => 'promotion',
                'title' => 'Refer & Earn',
                'body' => "Invite your friends and earn 1% of every deposit they make — for life!\n\nShare your referral link and start earning.",
                'cta_label' => 'Invite & Earn',
                'cta_url' => '/referrals',
                'frequency' => 'daily',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('announcements')->where('title', 'Refer & Earn')->where('cta_url', '/referrals')->delete();
    }
};
