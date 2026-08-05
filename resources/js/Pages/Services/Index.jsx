import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function ServicesIndex({ services }) {
    return (
        <PublicLayout>
            <Head title="Services" />

            <section className="mx-auto max-w-6xl px-6 py-16">
                <header className="max-w-2xl">
                    <p className="text-sm font-semibold uppercase tracking-[0.25em] text-rose-500">
                        Our Menu
                    </p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-charcoal-900">
                        Services
                    </h1>
                    <p className="mt-4 text-charcoal-500">
                        Every treatment is performed by trained stylists
                        using quality products, tailored to you.
                    </p>
                </header>

                {services.length === 0 ? (
                    <p className="mt-12 text-charcoal-500">
                        Services are being updated. Please check back soon.
                    </p>
                ) : (
                    <ul className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {services.map((service) => (
                            <li
                                key={service.id}
                                className="rounded-2xl border border-rose-100 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                            >
                                <h2 className="font-display text-xl font-semibold text-charcoal-900">
                                    {service.name}
                                </h2>
                                {service.description && (
                                    <p className="mt-2 text-sm text-charcoal-500">
                                        {service.description}
                                    </p>
                                )}
                                <div className="mt-6 flex items-center justify-between text-sm">
                                    <span className="rounded-full bg-blush-100 px-3 py-1 font-medium text-rose-600">
                                        {service.duration_minutes} min
                                    </span>
                                    <span className="font-display text-lg font-semibold text-charcoal-900">
                                        {formatPrice(service.price)}
                                    </span>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </PublicLayout>
    );
}
