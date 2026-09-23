<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookAppointmentRequest;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\PackageRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $created = $appointments->createBookings([
            ...$request->validated(),
            'customer_id' => $request->user()->id,
        ]);

        $count = count($created);
        $message = $count === 1
            ? 'Appointment booked.'
            : "{$count} appointments booked back-to-back.";

        return redirect()->route('account.appointments')->with('success', $message);
    }

    public function index(Request $request): Response
    {
        $appointments = $request->user()
            ->appointmentsAsCustomer()
            ->with(['staff:id,name', 'bookable'])
            ->orderByDesc('starts_at')
            ->get(['id', 'staff_id', 'bookable_type', 'bookable_id', 'starts_at', 'ends_at', 'status']);

        return Inertia::render('Account/Appointments', [
            'appointments' => $appointments,
        ]);
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentRepository $appointments): RedirectResponse
    {
        abort_unless($appointment->customer_id === $request->user()->id, 403);

        try {
            $appointments->cancel($appointment);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Appointment cancelled.');
    }
}
