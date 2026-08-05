<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\BookableType;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('+1 day', '+2 weeks');
        $ends = (clone $starts)->modify('+30 minutes');

        return [
            'customer_id' => User::factory()->customer(),
            'staff_id' => null,
            'bookable_type' => BookableType::Service,
            'bookable_id' => Service::factory(),
            'starts_at' => $starts,
            'ends_at' => $ends,
            'status' => AppointmentStatus::Confirmed,
            'notes' => null,
        ];
    }
}
