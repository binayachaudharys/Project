<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'payment_initiate' => $request->session()->get('payment_initiate'),
                'pending_sale_id' => $request->session()->get('pending_sale_id'),
            ],
            'salonContact' => [
                'name' => config('salon.name'),
                'tagline' => config('salon.tagline'),
                'address' => config('salon.address'),
                'phone' => config('salon.phone'),
                'whatsapp' => config('salon.whatsapp'),
                'instagram' => config('salon.instagram'),
                'facebook' => config('salon.facebook'),
                'tiktok' => config('salon.tiktok'),
                'promo' => config('salon.promo'),
                'logo' => config('salon.logo'),
                'open' => config('salon.salon_open'),
                'close' => config('salon.salon_close'),
                'menu' => config('salon.menu'),
                'location' => config('salon.location'),
                'currency' => config('salon.currency', 'Rs'),
            ],
        ];
    }
}
