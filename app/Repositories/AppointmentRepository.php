<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\BookableType;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Jsdecena\Baserepo\BaseRepository;

class AppointmentRepository extends BaseRepository
{
    public function __construct(Appointment $model)
    {
        parent::__construct($model);
    }

    /**
     * Whether a staff member already has a non-cancelled appointment
     * overlapping the given window.
     *
     * `$lockForUpdate` should only be set when called inside the
     * `createBooking` transaction, to serialize concurrent booking
     * attempts for the same staff member/slot.
     */
    public function hasStaffConflict(int $staffId, Carbon $start, Carbon $end, ?int $ignoreId = null, bool $lockForUpdate = false): bool
    {
        return $this->model->newQuery()
            ->where('staff_id', $staffId)
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($lockForUpdate, fn ($q) => $q->lockForUpdate())
            ->exists();
    }

    /**
     * Create a customer booking with overlap and capacity validation.
     */
    public function createBooking(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $start = Carbon::parse($data['starts_at']);
            $duration = $this->resolveDuration((string) $data['bookable_type'], (int) $data['bookable_id']);
            $end = $start->copy()->addMinutes($duration);

            $this->assertWithinSalonHours($start, $end);

            if (! empty($data['staff_id']) && $this->hasStaffConflict((int) $data['staff_id'], $start, $end, lockForUpdate: true)) {
                throw ValidationException::withMessages([
                    'starts_at' => 'That staff member is already booked for this time.',
                ]);
            }

            if (empty($data['staff_id']) && $this->exceedsSalonCapacity($start, $end)) {
                throw ValidationException::withMessages([
                    'starts_at' => 'No chairs available for this time.',
                ]);
            }

            $autoConfirm = Setting::resolveBool('auto_confirm', true);

            return $this->create([
                'customer_id' => $data['customer_id'],
                'staff_id' => $data['staff_id'] ?? null,
                'bookable_type' => $data['bookable_type'],
                'bookable_id' => $data['bookable_id'],
                'starts_at' => $start,
                'ends_at' => $end,
                'status' => $autoConfirm ? AppointmentStatus::Confirmed : AppointmentStatus::Pending,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Cancel an appointment, enforcing eligibility: it must not already be
     * cancelled and must start in the future.
     */
    public function cancel(Appointment $appointment): Appointment
    {
        if ($appointment->status === AppointmentStatus::Cancelled) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment has already been cancelled.',
            ]);
        }

        if ($appointment->starts_at->isPast()) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment can no longer be cancelled.',
            ]);
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        return $appointment->fresh();
    }

    /**
     * Duration in minutes for the given bookable (service duration, or the
     * configured flat package duration).
     */
    protected function resolveDuration(string $bookableType, int $bookableId): int
    {
        if ($bookableType === BookableType::Package->value) {
            return (int) Setting::resolve('package_duration', 60);
        }

        return (int) Service::query()->findOrFail($bookableId)->duration_minutes;
    }

    protected function assertWithinSalonHours(Carbon $start, Carbon $end): void
    {
        $open = (string) Setting::resolve('salon_open', '10:00');
        $close = (string) Setting::resolve('salon_close', '19:00');

        $dayOpen = $start->copy()->setTimeFromTimeString($open);
        $dayClose = $start->copy()->setTimeFromTimeString($close);

        if ($start->lt($dayOpen) || $end->gt($dayClose)) {
            throw ValidationException::withMessages([
                'starts_at' => 'Please choose a time within salon hours.',
            ]);
        }
    }

    /**
     * When no staff is chosen, cap the number of concurrent unstaffed
     * appointments overlapping the slot (i.e. available chairs).
     *
     * Only called from within the `createBooking` transaction, so the
     * overlapping rows are locked to serialize concurrent capacity checks.
     */
    protected function exceedsSalonCapacity(Carbon $start, Carbon $end): bool
    {
        $max = (int) Setting::resolve('max_concurrent', 1);

        $overlapping = $this->model->newQuery()
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->lockForUpdate()
            ->count();

        return $overlapping >= $max;
    }
}
