<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Payments\EsewaGateway;
use App\Services\Payments\VerifyResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config([
            'payments.esewa.merchant_code' => 'EPAYTEST',
            'payments.esewa.secret' => 'test-secret',
            'payments.esewa.status_url' => 'https://rc.esewa.com.np/api/epay/transaction/status/',
        ]);
    }

    public function test_verify_marks_sale_paid_once(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 10, 'price' => 100]);
        $productId = $product->id;

        $sale = Sale::query()->create([
            'sale_number' => 'PGS-'.now()->format('Ymd').'-0001',
            'customer_id' => null,
            'staff_id' => $staff->id,
            'appointment_id' => null,
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => SaleStatus::PendingPayment,
        ]);

        $sale->items()->create([
            'item_type' => ItemType::Product->value,
            'item_id' => $productId,
            'name_snapshot' => $product->name,
            'qty' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        // Keep sale total consistent with line for realism (stock uses line qty).
        $sale->update(['subtotal' => 200, 'total' => 200]);

        $idempotencyKey = (string) Str::uuid();
        $sale->payments()->create([
            'method' => PaymentMethod::Esewa,
            'amount' => 200,
            'status' => PaymentStatus::Pending,
            'idempotency_key' => $idempotencyKey,
        ]);

        $gateway = Mockery::mock(EsewaGateway::class);
        $gateway->shouldReceive('methodName')->andReturn('esewa');
        $gateway->shouldReceive('verify')->andReturn(new VerifyResult(
            ok: true,
            idempotencyKey: $idempotencyKey,
            gatewayReference: 'TX-1',
            payload: ['raw' => true],
        ));
        $this->app->instance(EsewaGateway::class, $gateway);

        $this->post(route('payments.callback', ['method' => 'esewa']), ['token' => 'x'])
            ->assertOk();

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'paid']);
        $this->assertDatabaseHas('products', ['id' => $productId, 'stock_qty' => 8]);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'status' => 'completed',
            'gateway_reference' => 'TX-1',
        ]);

        $productQty = Product::find($productId)->stock_qty;
        $this->post(route('payments.callback', ['method' => 'esewa']), ['token' => 'x']);
        $this->assertSame($productQty, Product::find($productId)->fresh()->stock_qty);
        $this->assertSame(1, $sale->stockMovements()->count());
    }

    public function test_staff_confirm_fallback_marks_paid(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 5, 'price' => 100]);

        $sale = Sale::query()->create([
            'sale_number' => 'PGS-'.now()->format('Ymd').'-0002',
            'customer_id' => null,
            'staff_id' => $staff->id,
            'appointment_id' => null,
            'subtotal' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => SaleStatus::PendingPayment,
        ]);

        $sale->items()->create([
            'item_type' => ItemType::Product->value,
            'item_id' => $product->id,
            'name_snapshot' => $product->name,
            'qty' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $sale->payments()->create([
            'method' => PaymentMethod::Khalti,
            'amount' => 100,
            'status' => PaymentStatus::Pending,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->actingAs($staff)->post(route('pos.payments.staff-confirm', $sale), [
            'method' => 'khalti',
            'gateway_reference' => 'shown-on-phone-123',
        ])->assertRedirect();

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'paid']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 4]);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'method' => 'khalti',
            'status' => 'completed',
            'gateway_reference' => 'shown-on-phone-123',
        ]);
    }

    public function test_failed_verify_does_not_mark_paid(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 3, 'price' => 50]);

        $sale = Sale::query()->create([
            'sale_number' => 'PGS-'.now()->format('Ymd').'-0003',
            'staff_id' => $staff->id,
            'subtotal' => 50,
            'discount' => 0,
            'total' => 50,
            'status' => SaleStatus::PendingPayment,
        ]);

        $idempotencyKey = (string) Str::uuid();
        $sale->payments()->create([
            'method' => PaymentMethod::Esewa,
            'amount' => 50,
            'status' => PaymentStatus::Pending,
            'idempotency_key' => $idempotencyKey,
        ]);

        $gateway = Mockery::mock(EsewaGateway::class);
        $gateway->shouldReceive('methodName')->andReturn('esewa');
        $gateway->shouldReceive('verify')->andReturn(new VerifyResult(
            ok: false,
            idempotencyKey: $idempotencyKey,
            failureReason: 'declined',
            payload: ['status' => 'FAILED'],
        ));
        $this->app->instance(EsewaGateway::class, $gateway);

        $this->post(route('payments.callback', ['method' => 'esewa']), ['token' => 'x'])
            ->assertOk()
            ->assertJson(['ok' => false]);

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'pending_payment']);
        $this->assertDatabaseHas('payments', ['idempotency_key' => $idempotencyKey, 'status' => 'failed']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 3]);
    }

    public function test_forged_esewa_callback_status_complete_does_not_mark_paid(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 4, 'price' => 75]);

        $sale = Sale::query()->create([
            'sale_number' => 'PGS-'.now()->format('Ymd').'-0004',
            'staff_id' => $staff->id,
            'subtotal' => 75,
            'discount' => 0,
            'total' => 75,
            'status' => SaleStatus::PendingPayment,
        ]);

        $idempotencyKey = (string) Str::uuid();
        $sale->payments()->create([
            'method' => PaymentMethod::Esewa,
            'amount' => 75,
            'status' => PaymentStatus::Pending,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Status API says not complete (or fails) — client status=COMPLETE must be ignored.
        Http::fake([
            'rc.esewa.com.np/*' => Http::response([
                'status' => 'PENDING',
                'total_amount' => '75.00',
            ], 200),
        ]);

        $this->post(route('payments.callback', ['method' => 'esewa']), [
            'transaction_uuid' => $idempotencyKey,
            'status' => 'COMPLETE',
            'total_amount' => '75.00',
            'transaction_code' => 'FORGED',
        ])
            ->assertOk()
            ->assertJson(['ok' => false]);

        Http::assertSent(function ($request) use ($idempotencyKey) {
            return str_contains($request->url(), 'rc.esewa.com.np')
                && $request['transaction_uuid'] === $idempotencyKey
                && $request['total_amount'] === '75.00'
                && $request['product_code'] === 'EPAYTEST';
        });

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'pending_payment']);
        $this->assertDatabaseHas('payments', [
            'idempotency_key' => $idempotencyKey,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 4]);
    }

    public function test_esewa_status_api_success_marks_paid(): void
    {
        $staff = User::factory()->staff()->create();
        $product = Product::factory()->create(['stock_qty' => 6, 'price' => 120]);

        $sale = Sale::query()->create([
            'sale_number' => 'PGS-'.now()->format('Ymd').'-0005',
            'staff_id' => $staff->id,
            'subtotal' => 120,
            'discount' => 0,
            'total' => 120,
            'status' => SaleStatus::PendingPayment,
        ]);

        $sale->items()->create([
            'item_type' => ItemType::Product->value,
            'item_id' => $product->id,
            'name_snapshot' => $product->name,
            'qty' => 1,
            'unit_price' => 120,
            'line_total' => 120,
        ]);

        $idempotencyKey = (string) Str::uuid();
        $sale->payments()->create([
            'method' => PaymentMethod::Esewa,
            'amount' => 120,
            'status' => PaymentStatus::Pending,
            'idempotency_key' => $idempotencyKey,
        ]);

        Http::fake([
            'rc.esewa.com.np/*' => Http::response([
                'status' => 'COMPLETE',
                'total_amount' => '120.00',
                'ref_id' => 'ESEWA-REF-99',
                'transaction_uuid' => $idempotencyKey,
            ], 200),
        ]);

        $this->post(route('payments.callback', ['method' => 'esewa']), [
            'transaction_uuid' => $idempotencyKey,
            // Client may omit status or forge it — status API is the source of truth.
            'status' => 'PENDING',
        ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        Http::assertSent(function ($request) use ($idempotencyKey) {
            return str_contains($request->url(), 'rc.esewa.com.np')
                && $request['transaction_uuid'] === $idempotencyKey
                && $request['total_amount'] === '120.00';
        });

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', [
            'idempotency_key' => $idempotencyKey,
            'status' => 'completed',
            'gateway_reference' => 'ESEWA-REF-99',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 5]);
    }
}
