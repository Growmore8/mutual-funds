<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Sends Web Push notifications to a user's subscribed devices.
 * No-ops safely if the minishlink/web-push package or VAPID keys are missing,
 * so the app keeps working until push is fully configured on the server.
 */
class WebPushService
{
    public function sendToUser(int $userId, string $title, ?string $body = null, ?string $url = null): void
    {
        if (! class_exists(\Minishlink\WebPush\WebPush::class)) {
            return;
        }

        $public = config('services.webpush.public_key');
        $private = config('services.webpush.private_key');
        if (! $public || ! $private) {
            return;
        }

        $subs = PushSubscription::where('user_id', $userId)->get();
        if ($subs->isEmpty()) {
            return;
        }

        try {
            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'subject' => config('services.webpush.subject'),
                    'publicKey' => $public,
                    'privateKey' => $private,
                ],
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body ?? '',
                'url' => $url ?: url('/app'),
            ]);

            foreach ($subs as $s) {
                $subscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $s->endpoint,
                    'publicKey' => $s->p256dh,
                    'authToken' => $s->auth,
                ]);
                $webPush->queueNotification($subscription, $payload);
            }

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess() && $report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('WebPush send failed: ' . $e->getMessage());
        }
    }
}
