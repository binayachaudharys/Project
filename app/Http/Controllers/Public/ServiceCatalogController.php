<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Repositories\ServiceRepository;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCatalogController extends Controller
{
    public function index(ServiceRepository $services): Response
    {
        return Inertia::render('Services/Index', [
            'services' => $services->allActive(),
        ]);
    }
}
