<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function methodName(): string;

    public function initiate(Sale $sale, Payment $payment): GatewayRedirect|QrPayload;

    public function verify(Request $request): VerifyResult;
}
