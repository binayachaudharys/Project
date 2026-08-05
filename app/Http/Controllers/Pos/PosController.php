<?php

namespace App\Http\Controllers\Pos;

use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosCheckoutRequest;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\PackageRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SaleRepository;
use App\Repositories\ServiceRepository;
use App\Services\Payments\GatewayRedirect;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\QrPayload;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(ServiceRepository $services, PackageRepository $packages, ProductRepository $products): Response
    {
        return Inertia::render('Pos/Index', [
            'services' => $services->allActive(),
            'packages' => $packages->allActive(),
            'products' => $products->allActive(),
        ]);
    }

    /**
     * Phone search for existing customers, used by the POS customer picker.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $phone = trim((string) $request->query('phone', ''));

        $customers = User::query()
            ->where('role', UserRole::Customer->value)
            ->when($phone !== '', fn ($query) => $query->where('phone', 'like', "%{$phone}%"))
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone']);

        return response()->json($customers);
    }

    public function checkout(
        PosCheckoutRequest $request,
        SaleRepository $sales,
        PaymentManager $payments,
    ): RedirectResponse {
        try {
            $sale = $sales->checkout([
                ...$request->validated(),
                'staff_id' => $request->user()->id,
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (ModelNotFoundException) {
            return back()->withErrors(['items' => 'One or more items are no longer available.']);
        }

        $method = PaymentMethod::from($request->validated('payment_method'));

        if ($method === PaymentMethod::Cash) {
            return redirect()->route('pos.index')->with('success', 'Sale completed.');
        }

        $payment = $sale->payments()->latest('id')->first();
        if ($payment === null) {
            return redirect()->route('pos.index')->with('success', 'Sale pending payment.');
        }

        try {
            $initiated = $payments->gateway($method->value)->initiate($sale, $payment);
        } catch (\Throwable $e) {
            Log::info('payment.initiate.failed', [
                'method' => $method->value,
                'sale_id' => $sale->id,
                'message' => $e->getMessage(),
            ]);

            return redirect()->route('pos.index')->with([
                'success' => 'Sale pending payment. Use staff confirm if the wallet already paid.',
                'pending_sale_id' => $sale->id,
            ]);
        }

        if ($initiated instanceof GatewayRedirect && strtoupper($initiated->method) === 'GET' && $initiated->params === []) {
            return redirect()->away($initiated->url);
        }

        $payload = $initiated instanceof QrPayload
            ? [
                'type' => 'qr',
                'qr_data' => $initiated->qrData,
                'meta' => $initiated->meta,
                'url' => null,
                'method' => null,
                'params' => [],
            ]
            : [
                'type' => 'redirect',
                'url' => $initiated->url,
                'method' => $initiated->method,
                'params' => $initiated->params,
                'qr_data' => null,
                'meta' => [],
            ];

        return redirect()->route('pos.index')->with([
            'success' => 'Sale pending payment.',
            'payment_initiate' => [
                ...$payload,
                'sale_id' => $sale->id,
                'method' => $method->value,
            ],
        ]);
    }

    public function void(Request $request, Sale $sale, SaleRepository $sales): RedirectResponse
    {
        try {
            $sales->voidPaidSale($sale, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Sale voided.');
    }
}
