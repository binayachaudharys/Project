import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';
import PackageMenu from '@/Components/PackageMenu';
import PublicLayout from '@/Layouts/PublicLayout';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function PackagesIndex({ packages, menu = [] }) {
    useEffect(() => {
        const hash = window.location.hash?.slice(1);
        if (!hash) {
            return undefined;
        }

        const timer = window.setTimeout(() => {
            document.getElementById(hash)?.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }, 50);

        return () => window.clearTimeout(timer);
    }, []);

    return (
        <PublicLayout>
            <Head title="Packages" />

            <section className="bg-blush-100 py-16">
                <PackageMenu menu={menu} />
            </section>

            <section className="mx-auto max-w-6xl px-6 py-16">
                <header className="mx-auto max-w-2xl text-center">
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-rose-500">
                        Bookable Bundles
                    </p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-charcoal-900">
                        Online packages
                    </h1>
                    <p className="mt-4 text-charcoal-500">
                        Reserve these bundles through the app when they are
                        activated by the salon.
                    </p>
                </header>

                {packages.length === 0 ? (
                    <p className="mt-12 text-center text-charcoal-500">
                        Online packages are being updated. Browse the menu
                        above or check back soon.
                    </p>
                ) : (
                    <ul className="mt-12 divide-y divide-charcoal-50 border-y border-charcoal-50">
                        {packages.map((pkg) => (
                            <li key={pkg.id}>
                                <Link
                                    href={route('packages.show', pkg.id)}
                                    className="flex flex-col gap-2 py-6 transition hover:bg-blush-100/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <h2 className="font-display text-xl font-semibold text-charcoal-900">
                                            {pkg.name}
                                        </h2>
                                        {pkg.description ? (
                                            <p className="mt-2 text-sm text-charcoal-500">
                                                {pkg.description}
                                            </p>
                                        ) : null}
                                        {pkg.services?.length > 0 ? (
                                            <p className="mt-2 text-xs uppercase tracking-wide text-charcoal-300">
                                                Includes{' '}
                                                {pkg.services
                                                    .map((s) => s.name)
                                                    .join(', ')}
                                            </p>
                                        ) : null}
                                    </div>
                                    <div className="shrink-0 font-semibold text-rose-600">
                                        {formatPrice(pkg.price)}
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </PublicLayout>
    );
}
