<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nepal POS payment gateways (sandbox defaults; override via .env)
    |--------------------------------------------------------------------------
    */

    'esewa' => [
        'merchant_code' => env('ESEWA_MERCHANT_CODE', 'EPAYTEST'),
        'secret' => env('ESEWA_SECRET', '8gBm/:&EnhH.1/q'),
        'base_url' => env('ESEWA_BASE_URL', 'https://rc-epay.esewa.com.np'),
        // Status enquiry host differs from the form host in sandbox docs.
        'status_url' => env('ESEWA_STATUS_URL', 'https://rc.esewa.com.np/api/epay/transaction/status/'),
    ],

    'khalti' => [
        'secret_key' => env('KHALTI_SECRET_KEY'),
        'public_key' => env('KHALTI_PUBLIC_KEY'),
        'base_url' => env('KHALTI_BASE_URL', 'https://dev.khalti.com/api/v2'),
    ],

    'fonepay' => [
        'merchant_code' => env('FONEPAY_MERCHANT_CODE'),
        'secret' => env('FONEPAY_SECRET'),
        'username' => env('FONEPAY_USERNAME'),
        'password' => env('FONEPAY_PASSWORD'),
        'base_url' => env(
            'FONEPAY_BASE_URL',
            'https://merchantapi.fonepay.com/api/merchant/merchantDetailsForThirdParty'
        ),
    ],

];
