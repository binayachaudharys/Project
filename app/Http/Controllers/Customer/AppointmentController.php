<?php

namespace App\Http\Controllers\Customer;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\PackageRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function create(ServiceRepository $services, PackageRepository $packages): Response
    {
        return Inertia::render('Book/Create', [
            'services' => $services->allActive(),
            'packages' => $packages->allActive(),
        ]);
    }

    public function store(BookAppointmentRequest $request, AppointmentRepository $appointments): RedirectResponse
    {
        $appointments->createBooking([
            ...$request->validated(),
            'customer_id' => $request->user()->id,
        ]);

        return redirect()->route('account.appointments')->with('success', 'Appointment booked.');
    }

    public function index(Request $request): Response
    {
        $appointments = $request->user()
            ->appointmentsAsCustomer()
            ->with('staff:id,name')
            ->orderByDesc('starts_at')
            ->get(['id', 'staff_id', 'bookable_type', 'bookable_id', 'starts_at', 'ends_at', 'status']);

        return Inertia::render('Account/Appointments', [
            'appointments' => $appointments,
        ]);
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentRepository $appointments): RedirectResponse
    {
        abort_unless($appointment->customer_id === $request->user()->id, 403);

        if ($appointment->status === AppointmentStatus::Cancelled || $appointment->starts_at->isPast()) {
            return back()->withErrors(['appointment' => 'This appointment can no longer be cancelled.']);
        }

        $appointments->cancel($appointment);

        return back()->with('success', 'Appointment cancelled.');
    }
}
