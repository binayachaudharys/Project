import { Link, usePage } from '@inertiajs/react';

const links = [
    { href: 'admin.dashboard', label: 'Dashboard' },
    { href: 'admin.services.index', label: 'Services' },
    { href: 'admin.packages.index', label: 'Packages' },
    { href: 'admin.products.index', label: 'Products' },
    { href: 'admin.staff.index', label: 'Staff' },
    { href: 'admin.sales.index', label: 'Sales' },
    { href: 'admin.settings.edit', label: 'Settings' },
];

export default function AdminLayout({ title, children }) {
    const { auth, flash } = usePage().props;

    return (
        <div className="min-h-screen bg-blush-50 font-sans text-charcoal-700">
            <header className="border-b border-rose-100 bg-white/90 backdrop-blur">
                <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div>
                        <Link
                            href={route('admin.dashboard')}
                            className="font-display text-xl font-semibold text-charcoal-900"
                        >
                            Pretty Girls{' '}
                            <span className="text-rose-500">Admin</span>
                        </Link>
                        {title && (
                            <p className="mt-0.5 text-sm text-charcoal-500">{title}</p>
                        )}
                    </div>
                    <nav className="flex flex-wrap items-center gap-3 text-sm">
                        {links.map((link) => (
                            <Link
                                key={link.href}
                                href={route(link.href)}
                                className={
                                    route().current(link.href) ||
                                    route().current(link.href.replace('.index', '.*')) ||
                                    route().current(link.href.replace('.edit', '.*'))
                                        ? 'font-semibold text-rose-600'
                                        : 'text-charcoal-500 transition hover:text-rose-500'
                                }
                            >
                                {link.label}
                            </Link>
                        ))}
                        <span className="text-charcoal-300">|</span>
                        <span className="text-charcoal-500">{auth?.user?.name}</span>
                        <Link
                            href={route('home')}
                            className="text-charcoal-500 transition hover:text-rose-500"
                        >
                            Site
                        </Link>
                    </nav>
                </div>
            </header>

            <main className="mx-auto max-w-6xl px-6 py-8">
                {flash?.success && (
                    <div className="mb-6 rounded-xl border border-rose-100 bg-white px-4 py-3 text-sm text-rose-700">
                        {flash.success}
                    </div>
                )}
                {children}
            </main>
        </div>
    );
}
