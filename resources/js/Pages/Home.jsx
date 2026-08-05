import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Home({ salonName }) {
    return (
        <PublicLayout>
            <Head title="Home" />

            <section className="relative isolate overflow-hidden">
                <div
                    className="absolute inset-0 -z-10 bg-gradient-to-br from-rose-500 via-rose-400 to-charcoal-900"
                    aria-hidden="true"
                />
                <div
                    className="absolute inset-0 -z-10 opacity-20 [background-image:radial-gradient(circle_at_20%_20%,white,transparent_35%),radial-gradient(circle_at_80%_0%,white,transparent_30%),radial-gradient(circle_at_50%_100%,white,transparent_40%)]"
                    aria-hidden="true"
                />

                <div className="mx-auto flex max-w-6xl flex-col items-center px-6 py-28 text-center sm:py-36">
                    <span className="rounded-full bg-blush-50/15 px-4 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-blush-50">
                        {salonName}
                    </span>

                    <h1 className="mt-8 max-w-3xl font-display text-4xl font-semibold leading-tight text-blush-50 sm:text-6xl">
                        Beauty, tended to with care.
                    </h1>

                    <p className="mt-6 max-w-xl text-lg text-blush-100/90">
                        Book expert hair, skin, and glow services at your
                        neighbourhood salon &mdash; simple booking, warm
                        care, no surprises.
                    </p>

                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href={route('services.index')}
                            className="rounded-full bg-blush-50 px-8 py-3 text-sm font-semibold text-rose-600 shadow-lg shadow-charcoal-900/20 transition hover:bg-blush-100"
                        >
                            Explore Services
                        </Link>
                        <Link
                            href={route('packages.index')}
                            className="rounded-full border border-blush-50/40 px-8 py-3 text-sm font-semibold text-blush-50 transition hover:bg-blush-50/10"
                        >
                            View Packages
                        </Link>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
