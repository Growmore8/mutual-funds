<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $guarded = [];

    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever('setting:' . $key, function () use ($key, $default) {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            $val = static::where('key', $key)->value('value');

            return $val !== null ? $val : $default;
        });
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('setting:' . $key);
    }
}
