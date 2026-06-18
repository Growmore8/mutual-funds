<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid';

    protected $description = 'Generate VAPID keys for web push — add the printed lines to your .env.';

    public function handle(): int
    {
        if (! class_exists(\Minishlink\WebPush\VAPID::class)) {
            $this->error('Run: composer require minishlink/web-push   (then run this again).');

            return self::FAILURE;
        }

        $keys = \Minishlink\WebPush\VAPID::createVapidKeys();

        $this->info('Add these to your .env (keep the private key secret):');
        $this->line('');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:support@growthcapitalltd.com');
        $this->line('');
        $this->info('Then run: php artisan config:cache');

        return self::SUCCESS;
    }
}
