<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Create a notification for one user. */
    public static function notify(int $userId, string $type, string $title, ?string $body = null, ?string $url = null, ?string $icon = null): void
    {
        static::create(compact('type', 'title', 'body', 'url', 'icon') + ['user_id' => $userId]);
    }

    /** Notify every admin (e.g. a new client request). */
    public static function notifyAdmins(string $type, string $title, ?string $body = null, ?string $url = null, ?string $icon = null): void
    {
        foreach (User::where('role', 'admin')->pluck('id') as $id) {
            static::notify($id, $type, $title, $body, $url, $icon);
        }
    }
}
