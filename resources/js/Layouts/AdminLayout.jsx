import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const navGroups = [
    {
        label: 'Desk',
        links: [
            { href: 'admin.dashboard', label: 'Dashboard' },
            { href: 'admin.bookings.index', label: 'Bookings' },
            { href: 'admin.billing.index', label: 'Billing' },
            { href: 'pos.index', label: 'New invoice' },
            { href: 'admin.sales.index', label: 'Sales' },
        ],
    },
    {
        label: 'Catalog',
        links: [
            { href: 'admin.services.index', label: 'Services' },
            { href: 'admin.packages.index', label: 'Packages' },
            { href: 'admin.products.index', label: 'Products' },
        ],
    },
    {
        label: 'Salon',
        links: [
            { href: 'admin.staff.index', label: 'Staff' },
            { href: 'admin.settings.edit', label: 'Settings' },
        ],
    },
];

function linkIsActive(href) {
    if (route().current(href)) {
        return true;
    }

    if (href.endsWith('.index')) {
        const prefix = href.replace(/\.index$/, '');
        return route().current(`${prefix}.*`);
    }

    if (href.endsWith('.edit')) {
        const prefix = href.replace(/\.edit$/, '');
        return route().current(`${prefix}.*`);
    }

    return false;
}

export default function AdminLayout({ title, children }) {
    const { auth, flash } = usePage().props;
    const [open, setOpen] = useState(false);

    const navClass = (href) =>
        linkIsActive(href)
            ? 'bg-rose-50 font-semibold text-rose-700'
            : 'text-charcoal-600 hover:bg-blush-100 hover:text-rose-600';

    return (
        <div className="min-h-screen bg-blush-50 font-sans text-charcoal-700 lg:flex">
            {open ? (
                <button
                    type="button"
                    className="fixed inset-0 z-30 bg-charcoal-900/40 lg:hidden"
                    aria-label="Close menu"
                    onClick={() => setOpen(false)}
                />
            ) : null}

            <aside
                className={`fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-rose-100 bg-white transition-transform lg:static lg:translate-x-0 ${
                    open ? 'translate-x-0' : '-translate-x-full'
                }`}
                aria-label="Admin"
            >
                <div className="border-b border-rose-100 px-5 py-5">
                    <Link
                        href={route('admin.dashboard')}
                        className="font-display text-xl font-semibold text-charcoal-900"
                        onClick={() => setOpen(false)}
                    >
                        Pretty Girls{' '}
                        <span className="text-rose-500">Admin</span>
                    </Link>
                    {title ? (
                        <p className="mt-1 text-xs text-charcoal-500">{title}</p>
                    ) : null}
                </div>

                <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                    {navGroups.map((group) => (
                        <div key={group.label}>
                            <p className="px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-charcoal-300">
                                {group.label}
                            </p>
                            <ul className="mt-2 space-y-0.5">
                                {group.links.map((link) => (
                                    <li key={link.href}>
                                        <Link
                                            href={route(link.href)}
                                            className={`block rounded-lg px-3 py-2 text-sm transition ${navClass(link.href)}`}
                                            onClick={() => setOpen(false)}
                                        >
                                            {link.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </nav>

                <div className="space-y-2 border-t border-rose-100 px-4 py-4 text-sm">
                    <p className="truncate text-charcoal-500">
                        {auth?.user?.name}
                    </p>
                    <Link
                        href={route('home')}
                        className="block text-charcoal-500 transition hover:text-rose-500"
                    >
                        View site
                    </Link>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="w-full rounded-lg bg-rose-500 px-3 py-2 text-left text-white transition hover:bg-rose-600"
                    >
                        Log out
                    </Link>
                </div>
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-20 flex items-center gap-3 border-b border-rose-100 bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
                    <button
                        type="button"
                        className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-charcoal-100 text-charcoal-700"
                        aria-expanded={open}
                        aria-label={open ? 'Close menu' : 'Open menu'}
                        onClick={() => setOpen((value) => !value)}
                    >
                        <span className="sr-only">Menu</span>
                        <span className="flex flex-col gap-1.5" aria-hidden="true">
                            <span className="block h-0.5 w-5 bg-current" />
                            <span className="block h-0.5 w-5 bg-current" />
                            <span className="block h-0.5 w-5 bg-current" />
                        </span>
                    </button>
                    <div>
                        <p className="font-display text-lg font-semibold text-charcoal-900">
                            Admin
                        </p>
                        {title ? (
                            <p className="text-xs text-charcoal-500">{title}</p>
                        ) : null}
                    </div>
                </header>

                <main
                    id="main-content"
                    tabIndex={-1}
                    className="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8"
                >
                    {flash?.success ? (
                        <div className="mb-6 rounded-xl border border-rose-100 bg-white px-4 py-3 text-sm text-rose-700">
                            {flash.success}
                        </div>
                    ) : null}
                    {children}
                </main>
            </div>
        </div>
    );
}
