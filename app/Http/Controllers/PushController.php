<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['nullable', 'string'],
            'keys.auth' => ['nullable', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'p256dh' => $data['keys']['p256dh'] ?? null,
                'auth' => $data['keys']['auth'] ?? null,
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $endpoint = $request->input('endpoint');
        if ($endpoint) {
            PushSubscription::where('user_id', $request->user()->id)->where('endpoint', $endpoint)->delete();
        }

        return response()->json(['ok' => true]);
    }
}
