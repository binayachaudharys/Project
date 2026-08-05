<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Repositories\PackageRepository;
use Inertia\Inertia;
use Inertia\Response;

class PackageCatalogController extends Controller
{
    public function index(PackageRepository $packages): Response
    {
        return Inertia::render('Packages/Index', [
            'packages' => $packages->allActive(),
        ]);
    }

    public function show(Package $package): Response
    {
        $package->load('services:id,name,duration_minutes');

        return Inertia::render('Packages/Show', [
            'package' => $package->only(['id', 'name', 'description', 'price'])
                + ['services' => $package->services],
        ]);
    }
}
