<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Repositories\AppointmentRepository;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function store(BookAppointmentRequest $request, AppointmentRepository $appointments): JsonResponse
    {
        $created = $appointments->createBookings([
            ...$request->validated(),
            'customer_id' => $request->user()->id,
        ]);

        return response()->json([
            'data' => count($created) === 1 ? $created[0] : $created,
        ], 201);
    }
}
