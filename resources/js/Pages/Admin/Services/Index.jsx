import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function ServicesIndex({ services }) {
    const destroy = (id) => {
        if (confirm('Delete this service?')) {
            router.delete(route('admin.services.destroy', id));
        }
    };

    return (
        <AdminLayout title="Services">
            <Head title="Admin · Services" />
            <div className="flex items-center justify-between gap-4">
                <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                    Services
                </h1>
                <Link
                    href={route('admin.services.create')}
                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 hover:bg-rose-600"
                >
                    Add service
                </Link>
            </div>

            <div className="mt-8 overflow-hidden rounded-2xl border border-rose-100 bg-white">
                <table className="min-w-full divide-y divide-rose-100 text-sm">
                    <thead className="bg-blush-50 text-left text-charcoal-500">
                        <tr>
                            <th className="px-4 py-3 font-medium">Name</th>
                            <th className="px-4 py-3 font-medium">Duration</th>
                            <th className="px-4 py-3 font-medium">Price</th>
                            <th className="px-4 py-3 font-medium">Active</th>
                            <th className="px-4 py-3 font-medium" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-rose-50">
                        {services.map((service) => (
                            <tr key={service.id}>
                                <td className="px-4 py-3 font-medium text-charcoal-900">
                                    {service.name}
                                </td>
                                <td className="px-4 py-3">{service.duration_minutes} min</td>
                                <td className="px-4 py-3">{formatPrice(service.price)}</td>
                                <td className="px-4 py-3">
                                    {service.is_active ? 'Yes' : 'No'}
                                </td>
                                <td className="px-4 py-3 text-right space-x-3">
                                    <Link
                                        href={route('admin.services.edit', service.id)}
                                        className="text-rose-600 hover:text-rose-700"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => destroy(service.id)}
                                        className="text-charcoal-400 hover:text-charcoal-700"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                        {services.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-charcoal-500">
                                    No services yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
