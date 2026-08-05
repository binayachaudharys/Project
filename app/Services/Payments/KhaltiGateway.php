<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KhaltiGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected HttpFactory $http,
    ) {}

    public function methodName(): string
    {
        return 'khalti';
    }

    public function initiate(Sale $sale, Payment $payment): GatewayRedirect
    {
        $secret = (string) config('payments.khalti.secret_key');
        if ($secret === '') {
            throw new RuntimeException('Khalti secret key is not configured.');
        }

        $base = rtrim((string) config('payments.khalti.base_url'), '/');
        $amountPaisa = (int) round(((float) $payment->amount) * 100);

        $payload = [
            'return_url' => route('payments.callback', ['method' => 'khalti']),
            'website_url' => url('/'),
            'amount' => $amountPaisa,
            'purchase_order_id' => $payment->idempotency_key,
            'purchase_order_name' => 'Sale '.$sale->sale_number,
        ];

        $response = $this->http
            ->withHeaders([
                'Authorization' => 'Key '.$secret,
                'Content-Type' => 'application/json',
            ])
            ->post($base.'/epayment/initiate/', $payload);

        $body = $response->json() ?? [];

        Log::info('payment.khalti.initiate', [
            'sale_id' => $sale->id,
            'payment_id' => $payment->id,
            'http_status' => $response->status(),
            'pidx' => $body['pidx'] ?? null,
            'has_payment_url' => isset($body['payment_url']),
        ]);

        if (! $response->successful() || empty($body['payment_url'])) {
            throw new RuntimeException('Khalti initiate failed.');
        }

        $payment->update([
            'gateway_payload' => array_merge($payment->gateway_payload ?? [], [
                'pidx' => $body['pidx'] ?? null,
            ]),
        ]);

        return new GatewayRedirect((string) $body['payment_url'], [], 'GET');
    }

    public function verify(Request $request): VerifyResult
    {
        $pidx = $request->input('pidx') ?? $request->query('pidx');
        $purchaseOrderId = $request->input('purchase_order_id') ?? $request->query('purchase_order_id');

        Log::info('payment.khalti.verify', [
            'pidx' => $pidx,
            'purchase_order_id' => $purchaseOrderId,
            'status' => $request->input('status') ?? $request->query('status'),
        ]);

        if (! is_string($pidx) || $pidx === '') {
            return new VerifyResult(ok: false, failureReason: 'Missing pidx.');
        }

        $secret = (string) config('payments.khalti.secret_key');
        if ($secret === '') {
            return new VerifyResult(ok: false, failureReason: 'Khalti secret key is not configured.');
        }

        $base = rtrim((string) config('payments.khalti.base_url'), '/');

        try {
            $response = $this->http
                ->withHeaders([
                    'Authorization' => 'Key '.$secret,
                    'Content-Type' => 'application/json',
                ])
                ->post($base.'/epayment/lookup/', ['pidx' => $pidx]);
        } catch (\Throwable $e) {
            return new VerifyResult(ok: false, failureReason: $e->getMessage());
        }

        $body = $response->json() ?? [];

        Log::info('payment.khalti.verify', [
            'http_status' => $response->status(),
            'lookup_status' => $body['status'] ?? null,
            'total_amount' => $body['total_amount'] ?? null,
        ]);

        $payment = $this->resolvePayment($pidx, is_string($purchaseOrderId) ? $purchaseOrderId : null);
        if ($payment === null) {
            return new VerifyResult(
                ok: false,
                failureReason: 'Unknown payment.',
                payload: $body,
            );
        }

        $status = (string) ($body['status'] ?? '');
        if (! $response->successful() || $status !== 'Completed') {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $payment->idempotency_key,
                failureReason: 'Transaction not completed.',
                payload: $body,
            );
        }

        // Fail-closed: gateway must return amount, matching payment.amount (paisa).
        if (! array_key_exists('total_amount', $body) || $body['total_amount'] === null || $body['total_amount'] === '') {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $payment->idempotency_key,
                failureReason: 'Amount missing from gateway response.',
                payload: $body,
            );
        }

        $expectedPaisa = (int) round(((float) $payment->amount) * 100);
        $reportedPaisa = (int) $body['total_amount'];

        if ($reportedPaisa !== $expectedPaisa) {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $payment->idempotency_key,
                failureReason: 'Amount mismatch.',
                payload: $body,
            );
        }

        return new VerifyResult(
            ok: true,
            idempotencyKey: $payment->idempotency_key,
            gatewayReference: (string) ($body['transaction_id'] ?? $pidx),
            payload: $body,
        );
    }

    protected function resolvePayment(string $pidx, ?string $purchaseOrderId): ?Payment
    {
        if ($purchaseOrderId) {
            $byKey = Payment::query()->where('idempotency_key', $purchaseOrderId)->first();
            if ($byKey !== null) {
                return $byKey;
            }
        }

        return Payment::query()
            ->where('method', 'khalti')
            ->where('gateway_payload->pidx', $pidx)
            ->first();
    }
}
