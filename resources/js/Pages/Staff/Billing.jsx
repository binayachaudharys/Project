import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

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

export default function StaffBilling({
    sales,
    markPaidRoute = 'staff.billing.mark-paid',
    invoiceRoute = 'staff.billing.invoice',
}) {
    const { flash } = usePage().props;

    function markPaid(sale) {
        if (!confirm(`Mark ${sale.sale_number} as paid?`)) {
            return;
        }

        router.post(route(markPaidRoute, sale.id), {}, {
            preserveScroll: true,
        });
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold text-gray-800">Billing</h2>
            }
        >
            <Head title="Billing" />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                {flash?.success ? (
                    <p className="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {flash.success}
                    </p>
                ) : null}

                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p className="text-sm text-gray-600">
                        Confirm payment received, then mark the bill as paid.
                        Use New invoice to build a cart with VAT.
                    </p>
                    <div className="flex flex-wrap gap-3 text-sm">
                        <Link
                            href={route('pos.index')}
                            className="rounded-full bg-rose-500 px-4 py-2 font-semibold text-white hover:bg-rose-600"
                        >
                            New invoice / add to cart
                        </Link>
                        <Link
                            href={route('staff.today')}
                            className="text-rose-600"
                        >
                            Back to today
                        </Link>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg bg-white shadow">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-left text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-medium">Bill #</th>
                                <th className="px-4 py-3 font-medium">Source</th>
                                <th className="px-4 py-3 font-medium">Customer</th>
                                <th className="px-4 py-3 font-medium">Items</th>
                                <th className="px-4 py-3 font-medium">VAT</th>
                                <th className="px-4 py-3 font-medium">Total</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {(sales ?? []).length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-4 py-8 text-center text-gray-500"
                                    >
                                        No bills yet. Confirm a booking or
                                        create a new invoice.
                                    </td>
                                </tr>
                            ) : (
                                sales.map((sale) => (
                                    <tr key={sale.id}>
                                        <td className="px-4 py-3 font-medium">
                                            {sale.sale_number}
                                        </td>
                                        <td className="px-4 py-3 text-gray-500">
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
                                                    ?.map(
                                                        (item) =>
                                                            item.name_snapshot,
                                                    )
                                                    .filter(Boolean)
                                                    .join(', ') ??
                                                '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            {formatPrice(sale.tax ?? 0)}
                                        </td>
                                        <td className="px-4 py-3">
                                            {formatPrice(sale.total)}
                                        </td>
                                        <td className="px-4 py-3 capitalize">
                                            {String(sale.status).replace(
                                                '_',
                                                ' ',
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Link
                                                    href={route(
                                                        invoiceRoute,
                                                        sale.id,
                                                    )}
                                                    className="rounded border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-rose-300 hover:text-rose-600"
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
            </div>
        </AuthenticatedLayout>
    );
}
