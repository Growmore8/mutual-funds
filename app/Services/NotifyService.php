<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notify.lk SMS gateway.
 *
 * Credentials + recipient numbers + per-event toggles are managed from
 * Admin → Settings → SMS (stored in the `settings` table). config/notify.php
 * only provides fallbacks. Event SMS are sent AFTER the response (defer) so
 * they never slow down a client's deposit / withdrawal / KYC submission.
 */
class NotifyService
{
    private const SEND_URL = 'https://app.notify.lk/api/v1/send';

    private const BALANCE_URL = 'https://app.notify.lk/api/v1/get-balance';

    private function cfg(string $key, $default = null)
    {
        $val = Setting::get($key, null);

        return ($val === null || $val === '') ? $default : $val;
    }

    public function enabled(): bool
    {
        return (string) $this->cfg('notify_enabled', config('notify.enabled') ? '1' : '0') === '1';
    }

    /** Recipient phone numbers (digits only), split on comma / whitespace / newline. */
    public function numbers(): array
    {
        $raw = (string) $this->cfg('notify_numbers', '');

        return collect(preg_split('/[\s,]+/', $raw))
            ->map(fn ($n) => preg_replace('/\D/', '', (string) $n))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function creds(): array
    {
        return [
            'user_id' => $this->cfg('notify_user_id', config('notify.user_id')),
            'api_key' => $this->cfg('notify_api_key', config('notify.api_key')),
            'sender_id' => $this->cfg('notify_sender_id', config('notify.sender_id')),
        ];
    }

    public function configured(): bool
    {
        $c = $this->creds();

        return ! empty($c['user_id']) && ! empty($c['api_key']);
    }

    /** Send one SMS immediately. Never throws — returns the API response array. */
    public function send(string $phone, string $message): array
    {
        $c = $this->creds();
        $to = preg_replace('/\D/', '', $phone);

        try {
            $res = Http::asForm()->timeout(12)->post(self::SEND_URL, [
                'user_id' => $c['user_id'],
                'api_key' => $c['api_key'],
                'sender_id' => $c['sender_id'],
                'to' => $to,
                'message' => $message,
            ]);
            Log::info("Notify.lk SMS to {$to}: " . $res->body());
            $json = $res->json();

            return is_array($json) ? $json : ['status' => 'error', 'body' => $res->body()];
        } catch (\Throwable $e) {
            Log::warning('Notify.lk send failed: ' . $e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Fire an event SMS to every configured number, after the response is sent.
     * Respects the master toggle and the per-event toggle (notify_on_<event>).
     */
    public function event(string $event, string $message): void
    {
        if (! $this->enabled() || ! $this->configured()) {
            return;
        }
        if ((string) $this->cfg('notify_on_' . $event, '1') !== '1') {
            return;
        }
        $numbers = $this->numbers();
        if (empty($numbers)) {
            return;
        }

        // Send after the HTTP response so the client's request is never blocked.
        defer(function () use ($numbers, $message) {
            foreach ($numbers as $phone) {
                $this->send($phone, $message);
            }
        });
    }

    /** Remaining SMS balance from Notify.lk (null on failure / not configured). */
    public function balance(): ?array
    {
        if (! $this->configured()) {
            return null;
        }
        $c = $this->creds();

        try {
            $res = Http::asForm()->timeout(8)->get(self::BALANCE_URL, [
                'user_id' => $c['user_id'],
                'api_key' => $c['api_key'],
            ]);

            return $res->json();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
