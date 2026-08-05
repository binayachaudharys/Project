<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EsewaGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected HttpFactory $http,
    ) {}

    public function methodName(): string
    {
        return 'esewa';
    }

    public function initiate(Sale $sale, Payment $payment): GatewayRedirect
    {
        $amount = number_format((float) $payment->amount, 2, '.', '');
        $merchantCode = (string) config('payments.esewa.merchant_code');
        $transactionUuid = $payment->idempotency_key;
        $signedFieldNames = 'total_amount,transaction_uuid,product_code';
        $signature = $this->sign("total_amount={$amount},transaction_uuid={$transactionUuid},product_code={$merchantCode}");

        $params = [
            'amount' => $amount,
            'tax_amount' => '0',
            'total_amount' => $amount,
            'transaction_uuid' => $transactionUuid,
            'product_code' => $merchantCode,
            'product_service_charge' => '0',
            'product_delivery_charge' => '0',
            'success_url' => route('payments.callback', ['method' => 'esewa']),
            'failure_url' => route('payments.callback', ['method' => 'esewa']),
            'signed_field_names' => $signedFieldNames,
            'signature' => $signature,
        ];

        $url = rtrim((string) config('payments.esewa.base_url'), '/').'/api/epay/main/v2/form';

        Log::info('payment.esewa.initiate', [
            'sale_id' => $sale->id,
            'payment_id' => $payment->id,
            'transaction_uuid' => $transactionUuid,
            'amount' => $amount,
            'url' => $url,
        ]);

        return new GatewayRedirect($url, $params, 'POST');
    }

    public function verify(Request $request): VerifyResult
    {
        $data = $this->extractCallbackData($request);

        Log::info('payment.esewa.verify', [
            'keys' => array_keys($data),
            'transaction_uuid' => $data['transaction_uuid'] ?? null,
            // Client status is never trusted; logged only for diagnostics.
            'client_status' => $data['status'] ?? null,
        ]);

        $idempotencyKey = $data['transaction_uuid'] ?? $request->input('transaction_uuid');

        if (! is_string($idempotencyKey) || $idempotencyKey === '') {
            return new VerifyResult(ok: false, failureReason: 'Missing transaction_uuid.', payload: $data);
        }

        $payment = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($payment === null) {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $idempotencyKey,
                failureReason: 'Unknown payment.',
                payload: $data,
            );
        }

        // Never treat client-only status (e.g. COMPLETE) as paid — always confirm via eSewa status API
        // using the amount stored on the payment row (not client-supplied amount).
        $statusCheck = $this->confirmPaidViaStatusApi($payment);
        $payload = array_merge($data, ['status_api' => $statusCheck['body']]);

        if (! $statusCheck['ok']) {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $idempotencyKey,
                failureReason: $statusCheck['failureReason'],
                payload: $payload,
            );
        }

        $body = $statusCheck['body'];
        $reference = (string) ($body['ref_id']
            ?? $body['transaction_code']
            ?? $body['reference_id']
            ?? $data['transaction_code']
            ?? $data['ref_id']
            ?? 'esewa');

        return new VerifyResult(
            ok: true,
            idempotencyKey: $idempotencyKey,
            gatewayReference: $reference,
            payload: $payload,
        );
    }

    /**
     * Server-side status enquiry. Client callback status is ignored.
     *
     * @return array{ok: bool, failureReason: string, body: array<string, mixed>}
     */
    protected function confirmPaidViaStatusApi(Payment $payment): array
    {
        $merchantCode = (string) config('payments.esewa.merchant_code');
        $expectedAmount = number_format((float) $payment->amount, 2, '.', '');
        $statusUrl = (string) config('payments.esewa.status_url');

        if ($merchantCode === '' || $statusUrl === '') {
            return [
                'ok' => false,
                'failureReason' => 'eSewa status verification is not configured.',
                'body' => [],
            ];
        }

        try {
            $response = $this->http->get($statusUrl, [
                'product_code' => $merchantCode,
                'total_amount' => $expectedAmount,
                'transaction_uuid' => $payment->idempotency_key,
            ]);
        } catch (\Throwable $e) {
            Log::info('payment.esewa.verify', [
                'status_check_failed' => true,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'failureReason' => 'eSewa status API unavailable.',
                'body' => [],
            ];
        }

        $body = is_array($response->json()) ? $response->json() : [];

        Log::info('payment.esewa.verify', [
            'status_http' => $response->status(),
            'status_body' => $body,
        ]);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'failureReason' => 'eSewa status API request failed.',
                'body' => $body,
            ];
        }

        $remoteStatus = strtoupper((string) ($body['status'] ?? $body['transaction_status'] ?? ''));
        if (! in_array($remoteStatus, ['COMPLETE', 'COMPLETED', 'SUCCESS'], true)) {
            return [
                'ok' => false,
                'failureReason' => 'Transaction not complete.',
                'body' => $body,
            ];
        }

        // Fail-closed: gateway must return amount, and it must match payment.amount.
        $rawAmount = $body['total_amount'] ?? $body['amount'] ?? null;
        if ($rawAmount === null || $rawAmount === '') {
            return [
                'ok' => false,
                'failureReason' => 'Amount missing from gateway response.',
                'body' => $body,
            ];
        }

        $reportedAmount = number_format((float) str_replace(',', '', (string) $rawAmount), 2, '.', '');
        if ($reportedAmount !== $expectedAmount) {
            return [
                'ok' => false,
                'failureReason' => 'Amount mismatch.',
                'body' => $body,
            ];
        }

        return [
            'ok' => true,
            'failureReason' => '',
            'body' => $body,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractCallbackData(Request $request): array
    {
        $encoded = $request->query('data') ?? $request->input('data');
        if (is_string($encoded) && $encoded !== '') {
            $decoded = base64_decode($encoded, true);
            if ($decoded !== false) {
                $json = json_decode($decoded, true);
                if (is_array($json)) {
                    return $json;
                }
            }
        }

        return $request->all();
    }

    protected function sign(string $message): string
    {
        $secret = (string) config('payments.esewa.secret');

        return base64_encode(hash_hmac('sha256', $message, $secret, true));
    }
}
