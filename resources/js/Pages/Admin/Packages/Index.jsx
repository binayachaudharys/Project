import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function PackagesIndex({ packages }) {
    const destroy = (id) => {
        if (confirm('Delete this package?')) {
            router.delete(route('admin.packages.destroy', id));
        }
    };

    return (
        <AdminLayout title="Packages">
            <Head title="Admin · Packages" />
            <div className="flex items-center justify-between gap-4">
                <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                    Packages
                </h1>
                <Link
                    href={route('admin.packages.create')}
                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 hover:bg-rose-600"
                >
                    Add package
                </Link>
            </div>

            <div className="mt-8 overflow-hidden rounded-2xl border border-rose-100 bg-white">
                <table className="min-w-full divide-y divide-rose-100 text-sm">
                    <thead className="bg-blush-50 text-left text-charcoal-500">
                        <tr>
                            <th className="px-4 py-3 font-medium">Name</th>
                            <th className="px-4 py-3 font-medium">Services</th>
                            <th className="px-4 py-3 font-medium">Price</th>
                            <th className="px-4 py-3 font-medium">Active</th>
                            <th className="px-4 py-3 font-medium" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-rose-50">
                        {packages.map((pkg) => (
                            <tr key={pkg.id}>
                                <td className="px-4 py-3 font-medium text-charcoal-900">
                                    {pkg.name}
                                </td>
                                <td className="px-4 py-3 text-charcoal-500">
                                    {(pkg.services || []).map((s) => s.name).join(', ') || '—'}
                                </td>
                                <td className="px-4 py-3">{formatPrice(pkg.price)}</td>
                                <td className="px-4 py-3">{pkg.is_active ? 'Yes' : 'No'}</td>
                                <td className="px-4 py-3 text-right space-x-3">
                                    <Link
                                        href={route('admin.packages.edit', pkg.id)}
                                        className="text-rose-600 hover:text-rose-700"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => destroy(pkg.id)}
                                        className="text-charcoal-400 hover:text-charcoal-700"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                        {packages.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-charcoal-500">
                                    No packages yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
