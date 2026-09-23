<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCatalogBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_lists_services(): void
    {
        Service::factory()->count(2)->create(['is_active' => true]);
        Service::factory()->create(['is_active' => false]);

        $this->getJson('/api/v1/services')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'description', 'duration_minutes', 'price'],
                ],
            ]);
    }

    public function test_api_lists_packages(): void
    {
        Package::factory()->count(2)->create(['is_active' => true]);
        Package::factory()->create(['is_active' => false]);

        $this->getJson('/api/v1/packages')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'description', 'price'],
                ],
            ]);
    }

    public function test_api_booking_requires_auth(): void
    {
        $this->postJson('/api/v1/bookings', [])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_booking(): void
    {
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['duration_minutes' => 60, 'is_active' => true]);
        $starts = now()->next('Wednesday')->setTime(11, 0);

        $this->actingAs($customer)
            ->postJson('/api/v1/bookings', [
                'bookable_type' => 'service',
                'bookable_id' => $service->id,
                'starts_at' => $starts->toIso8601String(),
                'staff_id' => null,
                'notes' => null,
            ])
            ->assertCreated()
            ->assertJsonPath('data.customer_id', $customer->id)
            ->assertJsonPath('data.bookable_type', 'service')
            ->assertJsonPath('data.bookable_id', $service->id)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'bookable_type' => 'service',
            'bookable_id' => $service->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_api_booking_validates_payload(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->postJson('/api/v1/bookings', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items', 'starts_at']);
    }
}
