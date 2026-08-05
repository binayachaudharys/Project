<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Salon operating defaults
    |--------------------------------------------------------------------------
    |
    | These act as env-driven fallbacks whenever a matching row is missing
    | from the `settings` table. Keys intentionally match Setting::key so
    | Setting::resolve() can look up either source with one call.
    |
    */

    'name' => env('SALON_NAME', 'Pretty Girls Ladies Salon'),

    'salon_open' => env('SALON_OPEN', '10:00'),
    'salon_close' => env('SALON_CLOSE', '19:00'),
    'slot_minutes' => (int) env('SALON_SLOT_MINUTES', 30),
    'package_duration' => (int) env('SALON_PACKAGE_DURATION', 60),
    'auto_confirm' => env('SALON_AUTO_CONFIRM', true),
    'max_concurrent' => (int) env('SALON_MAX_CONCURRENT', 1),
];
