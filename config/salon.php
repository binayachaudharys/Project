<?php

$location = env('SALON_LOCATION', 'nepal');
$profiles = require __DIR__.'/locations.php';
$profile = $profiles[$location] ?? ($profiles['nepal'] ?? []);

return [

    /*
    |--------------------------------------------------------------------------
    | Active location key
    |--------------------------------------------------------------------------
    |
    | Deploy one codebase for Nepal or Abu Dhabi by setting SALON_LOCATION.
    | Defaults come from config/locations.php; any SALON_* env key overrides.
    |
    */

    'location' => $location,

    'name' => env('SALON_NAME', $profile['name'] ?? 'Pretty Salon Nepal'),

    'salon_open' => env('SALON_OPEN', '10:00'),
    'salon_close' => env('SALON_CLOSE', '19:00'),
    'slot_minutes' => (int) env('SALON_SLOT_MINUTES', 30),
    'package_duration' => (int) env('SALON_PACKAGE_DURATION', 60),
    'auto_confirm' => env('SALON_AUTO_CONFIRM', true),
    'max_concurrent' => (int) env('SALON_MAX_CONCURRENT', 1),

    'tagline' => env('SALON_TAGLINE', $profile['tagline'] ?? 'A whole new you'),
    'address' => env('SALON_ADDRESS', $profile['address'] ?? 'Shankhamul Pool, 3rd Floor (NIMB Building)'),
    'phone' => env('SALON_PHONE', $profile['phone'] ?? '+977-9822783805'),
    'whatsapp' => env('SALON_WHATSAPP', $profile['whatsapp'] ?? '9779822783805'),
    'instagram' => env('SALON_INSTAGRAM', $profile['instagram'] ?? 'https://www.instagram.com/prettysalonnepal/'),
    'facebook' => env('SALON_FACEBOOK', $profile['facebook'] ?? 'https://www.facebook.com/people/Pretty-Salon-Nepal/61584002914099/'),
    'tiktok' => env('SALON_TIKTOK', $profile['tiktok'] ?? 'https://www.tiktok.com/@prettysalonnepal'),
    'promo' => env('SALON_PROMO', $profile['promo'] ?? 'Grand opening offer — GET 30% OFF for the first month'),
    'logo' => env('SALON_LOGO', '/images/logo.svg'),
    'currency' => env('SALON_CURRENCY', $profile['currency'] ?? 'Rs'),

    /*
    |--------------------------------------------------------------------------
    | Flyer menu (Pretty Salon Nepal price list)
    |--------------------------------------------------------------------------
    */
    'menu' => [
        [
            'title' => 'Hair Treatments',
            'badge' => '30% OFF',
            'items' => [
                ['name' => 'Keratin Treatment', 'price' => '4,500'],
                ['name' => 'Hair Botox', 'price' => '5,600'],
                ['name' => 'Nanoplastia', 'price' => '6,900'],
                ['name' => 'Cysteine Treatment', 'price' => '9,900'],
                ['name' => 'Hair Full Service', 'price' => '30% OFF'],
            ],
        ],
        [
            'title' => 'Hair Services',
            'items' => [
                ['name' => 'Hair Cut', 'price' => '500'],
                ['name' => 'Hot Oil Massage', 'price' => '3,500'],
                ['name' => 'Hair Spa & Blow Dry', 'price' => '4,500'],
                ['name' => 'Full Hair Colour', 'price' => '3,900–4,900+'],
            ],
            'note' => 'Colour price depends on hair length and colour.',
        ],
        [
            'title' => 'Facial Treatments',
            'items' => [
                ['name' => 'Hydra Facial', 'price' => '4,500'],
                ['name' => 'Carbon Facial', 'price' => '5,600'],
                ['name' => 'BB Glow Facial', 'price' => '4,900'],
                ['name' => 'HIFU Treatment', 'price' => '30% OFF'],
            ],
        ],
        [
            'title' => 'Eyes & Brows',
            'items' => [
                ['name' => 'Eyelash Extensions', 'price' => '4,500'],
                ['name' => 'Eyebrow Shaping', 'price' => '70'],
            ],
        ],
        [
            'title' => 'Body Care',
            'items' => [
                ['name' => 'Full Body Waxing', 'price' => '5,900'],
                ['name' => 'Body Laser', 'price' => 'Consult'],
                ['name' => 'Tattoo Removal', 'price' => '5,600'],
            ],
        ],
        [
            'title' => 'Nail Care',
            'items' => [
                ['name' => 'Nail Extensions', 'price' => '2,500'],
                ['name' => 'Manicure & Pedicure', 'price' => '4,000'],
                ['name' => 'Paraffin Treatment', 'price' => '1,000'],
            ],
        ],
        [
            'title' => 'Laser Hair Removal',
            'badge' => 'Launch',
            'items' => [
                ['name' => 'Underarms', 'price' => '3,000'],
                ['name' => 'Full Face', 'price' => '5,000'],
                ['name' => 'Upper Lips / Side Burns', 'price' => '1,000'],
                ['name' => 'Hand', 'price' => '5,500'],
                ['name' => 'Leg', 'price' => '7,500'],
                ['name' => 'Laser Full Body', 'price' => '22,000'],
            ],
        ],
        [
            'title' => 'Cold HIFU',
            'items' => [
                ['name' => 'Face', 'price' => '5,000'],
                ['name' => 'Neck', 'price' => '4,000'],
                ['name' => 'Abdomen', 'price' => '4,500'],
                ['name' => 'Both Arms', 'price' => '4,000'],
            ],
        ],
    ],
];
