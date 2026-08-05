<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffConfirmPaymentController extends Controller
{
    public function store(Request $request, Sale $sale, PaymentManager $payments): RedirectResponse
    {
        $validated = $request->validate([
            'method' => [
                'required',
                Rule::in([
                    PaymentMethod::Esewa->value,
                    PaymentMethod::Khalti->value,
                    PaymentMethod::Fonepay->value,
                ]),
            ],
            'gateway_reference' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $payments->staffConfirm(
                $sale,
                PaymentMethod::from($validated['method']),
                $validated['gateway_reference'] ?? null,
                $request->user(),
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('pos.index')->with('success', 'Payment confirmed.');
    }
}
