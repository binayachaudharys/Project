<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\PackageRepository;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function index(PackageRepository $packages): JsonResponse
    {
        return response()->json([
            'data' => $packages->allActive(),
        ]);
    }
}
