<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request, string $method, PaymentManager $payments): Response|JsonResponse
    {
        try {
            $gateway = $payments->gateway($method);
        } catch (InvalidArgumentException) {
            return response()->json(['ok' => false, 'message' => 'Unsupported payment method.'], 404);
        }

        $result = $gateway->verify($request);
        $sale = $payments->markPaidFromVerify($result);

        if ($result->ok) {
            return response()->json([
                'ok' => true,
                'sale_id' => $sale?->id,
                'status' => $sale?->status?->value,
            ]);
        }

        return response()->json([
            'ok' => false,
            'sale_id' => $sale?->id,
            'message' => $result->failureReason ?? 'Payment verification failed.',
        ]);
    }
}
