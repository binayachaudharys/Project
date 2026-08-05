import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function SalesIndex({ sales, total, filters }) {
    const [from, setFrom] = useState(filters.from);
    const [to, setTo] = useState(filters.to);

    const apply = (e) => {
        e.preventDefault();
        router.get(route('admin.sales.index'), { from, to }, { preserveState: true });
    };

    return (
        <AdminLayout title="Sales report">
            <Head title="Admin · Sales" />
            <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                Sales report
            </h1>
            <p className="mt-2 text-charcoal-500">Paid sales only, summed for the range.</p>

            <form onSubmit={apply} className="mt-6 flex flex-wrap items-end gap-4">
                <label className="text-sm">
                    <span className="text-charcoal-500">From</span>
                    <input
                        type="date"
                        className="mt-1 block rounded-md border-gray-300 shadow-sm"
                        value={from}
                        onChange={(e) => setFrom(e.target.value)}
                        required
                    />
                </label>
                <label className="text-sm">
                    <span className="text-charcoal-500">To</span>
                    <input
                        type="date"
                        className="mt-1 block rounded-md border-gray-300 shadow-sm"
                        value={to}
                        onChange={(e) => setTo(e.target.value)}
                        required
                    />
                </label>
                <button
                    type="submit"
                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 hover:bg-rose-600"
                >
                    Filter
                </button>
            </form>

            <p className="mt-6 font-display text-2xl font-semibold text-charcoal-900">
                Total: {formatPrice(total)}
            </p>

            <div className="mt-6 overflow-hidden rounded-2xl border border-rose-100 bg-white">
                <table className="min-w-full divide-y divide-rose-100 text-sm">
                    <thead className="bg-blush-50 text-left text-charcoal-500">
                        <tr>
                            <th className="px-4 py-3 font-medium">Sale #</th>
                            <th className="px-4 py-3 font-medium">Date</th>
                            <th className="px-4 py-3 font-medium">Customer</th>
                            <th className="px-4 py-3 font-medium">Staff</th>
                            <th className="px-4 py-3 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-rose-50">
                        {sales.map((sale) => (
                            <tr key={sale.id}>
                                <td className="px-4 py-3 font-medium">{sale.sale_number}</td>
                                <td className="px-4 py-3">
                                    {new Date(sale.created_at).toLocaleString()}
                                </td>
                                <td className="px-4 py-3">{sale.customer?.name ?? 'Walk-in'}</td>
                                <td className="px-4 py-3">{sale.staff?.name ?? '—'}</td>
                                <td className="px-4 py-3">{formatPrice(sale.total)}</td>
                            </tr>
                        ))}
                        {sales.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-charcoal-500">
                                    No paid sales in this range.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
