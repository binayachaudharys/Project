<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\BookableType;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCashSaleTest extends TestCase
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

    public function test_cash_sale_decrements_product_stock(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 10, 'price' => 100]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'customer_id' => null,
            'appointment_id' => null,
            'discount' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['item_type' => 'product', 'item_id' => $product->id, 'qty' => 2],
            ],
        ])->assertRedirect(route('staff.billing.invoice', [
            'sale' => Sale::query()->latest('id')->first(),
            'print' => 1,
        ]));

        $this->assertDatabaseHas('sales', ['status' => 'paid', 'total' => 200]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 8]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'delta' => -2,
            'reason' => 'sale',
        ]);
        $this->assertDatabaseHas('payments', [
            'method' => 'cash',
            'status' => 'completed',
            'amount' => 200,
        ]);
    }

    public function test_insufficient_stock_blocks_sale(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 1, 'price' => 100]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'payment_method' => 'cash',
            'discount' => 0,
            'items' => [
                ['item_type' => 'product', 'item_id' => $product->id, 'qty' => 3],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 1]);
        $this->assertDatabaseMissing('sales', ['status' => 'paid']);
    }

    public function test_customer_cannot_use_pos(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->post(route('pos.checkout'), [])->assertForbidden();
    }

    public function test_checkout_accepts_invoice_unit_price_override(): void
    {
        $staff = User::factory()->staff()->create();
        $service = Service::factory()->create(['price' => 500]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'discount' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['item_type' => 'service', 'item_id' => $service->id, 'qty' => 1, 'unit_price' => 350],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', ['status' => 'paid', 'subtotal' => 350, 'total' => 350]);
        $this->assertDatabaseHas('sale_items', ['item_id' => $service->id, 'unit_price' => 350]);
    }

    public function test_nepal_vat_exclusive_is_added_on_checkout(): void
    {
        Setting::query()->updateOrCreate(['key' => 'vat_enabled'], ['value' => '1']);
        Setting::query()->updateOrCreate(['key' => 'vat_rate'], ['value' => '13']);
        Setting::query()->updateOrCreate(['key' => 'vat_inclusive'], ['value' => '0']);

        $staff = User::factory()->staff()->create();
        $service = Service::factory()->create(['price' => 1000]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'discount' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['item_type' => 'service', 'item_id' => $service->id, 'qty' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', [
            'status' => 'paid',
            'subtotal' => 1000,
            'tax' => 130,
            'tax_rate' => 13,
            'total' => 1130,
            'prices_include_vat' => false,
        ]);
    }

    public function test_percent_discount_is_capped_at_subtotal(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 10, 'price' => 100]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'discount' => 150,
            'discount_type' => 'percent',
            'payment_method' => 'cash',
            'items' => [
                ['item_type' => 'product', 'item_id' => $product->id, 'qty' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', ['status' => 'paid', 'discount' => 100, 'total' => 0]);
    }

    public function test_cash_sale_marks_linked_appointment_completed(): void
    {
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create(['price' => 300]);
        $appointment = Appointment::factory()->create([
            'customer_id' => $customer->id,
            'bookable_type' => BookableType::Service->value,
            'bookable_id' => $service->id,
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'appointment_id' => $appointment->id,
            'discount' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['item_type' => 'service', 'item_id' => $service->id, 'qty' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'completed']);
    }

    public function test_digital_payment_method_creates_pending_sale_without_stock_change(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 5, 'price' => 100]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'discount' => 0,
            'payment_method' => 'esewa',
            'items' => [
                ['item_type' => 'product', 'item_id' => $product->id, 'qty' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', ['status' => 'pending_payment', 'total' => 100]);
        $this->assertDatabaseHas('payments', ['method' => 'esewa', 'status' => 'pending']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 5]);
    }

    public function test_owner_can_void_paid_sale_and_restore_stock(): void
    {
        $staff = User::factory()->staff()->create();
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create(['stock_qty' => 10, 'price' => 100]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'discount' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['item_type' => 'product', 'item_id' => $product->id, 'qty' => 3],
            ],
        ])->assertRedirect();

        $sale = Sale::firstOrFail();

        $this->actingAs($owner)
            ->post(route('pos.sales.void', $sale))
            ->assertRedirect();

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'void']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 10]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'delta' => 3,
            'reason' => 'void_restore',
        ]);
    }

    public function test_cannot_void_a_sale_that_is_not_paid(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 5, 'price' => 100]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'discount' => 0,
            'payment_method' => 'esewa',
            'items' => [
                ['item_type' => 'product', 'item_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $sale = Sale::firstOrFail();

        $this->actingAs($staff)
            ->post(route('pos.sales.void', $sale))
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'pending_payment']);
    }

    public function test_voiding_twice_does_not_double_restore_stock(): void
    {
        $staff = User::factory()->staff()->create();
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create(['stock_qty' => 10, 'price' => 100]);

        $this->actingAs($staff)->post(route('pos.checkout'), [
            'discount' => 0,
            'payment_method' => 'cash',
            'items' => [
                ['item_type' => 'product', 'item_id' => $product->id, 'qty' => 3],
            ],
        ])->assertRedirect();

        $sale = Sale::firstOrFail();

        $this->actingAs($owner)
            ->post(route('pos.sales.void', $sale))
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('pos.sales.void', $sale))
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'void']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 10]);
        $this->assertDatabaseCount('stock_movements', 2); // sale deduct + one void restore
        $this->assertEquals(
            1,
            \App\Models\StockMovement::query()
                ->where('sale_id', $sale->id)
                ->where('reason', 'void_restore')
                ->count()
        );
    }
}
