<?php

namespace App\Repositories;

use App\Enums\AppointmentStatus;
use App\Enums\ItemType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockReason;
use App\Models\Appointment;
use App\Models\Package;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\Setting;
use App\Models\User;
use App\Services\NepalVat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Jsdecena\Baserepo\BaseRepository;

class SaleRepository extends BaseRepository
{
    private const MAX_SALE_NUMBER_ATTEMPTS = 3;

    public function __construct(Sale $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a pending-payment sale for a confirmed appointment (desk billing).
     * Reuses an existing open bill when one is already linked.
     */
    public function createBillingFromAppointment(Appointment $appointment, User $actor): Sale
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            $appointment->loadMissing('bookable', 'sale');

            if ($appointment->status === AppointmentStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'appointment' => 'Cancelled appointments cannot be billed.',
                ]);
            }

            $existing = $appointment->sale;
            if ($existing && in_array($existing->status, [SaleStatus::PendingPayment, SaleStatus::Paid], true)) {
                return $existing->fresh(['items', 'payments', 'customer', 'appointment']);
            }

            $bookable = $appointment->bookable;
            if ($bookable === null) {
                throw ValidationException::withMessages([
                    'appointment' => 'This appointment has no bookable service or package.',
                ]);
            }

            $itemType = $appointment->bookable_type instanceof \BackedEnum
                ? $appointment->bookable_type->value
                : (string) $appointment->bookable_type;

            $line = $this->buildLine([
                'item_type' => $itemType,
                'item_id' => (int) $appointment->bookable_id,
                'qty' => 1,
            ]);

            $subtotal = round((float) $line['line_total'], 2);
            $vat = $this->resolveVat($subtotal, 0);

            $sale = $this->create([
                'sale_number' => $this->nextSaleNumber(),
                'customer_id' => $appointment->customer_id,
                'staff_id' => $actor->id,
                'appointment_id' => $appointment->id,
                'subtotal' => $subtotal,
                'discount' => 0,
                'tax' => $vat['tax'],
                'tax_rate' => $vat['rate'],
                'prices_include_vat' => $vat['inclusive'],
                'total' => $vat['total'],
                'status' => SaleStatus::PendingPayment,
            ]);

            $sale->items()->create($line);

            $sale->payments()->create([
                'method' => PaymentMethod::Cash,
                'amount' => $vat['total'],
                'status' => PaymentStatus::Pending,
                'idempotency_key' => (string) Str::uuid(),
            ]);

            if ($appointment->status === AppointmentStatus::Pending) {
                $appointment->update(['status' => AppointmentStatus::Confirmed]);
            }

            return $sale->fresh(['items', 'payments', 'customer', 'appointment']);
        });
    }

    /**
     * Paid sales within an inclusive calendar date range (local app timezone).
     *
     * @return array{sales: EloquentCollection<int, Sale>, total: float}
     */
    public function paidSalesInRange(Carbon $from, Carbon $to): array
    {
        $sales = $this->model->newQuery()
            ->where('status', SaleStatus::Paid)
            ->whereBetween('created_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->with(['customer:id,name', 'staff:id,name'])
            ->orderByDesc('created_at')
            ->get([
                'id', 'sale_number', 'customer_id', 'staff_id',
                'subtotal', 'discount', 'total', 'status', 'created_at',
            ]);

        return [
            'sales' => $sales,
            'total' => round((float) $sales->sum('total'), 2),
        ];
    }

    /**
     * Build and finalize a POS sale from cart payload. Cash payments are
     * completed immediately (paid + stock deducted); digital methods are
     * left `pending_payment` for the gateway flow (Task 7).
     *
     * @param  array{staff_id:int, customer_id?:int|null, appointment_id?:int|null, discount?:float|string, discount_type?:string, payment_method:string, prices_include_vat?:bool|null, items:array<int, array{item_type:string, item_id:int, qty:int, unit_price?:float|string|null}>}  $payload
     */
    public function checkout(array $payload): Sale
    {
        $attempt = 0;

        while (true) {
            try {
                return $this->checkoutWithinTransaction($payload);
            } catch (QueryException $e) {
                if (! $this->isDuplicateSaleNumberException($e) || ++$attempt >= self::MAX_SALE_NUMBER_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Finalize a pending digital payment: apply stock once, mark paid, and
     * complete any linked appointment. Idempotent when already paid.
     */
    public function finalizePendingSaleAsPaid(Sale $sale, User $actor): Sale
    {
        return DB::transaction(function () use ($sale, $actor) {
            $sale = $this->model->newQuery()->lockForUpdate()->findOrFail($sale->id);

            if ($sale->status === SaleStatus::Paid) {
                return $sale->fresh(['items', 'payments']);
            }

            if ($sale->status !== SaleStatus::PendingPayment) {
                throw ValidationException::withMessages([
                    'sale' => 'Only pending-payment sales can be marked paid.',
                ]);
            }

            $this->applyStockForPaidSale($sale, $actor);

            $sale->update(['status' => SaleStatus::Paid]);

            if ($sale->appointment_id) {
                $sale->appointment?->update(['status' => AppointmentStatus::Completed]);
            }

            return $sale->fresh(['items', 'payments']);
        });
    }

    /**
     * Void a paid sale: restores product stock line by line and marks the
     * sale `void`. Throws if the sale isn't currently paid.
     *
     * Locks the sale row before the status check so concurrent void requests
     * cannot both restore stock (double-void race).
     */
    public function voidPaidSale(Sale $sale, User $actor): Sale
    {
        return DB::transaction(function () use ($sale, $actor) {
            $sale = $this->model->newQuery()->lockForUpdate()->findOrFail($sale->id);

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
     * @param  array{staff_id:int, customer_id?:int|null, appointment_id?:int|null, discount?:float|string, discount_type?:string, payment_method:string, prices_include_vat?:bool|null, items:array<int, array{item_type:string, item_id:int, qty:int, unit_price?:float|string|null}>}  $payload
     */
    protected function checkoutWithinTransaction(array $payload): Sale
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
            $vat = $this->resolveVat(
                $subtotal,
                $discount,
                array_key_exists('prices_include_vat', $payload)
                    ? (bool) $payload['prices_include_vat']
                    : null,
            );

            $sale = $this->create([
                'sale_number' => $this->nextSaleNumber(),
                'customer_id' => $payload['customer_id'] ?? null,
                'staff_id' => $staff->id,
                'appointment_id' => $payload['appointment_id'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $vat['tax'],
                'tax_rate' => $vat['rate'],
                'prices_include_vat' => $vat['inclusive'],
                'total' => $vat['total'],
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
                    'amount' => $vat['total'],
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
                'amount' => $vat['total'],
                'status' => PaymentStatus::Pending,
                'idempotency_key' => (string) Str::uuid(),
            ]);

            return $sale->fresh(['items', 'payments']);
        });
    }

    /**
     * Resolve one cart line. Catalog price is default; optional unit_price
     * override is allowed for owner/staff invoice pricing.
     *
     * @param  array{item_type:string, item_id:int|string, qty:int|string, unit_price?:float|string|null}  $item
     * @return array{item_type:string, item_id:int, name_snapshot:string, qty:int, unit_price:float, line_total:float}
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

        $catalogPrice = round((float) $sellable->price, 2);
        $unitPrice = $catalogPrice;

        if (array_key_exists('unit_price', $item) && $item['unit_price'] !== null && $item['unit_price'] !== '') {
            $override = round((float) $item['unit_price'], 2);
            if ($override < 0) {
                throw ValidationException::withMessages([
                    'items' => 'Invoice line price cannot be negative.',
                ]);
            }
            $unitPrice = $override;
        }

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
     * @return array{taxable: float, tax: float, total: float, rate: float, inclusive: bool}
     */
    protected function resolveVat(float $subtotal, float $discount, ?bool $inclusiveOverride = null): array
    {
        $enabled = Setting::resolveBool('vat_enabled', true);
        $rate = (float) Setting::resolve('vat_rate', NepalVat::DEFAULT_RATE);
        $inclusive = $inclusiveOverride ?? Setting::resolveBool('vat_inclusive', false);

        return NepalVat::compute($subtotal, $discount, $rate, $inclusive, $enabled);
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
        $datePart = now()->format('Ymd');
        $prefix = sprintf('PGS-%s-', $datePart);

        $last = $this->model->newQuery()
            ->where('sale_number', 'like', $prefix.'%')
            ->orderByDesc('sale_number')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($last !== null) {
            $suffix = substr($last->sale_number, strlen($prefix));
            $sequence = max(1, (int) $suffix) + 1;
        }

        return sprintf('%s%04d', $prefix, $sequence);
    }

    protected function isDuplicateSaleNumberException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        if ($sqlState === '23000') {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'sale_number') && (
            str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
        );
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
