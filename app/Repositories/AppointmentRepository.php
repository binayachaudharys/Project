<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\BookableType;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
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
     * Update appointment status with simple transition rules for desk staff.
     */
    public function updateStatus(Appointment $appointment, AppointmentStatus $status): Appointment
    {
        if ($appointment->status === AppointmentStatus::Completed) {
            throw ValidationException::withMessages([
                'status' => 'Completed appointments cannot change status.',
            ]);
        }

        if ($appointment->status === AppointmentStatus::Cancelled && $status !== AppointmentStatus::Cancelled) {
            throw ValidationException::withMessages([
                'status' => 'Cancelled appointments cannot be reopened.',
            ]);
        }

        $appointment->update(['status' => $status]);

        return $appointment->fresh();
    }

    /**
     * Whether a staff member already has a non-cancelled appointment
     * overlapping the given window.
     */
    public function hasStaffConflict(int $staffId, Carbon $start, Carbon $end, ?int $ignoreId = null): bool
    {
        return $this->model->newQuery()
            ->where('staff_id', $staffId)
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->exists();
    }

    /**
     * Create one or more customer bookings.
     *
     * Multiple items are scheduled back-to-back starting at starts_at.
     *
     * @param  array{customer_id:int, items:list<array{bookable_type:string, bookable_id:int}>, starts_at:mixed, staff_id?:int|null, notes?:string|null}  $data
     * @return list<Appointment>
     */
    public function createBookings(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $cursor = Carbon::parse($data['starts_at']);
            $created = [];

            foreach ($data['items'] as $item) {
                $created[] = $this->createBooking([
                    'customer_id' => $data['customer_id'],
                    'staff_id' => $data['staff_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'bookable_type' => $item['bookable_type'],
                    'bookable_id' => $item['bookable_id'],
                    'starts_at' => $cursor->toDateTimeString(),
                ]);

                $cursor = $created[array_key_last($created)]->ends_at->copy();
            }

            return $created;
        });
    }

    /**
     * Create a customer booking with overlap and capacity validation.
     *
     * Concurrent empty-slot races are serialized by locking a stable row
     * before conflict checks: the staff user when assigned, otherwise the
     * `max_concurrent` settings row (salon capacity mutex).
     */
    public function createBooking(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $start = Carbon::parse($data['starts_at']);
            $duration = $this->resolveDuration((string) $data['bookable_type'], (int) $data['bookable_id']);
            $end = $start->copy()->addMinutes($duration);

            $this->assertWithinSalonHours($start, $end);

            if (! empty($data['staff_id'])) {
                // Empty lockForUpdate result sets do not block peers; hold the
                // staff user row so concurrent bookings for the same stylist
                // serialize even when no overlapping appointments exist yet.
                User::query()->whereKey((int) $data['staff_id'])->lockForUpdate()->firstOrFail();

                if ($this->hasStaffConflict((int) $data['staff_id'], $start, $end)) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'That staff member is already booked for this time.',
                    ]);
                }
            } else {
                $this->lockSalonCapacitySetting();

                if ($this->exceedsSalonCapacity($start, $end)) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'No chairs available for this time.',
                    ]);
                }
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
     * Ensure a lockable `max_concurrent` settings row and hold it for the
     * rest of the booking transaction (capacity serialization).
     */
    protected function lockSalonCapacitySetting(): void
    {
        $setting = Setting::query()
            ->where('key', 'max_concurrent')
            ->lockForUpdate()
            ->first();

        if ($setting !== null) {
            return;
        }

        Setting::query()->firstOrCreate(
            ['key' => 'max_concurrent'],
            ['value' => (string) config('salon.max_concurrent', 1)],
        );

        Setting::query()
            ->where('key', 'max_concurrent')
            ->lockForUpdate()
            ->firstOrFail();
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
     * Callers must already hold {@see lockSalonCapacitySetting()} so
     * concurrent empty-slot bookings cannot both pass this check.
     */
    protected function exceedsSalonCapacity(Carbon $start, Carbon $end): bool
    {
        $max = (int) Setting::resolve('max_concurrent', 1);

        $overlapping = $this->model->newQuery()
            ->whereNotIn('status', [AppointmentStatus::Cancelled->value])
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->count();

        return $overlapping >= $max;
    }
}
