<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Repositories\AppointmentRepository;
use App\Repositories\SaleRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $date = $request->query('date');

        $appointments = Appointment::query()
            ->with([
                'customer:id,name,phone',
                'staff:id,name',
                'bookable',
                'sale:id,appointment_id,sale_number,status,total',
            ])
            ->when(
                $status && in_array($status, array_column(AppointmentStatus::cases(), 'value'), true),
                fn ($q) => $q->where('status', $status)
            )
            ->when($date, fn ($q) => $q->whereDate('starts_at', $date))
            ->orderByDesc('starts_at')
            ->limit(100)
            ->get();

        return Inertia::render('Admin/Bookings/Index', [
            'appointments' => $appointments,
            'filters' => [
                'status' => $status,
                'date' => $date,
            ],
        ]);
    }

    public function updateStatus(
        Request $request,
        Appointment $appointment,
        AppointmentRepository $appointments,
        SaleRepository $sales,
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
        ]);

        try {
            $status = AppointmentStatus::from($validated['status']);
            $appointments->updateStatus($appointment, $status);

            if ($status === AppointmentStatus::Confirmed) {
                $sales->createBillingFromAppointment($appointment->fresh(), $request->user());
            }
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $message = match ($status) {
            AppointmentStatus::Confirmed => 'Booking confirmed and added to billing.',
            AppointmentStatus::Pending => 'Booking set to pending.',
            AppointmentStatus::Cancelled => 'Booking cancelled.',
            AppointmentStatus::Completed => 'Booking marked completed.',
        };

        return back()->with('success', $message);
    }
}
