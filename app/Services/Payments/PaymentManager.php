<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\SaleRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PaymentManager
{
    public function __construct(
        protected SaleRepository $sales,
    ) {}

    public function gateway(string $method): PaymentGatewayInterface
    {
        return match ($method) {
            PaymentMethod::Esewa->value, 'esewa' => app(EsewaGateway::class),
            PaymentMethod::Khalti->value, 'khalti' => app(KhaltiGateway::class),
            PaymentMethod::Fonepay->value, 'fonepay' => app(FonepayGateway::class),
            default => throw new InvalidArgumentException("Unsupported payment method [{$method}]."),
        };
    }

    /**
     * Mark a sale paid from a gateway verify result. Safe to call twice:
     * stock is applied only while the sale is still pending_payment and the
     * payment is still pending.
     */
    public function markPaidFromVerify(VerifyResult $result): ?Sale
    {
        if ($result->idempotencyKey === null || $result->idempotencyKey === '') {
            return null;
        }

        return DB::transaction(function () use ($result) {
            $payment = Payment::query()
                ->where('idempotency_key', $result->idempotencyKey)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return null;
            }

            $sale = Sale::query()->lockForUpdate()->find($payment->sale_id);
            if ($sale === null) {
                return null;
            }

            if ($payment->status === PaymentStatus::Completed || $sale->status === SaleStatus::Paid) {
                return $sale->fresh(['items', 'payments']);
            }

            if (! $result->ok) {
                if ($payment->status === PaymentStatus::Pending) {
                    $payment->update([
                        'status' => PaymentStatus::Failed,
                        'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                            'verify_failure' => $result->failureReason,
                            'verify_payload' => $result->payload,
                        ]),
                    ]);
                }

                return $sale->fresh(['items', 'payments']);
            }

            $payment->update([
                'status' => PaymentStatus::Completed,
                'gateway_reference' => $result->gatewayReference,
                'gateway_payload' => array_merge($payment->gateway_payload ?? [], $result->payload),
            ]);

            $actor = $sale->staff ?? User::query()->findOrFail($sale->staff_id);

            return $this->sales->finalizePendingSaleAsPaid($sale, $actor);
        });
    }

    /**
     * LAN-friendly staff confirmation when gateway callbacks cannot reach the salon PC.
     */
    public function staffConfirm(Sale $sale, PaymentMethod $method, ?string $ref, User $actor): Sale
    {
        if ($method === PaymentMethod::Cash) {
            throw ValidationException::withMessages([
                'method' => 'Use cash checkout for cash payments.',
            ]);
        }

        return DB::transaction(function () use ($sale, $method, $ref, $actor) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($sale->status === SaleStatus::Paid) {
                return $sale->fresh(['items', 'payments']);
            }

            if ($sale->status !== SaleStatus::PendingPayment) {
                throw ValidationException::withMessages([
                    'sale' => 'Only pending-payment sales can be staff-confirmed.',
                ]);
            }

            $payment = Payment::query()
                ->where('sale_id', $sale->id)
                ->where('status', PaymentStatus::Pending->value)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($payment === null) {
                $payment = $sale->payments()->create([
                    'method' => $method,
                    'amount' => $sale->total,
                    'status' => PaymentStatus::Pending,
                    'idempotency_key' => (string) Str::uuid(),
                ]);
                $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            }

            $payment->update([
                'method' => $method,
                'status' => PaymentStatus::Completed,
                'gateway_reference' => $ref,
                'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                    'staff_confirm' => true,
                    'confirmed_by' => $actor->id,
                ]),
            ]);

            return $this->sales->finalizePendingSaleAsPaid($sale, $actor);
        });
    }
}
