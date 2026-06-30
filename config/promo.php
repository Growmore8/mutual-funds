<?php

return [
    /*
    | FIFA World Cup 2026 promo. Everything tied to this auto-hides after `until`
    | (the day after the final) — no manual cleanup needed. Symbols must exist as
    | enabled spot instruments to appear in the watchlist.
    */
    'worldcup' => [
        'enabled' => env('PROMO_WORLDCUP', true),
        'until' => env('PROMO_WORLDCUP_UNTIL', '2026-07-20'),   // final is ~19 Jul 2026
        'symbols' => ['KO', 'PEP', 'V', 'MA', 'MCD', 'NKE', 'DIS', 'SBUX', 'WMT', 'AMZN'],
    ],
];
