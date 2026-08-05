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
        $appointment = $appointments->createBooking([
            ...$request->validated(),
            'customer_id' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $appointment,
        ], 201);
    }
}
