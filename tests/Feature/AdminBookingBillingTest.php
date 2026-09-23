<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\SaleStatus;
use App\Models\Appointment;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBookingBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Setting::query()->updateOrCreate(
            ['key' => 'vat_enabled'],
            ['value' => '0'],
        );
    }

    public function test_owner_can_confirm_booking_and_create_billing(): void
    {
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['price' => 1500, 'is_active' => true]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => 'service',
            'bookable_id' => $service->id,
            'status' => AppointmentStatus::Pending,
            'starts_at' => now()->addDay()->setTime(11, 0),
            'ends_at' => now()->addDay()->setTime(12, 0),
        ]);

        $this->actingAs($owner)
            ->post(route('admin.bookings.status', $appointment), [
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertDatabaseHas('sales', [
            'appointment_id' => $appointment->id,
            'customer_id' => $customer->id,
            'status' => SaleStatus::PendingPayment->value,
            'total' => 1500,
        ]);
    }

    public function test_owner_can_set_pending_and_cancel(): void
    {
        $owner = User::factory()->owner()->create();
        $appointment = Appointment::factory()->create([
            'status' => AppointmentStatus::Confirmed,
            'starts_at' => now()->addDay()->setTime(11, 0),
            'ends_at' => now()->addDay()->setTime(12, 0),
        ]);

        $this->actingAs($owner)
            ->post(route('admin.bookings.status', $appointment), [
                'status' => 'pending',
            ])
            ->assertRedirect();

        $this->assertSame(AppointmentStatus::Pending, $appointment->fresh()->status);

        $this->actingAs($owner)
            ->post(route('admin.bookings.status', $appointment), [
                'status' => 'cancelled',
            ])
            ->assertRedirect();

        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
    }

    public function test_staff_can_mark_billing_paid(): void
    {
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['price' => 900, 'is_active' => true]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => 'service',
            'bookable_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'starts_at' => now()->addDay()->setTime(11, 0),
            'ends_at' => now()->addDay()->setTime(12, 0),
        ]);

        $sale = app(\App\Repositories\SaleRepository::class)
            ->createBillingFromAppointment($appointment, $staff);

        $this->actingAs($staff)
            ->post(route('staff.billing.mark-paid', $sale))
            ->assertRedirect();

        $this->assertSame(SaleStatus::Paid, $sale->fresh()->status);
        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_owner_bookings_index_ok(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Bookings/Index'));
    }

    public function test_owner_can_view_printable_invoice(): void
    {
        $owner = User::factory()->owner()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['price' => 900, 'is_active' => true]);

        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => 'service',
            'bookable_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
            'starts_at' => now()->addDay()->setTime(11, 0),
            'ends_at' => now()->addDay()->setTime(12, 0),
        ]);

        $sale = app(\App\Repositories\SaleRepository::class)
            ->createBillingFromAppointment($appointment, $owner);

        $this->actingAs($owner)
            ->get(route('admin.billing.invoice', $sale))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Billing/Invoice')
                ->has('sale.sale_number')
                ->where('sale.id', $sale->id));
    }
}
