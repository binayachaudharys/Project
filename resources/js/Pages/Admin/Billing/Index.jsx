import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

function formatWhen(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export default function BillingIndex({
    sales,
    markPaidRoute = 'admin.billing.mark-paid',
    invoiceRoute = 'admin.billing.invoice',
}) {
    function markPaid(sale) {
        if (!confirm(`Mark ${sale.sale_number} as paid?`)) {
            return;
        }

        router.post(route(markPaidRoute, sale.id), {}, {
            preserveScroll: true,
        });
    }

    return (
        <AdminLayout title="Billing">
            <Head title="Admin · Billing" />

            <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                Billing
            </h1>
            <div className="mt-2 flex flex-wrap items-center justify-between gap-3">
                <p className="text-charcoal-500">
                    Appointment bills and walk-in invoices. Use New invoice to
                    add items to a cart with Nepal VAT.
                </p>
                <Link
                    href={route('pos.index')}
                    className="inline-flex rounded-full bg-rose-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-600"
                >
                    New invoice / add to cart
                </Link>
            </div>

            <div className="mt-8 overflow-x-auto rounded-2xl border border-rose-100 bg-white">
                <table className="min-w-full divide-y divide-rose-100 text-sm">
                    <thead className="bg-blush-50 text-left text-charcoal-500">
                        <tr>
                            <th className="px-4 py-3 font-medium">Bill #</th>
                            <th className="px-4 py-3 font-medium">Source</th>
                            <th className="px-4 py-3 font-medium">Customer</th>
                            <th className="px-4 py-3 font-medium">Items</th>
                            <th className="px-4 py-3 font-medium">Appointment</th>
                            <th className="px-4 py-3 font-medium">VAT</th>
                            <th className="px-4 py-3 font-medium">Total</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-rose-50">
                        {(sales ?? []).length === 0 ? (
                            <tr>
                                <td
                                    colSpan={9}
                                    className="px-4 py-8 text-center text-charcoal-500"
                                >
                                    No bills yet. Confirm a booking or create a
                                    new invoice.
                                </td>
                            </tr>
                        ) : (
                            sales.map((sale) => (
                                <tr key={sale.id}>
                                    <td className="px-4 py-3 font-medium">
                                        {sale.sale_number}
                                    </td>
                                    <td className="px-4 py-3 text-charcoal-500">
                                        {sale.appointment_id
                                            ? 'Appointment'
                                            : 'Walk-in'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {sale.customer?.name ?? 'Walk-in'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {sale.appointment?.bookable?.name ??
                                            sale.items
                                                ?.map((item) => item.name_snapshot)
                                                .filter(Boolean)
                                                .join(', ') ??
                                            '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatWhen(
                                            sale.appointment?.starts_at,
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatPrice(sale.tax ?? 0)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatPrice(sale.total)}
                                    </td>
                                    <td className="px-4 py-3 capitalize">
                                        {String(sale.status).replace('_', ' ')}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={route(
                                                    invoiceRoute,
                                                    sale.id,
                                                )}
                                                className="rounded border border-charcoal-100 px-3 py-1.5 text-xs font-semibold text-charcoal-700 hover:border-rose-300 hover:text-rose-600"
                                            >
                                                Print
                                            </Link>
                                            {sale.status ===
                                            'pending_payment' ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        markPaid(sale)
                                                    }
                                                    className="rounded bg-rose-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-600"
                                                >
                                                    Mark paid
                                                </button>
                                            ) : (
                                                <span className="text-xs text-emerald-700">
                                                    Paid
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
