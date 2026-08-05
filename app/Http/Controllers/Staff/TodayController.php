<?php

namespace App\Http\Controllers\Staff;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TodayController extends Controller
{
    /**
     * Today's non-cancelled appointments, in the salon's local timezone.
     */
    public function index(): Response
    {
        $today = Carbon::now('Asia/Kathmandu')->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $appointments = Appointment::query()
            ->whereBetween('starts_at', [$today, $tomorrow])
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->with(['customer:id,name,phone', 'staff:id,name'])
            ->orderBy('starts_at')
            ->get(['id', 'customer_id', 'staff_id', 'bookable_type', 'bookable_id', 'starts_at', 'ends_at', 'status']);

        return Inertia::render('Staff/Today', [
            'appointments' => $appointments,
        ]);
    }
}
