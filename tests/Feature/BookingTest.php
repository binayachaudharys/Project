<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Package;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_customer_can_book_service_without_payment(): void
    {
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['duration_minutes' => 60, 'is_active' => true]);
        $starts = now()->next('Wednesday')->setTime(11, 0);

        $this->actingAs($customer)
            ->post(route('book.store'), [
                'bookable_type' => 'service',
                'bookable_id' => $service->id,
                'starts_at' => $starts->toIso8601String(),
                'staff_id' => null,
                'notes' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'bookable_type' => 'service',
            'bookable_id' => $service->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_customer_can_book_package_using_configured_duration(): void
    {
        Setting::create(['key' => 'package_duration', 'value' => '90']);

        $customer = User::factory()->customer()->create();
        $package = Package::factory()->create(['is_active' => true]);
        $starts = now()->next('Monday')->setTime(10, 0);

        $this->actingAs($customer)
            ->post(route('book.store'), [
                'bookable_type' => 'package',
                'bookable_id' => $package->id,
                'starts_at' => $starts->toIso8601String(),
            ])
            ->assertRedirect(route('account.appointments'));

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'bookable_type' => 'package',
            'bookable_id' => $package->id,
            'ends_at' => $starts->copy()->addMinutes(90)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_booking_rejects_staff_overlap(): void
    {
        $customer = User::factory()->customer()->create();
        $staff = User::factory()->staff()->create();
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $starts = now()->next('Thursday')->setTime(12, 0);

        Appointment::factory()->create([
            'staff_id' => $staff->id,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->actingAs($customer)
            ->post(route('book.store'), [
                'bookable_type' => 'service',
                'bookable_id' => $service->id,
                'starts_at' => $starts->toIso8601String(),
                'staff_id' => $staff->id,
            ])
            ->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_rejects_outside_salon_hours(): void
    {
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $starts = now()->next('Friday')->setTime(20, 0);

        $this->actingAs($customer)
            ->post(route('book.store'), [
                'bookable_type' => 'service',
                'bookable_id' => $service->id,
                'starts_at' => $starts->toIso8601String(),
            ])
            ->assertSessionHasErrors('starts_at');
    }

    public function test_booking_respects_max_concurrent_capacity_without_staff(): void
    {
        Setting::create(['key' => 'max_concurrent', 'value' => '1']);

        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['duration_minutes' => 60]);
        $starts = now()->next('Saturday')->setTime(13, 0);

        Appointment::factory()->create([
            'staff_id' => null,
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->addHour(),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->actingAs($customer)
            ->post(route('book.store'), [
                'bookable_type' => 'service',
                'bookable_id' => $service->id,
                'starts_at' => $starts->toIso8601String(),
            ])
            ->assertSessionHasErrors('starts_at');
    }

    public function test_booking_rejects_inactive_service(): void
    {
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['is_active' => false]);
        $starts = now()->next('Tuesday')->setTime(11, 0);

        $this->actingAs($customer)
            ->post(route('book.store'), [
                'bookable_type' => 'service',
                'bookable_id' => $service->id,
                'starts_at' => $starts->toIso8601String(),
            ])
            ->assertSessionHasErrors('bookable_id');
    }

    public function test_guest_cannot_book(): void
    {
        $this->post(route('book.store'), [])->assertRedirect(route('login'));
    }

    public function test_customer_can_view_own_appointments(): void
    {
        $customer = User::factory()->customer()->create();
        Appointment::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer)
            ->get(route('account.appointments'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Account/Appointments')
                ->has('appointments', 1)
            );
    }

    public function test_customer_can_cancel_future_appointment(): void
    {
        $customer = User::factory()->customer()->create();
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->actingAs($customer)
            ->delete(route('account.appointments.cancel', $appointment))
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_customer_cannot_cancel_another_customers_appointment(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $appointment = Appointment::factory()->create(['customer_id' => $other->id]);

        $this->actingAs($customer)
            ->delete(route('account.appointments.cancel', $appointment))
            ->assertForbidden();
    }

    public function test_staff_can_view_todays_appointments(): void
    {
        $staff = User::factory()->staff()->create();
        $todayAppointment = Appointment::factory()->create([
            'starts_at' => now()->setTime(14, 0),
            'ends_at' => now()->setTime(14, 30),
        ]);
        Appointment::factory()->create([
            'starts_at' => now()->addDays(3)->setTime(14, 0),
            'ends_at' => now()->addDays(3)->setTime(14, 30),
        ]);

        $this->actingAs($staff)
            ->get(route('staff.today'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Staff/Today')
                ->has('appointments', 1)
                ->where('appointments.0.id', $todayAppointment->id)
            );
    }
}
