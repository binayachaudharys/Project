import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const cards = [
    { href: 'admin.services.index', label: 'Services', desc: 'Manage service catalog' },
    { href: 'admin.packages.index', label: 'Packages', desc: 'Bundles and service pivots' },
    { href: 'admin.products.index', label: 'Products', desc: 'Retail + stock adjusts' },
    { href: 'admin.staff.index', label: 'Staff', desc: 'Create staff users' },
    { href: 'admin.sales.index', label: 'Sales report', desc: 'Paid sales by date range' },
    { href: 'admin.settings.edit', label: 'Settings', desc: 'Hours and booking limits' },
];

export default function Dashboard() {
    return (
        <AdminLayout title="Owner console">
            <Head title="Admin" />
            <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                Dashboard
            </h1>
            <p className="mt-2 text-charcoal-500">
                Catalog, stock, staff, reports, and salon settings.
            </p>
            <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {cards.map((card) => (
                    <li key={card.href}>
                        <Link
                            href={route(card.href)}
                            className="block rounded-2xl border border-rose-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <h2 className="font-display text-xl font-semibold text-charcoal-900">
                                {card.label}
                            </h2>
                            <p className="mt-2 text-sm text-charcoal-500">{card.desc}</p>
                        </Link>
                    </li>
                ))}
            </ul>
            <p className="mt-8 text-sm text-charcoal-500">
                <a
                    href="/log-viewer"
                    className="font-medium text-rose-600 hover:text-rose-700"
                >
                    Open Log Viewer
                </a>{' '}
                (owner only)
            </p>
        </AdminLayout>
    );
}
