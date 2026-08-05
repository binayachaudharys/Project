import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function PackagesShow({ package: pkg }) {
    return (
        <PublicLayout>
            <Head title={pkg.name} />

            <section className="mx-auto max-w-4xl px-6 py-16">
                <Link
                    href={route('packages.index')}
                    className="text-sm font-medium text-rose-500 hover:text-rose-600"
                >
                    &larr; Back to Packages
                </Link>

                <header className="mt-6">
                    <h1 className="font-display text-4xl font-semibold text-charcoal-900">
                        {pkg.name}
                    </h1>
                    {pkg.description && (
                        <p className="mt-4 text-lg text-charcoal-500">
                            {pkg.description}
                        </p>
                    )}
                </header>

                <div className="mt-10 rounded-2xl border border-rose-100 bg-white p-8 shadow-sm">
                    <h2 className="font-display text-lg font-semibold text-charcoal-900">
                        What's included
                    </h2>
                    <ul className="mt-4 space-y-3">
                        {pkg.services.map((service) => (
                            <li
                                key={service.id}
                                className="flex items-center justify-between rounded-xl bg-blush-50 px-4 py-3 text-sm"
                            >
                                <span className="font-medium text-charcoal-700">
                                    {service.name}
                                </span>
                                {service.duration_minutes && (
                                    <span className="text-charcoal-500">
                                        {service.duration_minutes} min
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>

                    <div className="mt-8 flex items-center justify-between border-t border-rose-100 pt-6">
                        <span className="text-sm font-medium uppercase tracking-wide text-charcoal-300">
                            Package price
                        </span>
                        <span className="font-display text-2xl font-semibold text-rose-600">
                            {formatPrice(pkg.price)}
                        </span>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
