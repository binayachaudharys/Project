<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Package;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_owner_can_create_service(): void
    {
        $owner = User::factory()->owner()->create();
        $this->actingAs($owner)->post(route('admin.services.store'), [
            'name' => 'Facial',
            'description' => 'Basic facial',
            'duration_minutes' => 40,
            'price' => 1200,
            'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('services', ['name' => 'Facial']);
    }

    public function test_staff_cannot_create_service(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->post(route('admin.services.store'), [
            'name' => 'X',
            'duration_minutes' => 10,
            'price' => 1,
        ])->assertForbidden();
    }

    public function test_owner_stock_adjust_writes_movement(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create(['stock_qty' => 5]);
        $this->actingAs($owner)->post(route('admin.products.stock', $product), [
            'delta' => 3,
            'reason' => 'manual_adjust',
        ]);
        $this->assertSame(8, $product->fresh()->stock_qty);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'delta' => 3,
            'reason' => 'manual_adjust',
            'user_id' => $owner->id,
        ]);
    }

    public function test_owner_can_create_package_with_services_pivot(): void
    {
        $owner = User::factory()->owner()->create();
        $service = Service::factory()->create();

        $this->actingAs($owner)->post(route('admin.packages.store'), [
            'name' => 'Glow Set',
            'description' => 'Bundle',
            'price' => 3500,
            'is_active' => true,
            'service_ids' => [$service->id],
        ])->assertRedirect();

        $package = Package::query()->where('name', 'Glow Set')->first();
        $this->assertNotNull($package);
        $this->assertTrue($package->services->contains('id', $service->id));
    }

    public function test_owner_can_create_staff_user(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('admin.staff.store'), [
            'name' => 'New Stylist',
            'email' => 'stylist@example.com',
            'phone' => '9800111222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'stylist@example.com',
            'role' => UserRole::Staff->value,
        ]);
    }

    public function test_owner_can_update_settings(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->put(route('admin.settings.update'), [
            'salon_open' => '09:00',
            'salon_close' => '20:00',
            'auto_confirm' => false,
            'max_concurrent' => 2,
            'slot_minutes' => 30,
            'package_duration' => 60,
            'vat_enabled' => true,
            'vat_rate' => 13,
            'vat_inclusive' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'salon_open', 'value' => '09:00']);
        $this->assertDatabaseHas('settings', ['key' => 'max_concurrent', 'value' => '2']);
        $this->assertDatabaseHas('settings', ['key' => 'auto_confirm', 'value' => '0']);
        $this->assertDatabaseHas('settings', ['key' => 'vat_rate', 'value' => '13']);
    }

    public function test_sales_report_filters_paid_by_date_range(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create();

        $older = Sale::query()->create([
            'sale_number' => 'PGS-20260801-0001',
            'staff_id' => $staff->id,
            'subtotal' => 1000,
            'discount' => 0,
            'total' => 1000,
            'status' => SaleStatus::Paid,
        ]);
        $older->forceFill([
            'created_at' => '2026-08-01 12:00:00',
            'updated_at' => '2026-08-01 12:00:00',
        ])->saveQuietly();

        $inRange = Sale::query()->create([
            'sale_number' => 'PGS-20260805-0001',
            'staff_id' => $staff->id,
            'subtotal' => 500,
            'discount' => 0,
            'total' => 500,
            'status' => SaleStatus::Paid,
        ]);
        $inRange->forceFill([
            'created_at' => '2026-08-05 12:00:00',
            'updated_at' => '2026-08-05 12:00:00',
        ])->saveQuietly();

        $this->actingAs($owner)
            ->get(route('admin.sales.index', [
                'from' => '2026-08-04',
                'to' => '2026-08-06',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Sales/Index')
                ->has('sales', 1)
                ->where('total', 500)
            );
    }

    public function test_owner_can_access_log_viewer(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('log-viewer.index'))
            ->assertOk();
    }

    public function test_staff_cannot_access_log_viewer(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('log-viewer.index'))
            ->assertForbidden();
    }
}
