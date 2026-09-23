<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $sales = Sale::query()
            ->whereIn('status', [SaleStatus::PendingPayment->value, SaleStatus::Paid->value])
            ->with([
                'customer:id,name,phone',
                'staff:id,name',
                'appointment:id,starts_at,status,bookable_type,bookable_id',
                'appointment.bookable',
                'items',
                'payments',
            ])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $isOwner = $request->user()?->role === UserRole::Owner;

        return Inertia::render($isOwner ? 'Admin/Billing/Index' : 'Staff/Billing', [
            'sales' => $sales,
            'markPaidRoute' => $isOwner ? 'admin.billing.mark-paid' : 'staff.billing.mark-paid',
            'invoiceRoute' => $isOwner ? 'admin.billing.invoice' : 'staff.billing.invoice',
        ]);
    }

    public function markPaid(Request $request, Sale $sale, PaymentManager $payments): RedirectResponse
    {
        try {
            $payments->markPaidAtDesk($sale, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', 'Payment marked as paid.');
    }

    public function invoice(Request $request, Sale $sale): Response
    {
        abort_unless(
            in_array($sale->status, [SaleStatus::PendingPayment, SaleStatus::Paid], true),
            404,
        );

        $sale->load([
            'customer:id,name,phone',
            'staff:id,name',
            'appointment:id,starts_at,status,bookable_type,bookable_id',
            'appointment.bookable',
            'items',
            'payments',
        ]);

        $isOwner = $request->user()?->role === UserRole::Owner;

        return Inertia::render('Billing/Invoice', [
            'sale' => $sale,
            'salon' => [
                'name' => config('salon.name'),
                'address' => config('salon.address'),
                'phone' => config('salon.phone'),
                'logo' => config('salon.logo'),
                'tagline' => config('salon.tagline'),
            ],
            'backRoute' => $isOwner ? 'admin.billing.index' : 'staff.billing.index',
        ]);
    }
}
