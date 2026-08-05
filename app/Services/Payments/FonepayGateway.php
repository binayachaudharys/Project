<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FonepayGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected HttpFactory $http,
    ) {}

    public function methodName(): string
    {
        return 'fonepay';
    }

    public function initiate(Sale $sale, Payment $payment): QrPayload
    {
        $merchantCode = (string) config('payments.fonepay.merchant_code');
        $secret = (string) config('payments.fonepay.secret');
        $username = (string) config('payments.fonepay.username');
        $password = (string) config('payments.fonepay.password');

        if ($merchantCode === '' || $secret === '') {
            // Local/LAN fallback: still give staff something to display with the PRN.
            $qrData = 'fonepay:'.$payment->idempotency_key.':'.$payment->amount;

            Log::info('payment.fonepay.initiate', [
                'sale_id' => $sale->id,
                'payment_id' => $payment->id,
                'mode' => 'local_qr',
                'prn' => $payment->idempotency_key,
            ]);

            return new QrPayload($qrData, [
                'prn' => $payment->idempotency_key,
                'amount' => (string) $payment->amount,
                'sale_number' => $sale->sale_number,
            ]);
        }

        $base = rtrim((string) config('payments.fonepay.base_url'), '/');
        $amount = number_format((float) $payment->amount, 2, '.', '');
        $prn = $payment->idempotency_key;
        $remarks1 = 'Sale '.$sale->sale_number;
        $remarks2 = 'salon';

        $dataValidation = hash_hmac(
            'sha512',
            "{$amount},{$prn},{$merchantCode},{$remarks1},{$remarks2}",
            $secret
        );

        $payload = [
            'amount' => $amount,
            'remarks1' => $remarks1,
            'remarks2' => $remarks2,
            'prn' => $prn,
            'merchantCode' => $merchantCode,
            'dataValidation' => $dataValidation,
            'username' => $username,
            'password' => $password,
        ];

        $response = $this->http->asJson()->post($base.'/thirdPartyDynamicQrDownload', $payload);
        $body = $response->json() ?? [];

        Log::info('payment.fonepay.initiate', [
            'sale_id' => $sale->id,
            'payment_id' => $payment->id,
            'http_status' => $response->status(),
            'has_qr' => isset($body['qrMessage']) || isset($body['qr_message']),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Fonepay QR initiate failed.');
        }

        $qrData = (string) ($body['qrMessage'] ?? $body['qr_message'] ?? $body['qrData'] ?? '');

        if ($qrData === '') {
            throw new RuntimeException('Fonepay returned an empty QR payload.');
        }

        return new QrPayload($qrData, [
            'prn' => $prn,
            'amount' => $amount,
            'raw' => array_diff_key($body, array_flip(['password', 'username'])),
        ]);
    }

    public function verify(Request $request): VerifyResult
    {
        $prn = $request->input('prn') ?? $request->query('prn') ?? $request->input('transaction_uuid');

        Log::info('payment.fonepay.verify', [
            'prn' => $prn,
        ]);

        if (! is_string($prn) || $prn === '') {
            return new VerifyResult(ok: false, failureReason: 'Missing prn.');
        }

        $payment = Payment::query()->where('idempotency_key', $prn)->first();
        if ($payment === null) {
            return new VerifyResult(ok: false, idempotencyKey: $prn, failureReason: 'Unknown payment.');
        }

        $merchantCode = (string) config('payments.fonepay.merchant_code');
        $secret = (string) config('payments.fonepay.secret');

        // Without merchant credentials (LAN), accept only trusted staff-confirm path.
        if ($merchantCode === '' || $secret === '') {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $prn,
                failureReason: 'Fonepay credentials not configured; use staff confirm.',
            );
        }

        $base = rtrim((string) config('payments.fonepay.base_url'), '/');
        $dataValidation = hash_hmac('sha512', "{$prn},{$merchantCode}", $secret);

        try {
            $response = $this->http->asJson()->post($base.'/thirdPartyDynamicQrGetStatus', [
                'prn' => $prn,
                'merchantCode' => $merchantCode,
                'dataValidation' => $dataValidation,
                'username' => config('payments.fonepay.username'),
                'password' => config('payments.fonepay.password'),
            ]);
        } catch (\Throwable $e) {
            return new VerifyResult(ok: false, idempotencyKey: $prn, failureReason: $e->getMessage());
        }

        $body = $response->json() ?? [];

        Log::info('payment.fonepay.verify', [
            'http_status' => $response->status(),
            'body_status' => $body['paymentStatus'] ?? $body['status'] ?? null,
        ]);

        $status = strtoupper((string) ($body['paymentStatus'] ?? $body['status'] ?? ''));
        $success = $response->successful() && in_array($status, ['SUCCESS', 'SUCCESSFUL', 'COMPLETE', 'COMPLETED', 'PAID'], true);

        if (! $success) {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $prn,
                failureReason: 'Payment not successful.',
                payload: $body,
            );
        }

        // Fail-closed: gateway must return amount matching payment.amount.
        if (! array_key_exists('amount', $body) || $body['amount'] === null || $body['amount'] === '') {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $prn,
                failureReason: 'Amount missing from gateway response.',
                payload: $body,
            );
        }

        $reported = number_format((float) $body['amount'], 2, '.', '');
        $expected = number_format((float) $payment->amount, 2, '.', '');
        if ($reported !== $expected) {
            return new VerifyResult(
                ok: false,
                idempotencyKey: $prn,
                failureReason: 'Amount mismatch.',
                payload: $body,
            );
        }

        return new VerifyResult(
            ok: true,
            idempotencyKey: $prn,
            gatewayReference: (string) ($body['traceId'] ?? $body['transactionId'] ?? $prn),
            payload: $body,
        );
    }
}
