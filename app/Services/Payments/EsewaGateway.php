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
            'status' => $data['status'] ?? null,
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

        $expectedAmount = number_format((float) $payment->amount, 2, '.', '');
        $reportedAmount = isset($data['total_amount'])
            ? number_format((float) str_replace(',', '', (string) $data['total_amount']), 2, '.', '')
            : null;

        if ($reportedAmount !== null && $reportedAmount !== $expectedAmount) {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $idempotencyKey,
                failureReason: 'Amount mismatch.',
                payload: $data,
            );
        }

        if (! $this->statusComplete($payment, $data)) {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $idempotencyKey,
                failureReason: 'Transaction not complete.',
                payload: $data,
            );
        }

        $reference = (string) ($data['transaction_code'] ?? $data['ref_id'] ?? $data['reference_id'] ?? 'esewa');

        return new VerifyResult(
            ok: true,
            idempotencyKey: $idempotencyKey,
            gatewayReference: $reference,
            payload: $data,
        );
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

    /**
     * @param  array<string, mixed>  $data
     */
    protected function statusComplete(Payment $payment, array $data): bool
    {
        $status = strtoupper((string) ($data['status'] ?? ''));
        if (in_array($status, ['COMPLETE', 'COMPLETED', 'SUCCESS'], true)) {
            return true;
        }

        $merchantCode = (string) config('payments.esewa.merchant_code');
        $amount = number_format((float) $payment->amount, 2, '.', '');
        $statusUrl = (string) config('payments.esewa.status_url');

        try {
            $response = $this->http->get($statusUrl, [
                'product_code' => $merchantCode,
                'total_amount' => $amount,
                'transaction_uuid' => $payment->idempotency_key,
            ]);
        } catch (\Throwable $e) {
            Log::info('payment.esewa.verify', [
                'status_check_failed' => true,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        $body = $response->json() ?? [];
        Log::info('payment.esewa.verify', [
            'status_http' => $response->status(),
            'status_body' => $body,
        ]);

        $remoteStatus = strtoupper((string) ($body['status'] ?? $body['transaction_status'] ?? ''));

        return $response->successful() && in_array($remoteStatus, ['COMPLETE', 'COMPLETED', 'SUCCESS'], true);
    }

    protected function sign(string $message): string
    {
        $secret = (string) config('payments.esewa.secret');

        return base64_encode(hash_hmac('sha256', $message, $secret, true));
    }
}
