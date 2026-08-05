<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\ServiceRepository;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(ServiceRepository $services): JsonResponse
    {
        return response()->json([
            'data' => $services->allActive(),
        ]);
    }
}
