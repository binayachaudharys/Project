<?php

namespace App\Http\Controllers\Pos;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\PosCheckoutRequest;
use App\Models\Sale;
use App\Models\User;
use App\Repositories\PackageRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SaleRepository;
use App\Repositories\ServiceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function checkout(PosCheckoutRequest $request, SaleRepository $sales): RedirectResponse
    {
        try {
            $sales->checkout([
                ...$request->validated(),
                'staff_id' => $request->user()->id,
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (ModelNotFoundException) {
            return back()->withErrors(['items' => 'One or more items are no longer available.']);
        }

        return redirect()->route('pos.index')->with('success', 'Sale completed.');
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
