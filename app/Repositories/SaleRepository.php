<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\ItemType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockReason;
use App\Models\Package;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Jsdecena\Baserepo\BaseRepository;

class SaleRepository extends BaseRepository
{
    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }

    /**
     * Build and finalize a POS sale from cart payload. Cash payments are
     * completed immediately (paid + stock deducted); digital methods are
     * left `pending_payment` for the gateway flow (Task 7).
     *
     * @param  array{staff_id:int, customer_id?:int|null, appointment_id?:int|null, discount?:float|string, discount_type?:string, payment_method:string, items:array<int, array{item_type:string, item_id:int, qty:int}>}  $payload
     */
    public function checkout(array $payload): Sale
    {
        return DB::transaction(function () use ($payload) {
            $staff = User::findOrFail($payload['staff_id']);
            $method = PaymentMethod::from($payload['payment_method']);

            $lines = collect($payload['items'])->map(fn (array $item) => $this->buildLine($item));

            $subtotal = round((float) $lines->sum('line_total'), 2);
            $discount = $this->resolveDiscount(
                $subtotal,
                (float) ($payload['discount'] ?? 0),
                (string) ($payload['discount_type'] ?? 'amount')
            );
            $total = round($subtotal - $discount, 2);

            $sale = $this->create([
                'sale_number' => $this->nextSaleNumber(),
                'customer_id' => $payload['customer_id'] ?? null,
                'staff_id' => $staff->id,
                'appointment_id' => $payload['appointment_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => SaleStatus::Draft,
            ]);

            foreach ($lines as $line) {
                $sale->items()->create($line);
            }

            if ($method === PaymentMethod::Cash) {
                $this->applyStockForPaidSale($sale, $staff);

                $sale->update(['status' => SaleStatus::Paid]);

                $sale->payments()->create([
                    'method' => $method,
                    'amount' => $total,
                    'status' => PaymentStatus::Completed,
                    'idempotency_key' => (string) Str::uuid(),
                ]);

                if ($sale->appointment_id) {
                    $sale->appointment?->update(['status' => AppointmentStatus::Completed]);
                }

                return $sale->fresh(['items', 'payments']);
            }

            $sale->update(['status' => SaleStatus::PendingPayment]);

            $sale->payments()->create([
                'method' => $method,
                'amount' => $total,
                'status' => PaymentStatus::Pending,
                'idempotency_key' => (string) Str::uuid(),
            ]);

            return $sale->fresh(['items', 'payments']);
        });
    }

    /**
     * Void a paid sale: restores product stock line by line and marks the
     * sale `void`. Throws if the sale isn't currently paid.
     */
    public function voidPaidSale(Sale $sale, User $actor): Sale
    {
        return DB::transaction(function () use ($sale, $actor) {
            if ($sale->status !== SaleStatus::Paid) {
                throw ValidationException::withMessages([
                    'sale' => 'Only paid sales can be voided.',
                ]);
            }

            foreach ($sale->items()->where('item_type', ItemType::Product->value)->get() as $line) {
                $product = Product::lockForUpdate()->findOrFail($line->item_id);
                $product->increment('stock_qty', $line->qty);

                StockMovement::create([
                    'product_id' => $product->id,
                    'delta' => $line->qty,
                    'reason' => StockReason::VoidRestore->value,
                    'sale_id' => $sale->id,
                    'user_id' => $actor->id,
                ]);
            }

            $sale->update(['status' => SaleStatus::Void]);

            return $sale->fresh(['items', 'payments']);
        });
    }

    /**
     * Resolve one cart line's server-side name/price and compute its total.
     * Client-supplied prices are never trusted.
     */
    protected function buildLine(array $item): array
    {
        $itemId = (int) $item['item_id'];
        $qty = (int) $item['qty'];

        $sellable = match ($item['item_type']) {
            ItemType::Service->value => Service::findOrFail($itemId),
            ItemType::Package->value => Package::findOrFail($itemId),
            ItemType::Product->value => Product::findOrFail($itemId),
        };

        $unitPrice = round((float) $sellable->price, 2);

        return [
            'item_type' => $item['item_type'],
            'item_id' => $itemId,
            'name_snapshot' => $sellable->name,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'line_total' => round($unitPrice * $qty, 2),
        ];
    }

    /**
     * Apply a discount and clamp it so it never exceeds the subtotal.
     */
    protected function resolveDiscount(float $subtotal, float $discount, string $discountType): float
    {
        if ($discountType === 'percent') {
            return round(min($subtotal * ($discount / 100), $subtotal), 2);
        }

        return round(min($discount, $subtotal), 2);
    }

    /**
     * Sequential sale number for today: PGS-YYYYMMDD-XXXX.
     */
    protected function nextSaleNumber(): string
    {
        $count = $this->model->newQuery()
            ->whereDate('created_at', now()->toDateString())
            ->lockForUpdate()
            ->count();

        return sprintf('PGS-%s-%04d', now()->format('Ymd'), $count + 1);
    }

    /**
     * Deduct stock for each product line of a newly paid sale, recording a
     * stock movement per line. Re-checks availability under a row lock in
     * case stock changed since the cart was built.
     */
    protected function applyStockForPaidSale(Sale $sale, User $actor): void
    {
        foreach ($sale->items()->where('item_type', ItemType::Product->value)->get() as $line) {
            $product = Product::lockForUpdate()->findOrFail($line->item_id);
            if ($product->stock_qty < $line->qty) {
                throw ValidationException::withMessages(['items' => "Insufficient stock for {$product->name}."]);
            }
            $product->decrement('stock_qty', $line->qty);
            StockMovement::create([
                'product_id' => $product->id,
                'delta' => -1 * $line->qty,
                'reason' => StockReason::Sale->value,
                'sale_id' => $sale->id,
                'user_id' => $actor->id,
            ]);
        }
    }
}
