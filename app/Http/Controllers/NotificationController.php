<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** JSON feed for the bell dropdown + sound polling. */
    public function feed(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->appNotifications()->whereNull('read_at')->count(),
            'items' => $user->appNotifications()->limit(12)->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'body' => $n->body,
                    'url' => $n->url,
                    'icon' => $n->icon ?? self::iconFor($n->type),
                    'read' => $n->read_at !== null,
                    'ago' => $n->created_at->diffForHumans(),
                ]),
        ]);
    }

    public function markRead(Request $request)
    {
        $request->user()->appNotifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private static function iconFor(string $type): string
    {
        return [
            'deposit' => 'fa-arrow-down',
            'withdrawal' => 'fa-money-bill-transfer',
            'profit' => 'fa-chart-line',
            'kyc' => 'fa-id-card',
            'message' => 'fa-headset',
        ][$type] ?? 'fa-bell';
    }
}
