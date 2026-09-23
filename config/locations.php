<?php

/**
 * Location brand profiles. Activated via SALON_LOCATION in .env.
 * Env keys (SALON_NAME, SALON_PHONE, …) always win over these defaults.
 */
return [

    'nepal' => [
        'name' => 'Pretty Salon Nepal',
        'tagline' => 'A whole new you',
        'address' => 'Shankhamul Pool, 3rd Floor (NIMB Building)',
        'phone' => '+977-9822783805',
        'whatsapp' => '9779822783805',
        'instagram' => 'https://www.instagram.com/prettysalonnepal/',
        'facebook' => 'https://www.facebook.com/people/Pretty-Salon-Nepal/61584002914099/',
        'tiktok' => 'https://www.tiktok.com/@prettysalonnepal',
        'promo' => 'Grand opening offer — GET 30% OFF for the first month',
        'currency' => 'Rs',
        'timezone' => 'Asia/Kathmandu',
    ],

    'abudhabi' => [
        'name' => 'Pretty Salon Abu Dhabi',
        'tagline' => 'A whole new you',
        'address' => 'Abu Dhabi, UAE — update street address in .env',
        'phone' => '',
        'whatsapp' => '',
        'instagram' => '',
        'facebook' => '',
        'tiktok' => 'https://www.tiktok.com/@prettysalon_abudhabi',
        'promo' => 'Welcome to Pretty Salon Abu Dhabi',
        'currency' => 'AED',
        'timezone' => 'Asia/Dubai',
    ],

];
