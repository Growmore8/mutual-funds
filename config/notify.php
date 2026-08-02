<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notify.lk SMS
    |--------------------------------------------------------------------------
    | These are only FALLBACKS. The live values are managed from the admin UI
    | (Admin → Settings → SMS) and stored in the `settings` table, so no API
    | key needs to live in the repo. Leave these empty and configure in admin.
    */

    'enabled'   => env('NOTIFY_ENABLED', false),
    'user_id'   => env('NOTIFY_USER_ID'),
    'api_key'   => env('NOTIFY_API_KEY'),
    'sender_id' => env('NOTIFY_SENDER_ID', 'NotifyDEMO'),

];
