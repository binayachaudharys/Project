<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'description', 'duration_minutes', 'price']);

        return Inertia::render('Home', [
            'salon' => [
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
            'services' => $services,
        ]);
    }
}
