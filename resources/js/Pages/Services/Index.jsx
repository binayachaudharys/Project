import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';

function formatServicePrice(service) {
    const value = Number(service.price);
    if (!Number.isFinite(value) || value <= 0) {
        if (service.description?.toLowerCase().includes('consult')) {
            return 'Consult';
        }
        if (service.description?.includes('%')) {
            return 'Offer — ask in salon';
        }

        return 'Ask in salon';
    }

    return `Rs. ${value.toLocaleString('en-IN')}`;
}

export default function ServicesIndex({ services }) {
    const { auth } = usePage().props;
    const bookHref = auth?.user ? route('book.create') : route('login');

    const groups = useMemo(() => {
        const map = new Map();
        services.forEach((service) => {
            const key = service.category || 'Services';
            if (!map.has(key)) {
                map.set(key, []);
            }
            map.get(key).push(service);
        });
        return Array.from(map.entries());
    }, [services]);

    return (
        <PublicLayout>
            <Head title="Services" />

            <section className="bg-blush-100 px-6 py-16">
                <header className="mx-auto max-w-2xl text-center">
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-rose-500">
                        Our Menu
                    </p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-charcoal-900 sm:text-5xl">
                        Services
                    </h1>
                    <p className="mt-4 text-charcoal-500">
                        Full flyer menu — book online or ask us on WhatsApp.
                    </p>
                </header>
            </section>

            <section className="mx-auto max-w-4xl px-6 py-16">
                {services.length === 0 ? (
                    <p className="text-center text-charcoal-500">
                        Services are being updated. Please check back soon.
                    </p>
                ) : (
                    <div className="space-y-12">
                        {groups.map(([category, items]) => (
                            <div key={category}>
                                <h2 className="font-display text-2xl font-semibold text-charcoal-900">
                                    {category}
                                </h2>
                                <ul className="mt-4 divide-y divide-charcoal-50 border-y border-charcoal-50">
                                    {items.map((service) => (
                                        <li
                                            key={service.id}
                                            className="flex flex-col gap-2 py-5 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div>
                                                <h3 className="font-medium text-charcoal-900">
                                                    {service.name}
                                                </h3>
                                                {service.description ? (
                                                    <p className="mt-1 text-sm text-charcoal-500">
                                                        {service.description}
                                                    </p>
                                                ) : null}
                                            </div>
                                            <div className="flex shrink-0 items-center gap-6 text-sm">
                                                <span className="text-charcoal-300">
                                                    {service.duration_minutes}{' '}
                                                    min
                                                </span>
                                                <span className="font-semibold text-rose-600">
                                                    {formatServicePrice(
                                                        service,
                                                    )}
                                                </span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))}
                    </div>
                )}

                <div className="mt-12 text-center">
                    <Link
                        href={bookHref}
                        className="inline-flex bg-rose-500 px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-rose-600"
                    >
                        Book Now
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
