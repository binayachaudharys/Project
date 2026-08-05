import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function PackagesIndex({ packages }) {
    return (
        <PublicLayout>
            <Head title="Packages" />

            <section className="mx-auto max-w-6xl px-6 py-16">
                <header className="max-w-2xl">
                    <p className="text-sm font-semibold uppercase tracking-[0.25em] text-rose-500">
                        Curated Bundles
                    </p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-charcoal-900">
                        Packages
                    </h1>
                    <p className="mt-4 text-charcoal-500">
                        Save more with combined treatments, designed for
                        your favourite look.
                    </p>
                </header>

                {packages.length === 0 ? (
                    <p className="mt-12 text-charcoal-500">
                        Packages are being updated. Please check back soon.
                    </p>
                ) : (
                    <ul className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {packages.map((pkg) => (
                            <li key={pkg.id}>
                                <Link
                                    href={route('packages.show', pkg.id)}
                                    className="block h-full rounded-2xl border border-rose-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                                >
                                    <h2 className="font-display text-xl font-semibold text-charcoal-900">
                                        {pkg.name}
                                    </h2>
                                    {pkg.description && (
                                        <p className="mt-2 text-sm text-charcoal-500">
                                            {pkg.description}
                                        </p>
                                    )}
                                    {pkg.services?.length > 0 && (
                                        <p className="mt-4 text-xs uppercase tracking-wide text-charcoal-300">
                                            Includes{' '}
                                            {pkg.services
                                                .map((s) => s.name)
                                                .join(', ')}
                                        </p>
                                    )}
                                    <div className="mt-6 font-display text-lg font-semibold text-charcoal-900">
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
