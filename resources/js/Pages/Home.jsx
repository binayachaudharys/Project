import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import PackageMenu from '@/Components/PackageMenu';
import PublicLayout from '@/Layouts/PublicLayout';
import { socialHandle } from '@/lib/money';

export default function Home({ salon }) {
    const { auth } = usePage().props;
    const salonName = salon?.name || 'Pretty Salon Nepal';
    const bookHref = auth?.user ? route('book.create') : route('login');
    const whatsappHref = salon?.whatsapp
        ? `https://wa.me/${salon.whatsapp}`
        : null;
    const menu = salon?.menu ?? [];
    const igHandle = socialHandle(salon?.instagram, '@prettysalonnepal');

    const testimonials = useMemo(
        () => [
            {
                quote: 'Warm welcome and careful hair work. Felt pampered from start to finish. If you want more than a quick appointment, this is your place.',
                name: 'Anisha R.',
            },
            {
                quote: 'The facial left my skin glowing. Booking online was simple and clear, and the team listened to exactly what I wanted.',
                name: 'Priya S.',
            },
            {
                quote: `Friendly team and great laser advice. Happy we found ${salonName} for our first visit — already planning the next one.`,
                name: 'Sita M.',
            },
        ],
        [salonName],
    );

    useEffect(() => {
        const hash = window.location.hash?.slice(1);
        if (!hash) {
            return undefined;
        }

        const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;

        const timer = window.setTimeout(() => {
            document.getElementById(hash)?.scrollIntoView({
                behavior: reduceMotion ? 'auto' : 'smooth',
                block: 'start',
            });
        }, 50);

        return () => window.clearTimeout(timer);
    }, []);

    const exploreLinks = [
        { href: route('packages.index'), label: 'Packages' },
        {
            href: `${route('packages.index')}#laser-hair-removal`,
            label: 'Laser',
        },
        { href: `${route('packages.index')}#cold-hifu`, label: 'Cold HIFU' },
        { href: '#gallery', label: 'Gallery' },
        { href: bookHref, label: 'Book Online' },
        {
            href: salon?.instagram,
            label: 'Instagram',
            external: true,
        },
        {
            href: salon?.facebook,
            label: 'Facebook',
            external: true,
        },
        {
            href: salon?.tiktok,
            label: 'TikTok',
            external: true,
        },
    ].filter((item) => item.href);

    return (
        <PublicLayout>
            <Head title="Home" />

            {/* Hero — Refresh-style centered brand + dual CTA */}
            <section className="relative isolate min-h-[78vh] overflow-hidden">
                <img
                    src="/images/flyer-packages.png"
                    alt=""
                    className="absolute inset-0 -z-20 h-full w-full scale-105 object-cover animate-hero-zoom"
                    aria-hidden="true"
                />
                <div
                    className="absolute inset-0 -z-10 bg-gradient-to-b from-charcoal-900/75 via-charcoal-900/55 to-charcoal-900/80"
                    aria-hidden="true"
                />

                <div className="mx-auto flex min-h-[78vh] max-w-4xl flex-col items-center justify-center px-6 py-24 text-center animate-fade-up">
                    {salon?.logo ? (
                        <img
                            src={salon.logo}
                            alt={salonName}
                            className="h-16 w-auto brightness-0 invert sm:h-20"
                        />
                    ) : null}

                    <h1 className="mt-8 font-display text-4xl font-semibold tracking-tight text-white sm:text-6xl">
                        {salonName}
                    </h1>

                    <p className="mt-5 font-display text-2xl italic text-white/90 sm:text-3xl">
                        {salon?.tagline || 'A Whole New You'}
                    </p>

                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        <Link
                            href={bookHref}
                            className="bg-rose-500 px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-rose-600"
                        >
                            Book Now
                        </Link>
                        {whatsappHref ? (
                            <a
                                href={whatsappHref}
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Contact on WhatsApp (opens in a new tab)"
                                className="border border-white/70 px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-white/10"
                            >
                                Contact Me
                            </a>
                        ) : null}
                    </div>
                </div>
            </section>

            {/* Notifications */}
            <section className="border-b border-charcoal-50 bg-white px-6 py-12">
                <div className="mx-auto max-w-3xl">
                    <h2 className="text-center text-xs font-semibold uppercase tracking-[0.28em] text-charcoal-300">
                        Notifications
                    </h2>
                    <ul className="mt-8 space-y-5 text-sm leading-relaxed text-charcoal-500">
                        {salon?.promo ? (
                            <li className="flex gap-3">
                                <span
                                    className="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"
                                    aria-hidden="true"
                                />
                                <span>{salon.promo}</span>
                            </li>
                        ) : null}
                        <li className="flex gap-3">
                            <span
                                className="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"
                                aria-hidden="true"
                            />
                            <span>
                                Follow us on Instagram{' '}
                                {salon?.instagram ? (
                                    <a
                                        href={salon.instagram}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="font-semibold text-rose-600 underline-offset-2 hover:underline"
                                        aria-label={`${igHandle} on Instagram (opens in a new tab)`}
                                    >
                                        {igHandle}
                                    </a>
                                ) : (
                                    igHandle
                                )}{' '}
                                and Facebook for openings, offers, and new
                                treatments.
                            </span>
                        </li>
                        <li className="flex gap-3">
                            <span
                                className="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"
                                aria-hidden="true"
                            />
                            <span>
                                For colour and laser bookings, message us on
                                WhatsApp with a clear photo in natural light so
                                we can advise the right plan.
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            {/* Welcome / journey */}
            <section className="bg-blush-100 px-6 py-20">
                <div className="mx-auto max-w-3xl text-center">
                    <h2 className="font-display text-3xl font-semibold text-charcoal-900 sm:text-5xl">
                        Your Beauty Journey Starts Here!
                    </h2>
                    <p className="mt-6 text-base leading-relaxed text-charcoal-500 sm:text-lg">
                        Welcome! We&apos;re so glad you&apos;re considering{' '}
                        <strong className="font-semibold text-charcoal-700">
                            {salonName}
                        </strong>{' '}
                        for your next hair, skin, or glow transformation. We&apos;re
                        not only about beautiful results — we&apos;re about a
                        calm, welcoming space where you feel cared for.
                    </p>

                    <h3 className="mt-12 font-display text-2xl font-semibold text-charcoal-900">
                        Grand Opening — Get Rewarded
                    </h3>
                    <p className="mt-4 text-base leading-relaxed text-charcoal-500">
                        Celebrate with us during our opening month. Enjoy{' '}
                        <strong className="font-semibold text-rose-600">
                            30% OFF
                        </strong>{' '}
                        selected treatments and explore our flyer package menus
                        for hair, facial, laser, HIFU, and nails.
                    </p>

                    <h3 className="mt-12 font-display text-2xl font-semibold text-charcoal-900">
                        Ready to Experience the Difference?
                    </h3>
                    <p className="mt-4 text-base leading-relaxed text-charcoal-500">
                        Book your first appointment online or message us on
                        WhatsApp. We can&apos;t wait to welcome you at Shankhamul.
                    </p>

                    <Link
                        href={bookHref}
                        className="mt-10 inline-flex bg-rose-500 px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-rose-600"
                    >
                        Book Your Visit
                    </Link>
                </div>
            </section>

            {/* Explore tiles — Refresh “Expert … Services” style */}
            <section className="bg-white px-6 py-20">
                <div className="mx-auto max-w-6xl">
                    <h2 className="mx-auto max-w-3xl text-center font-display text-3xl font-semibold text-charcoal-900 sm:text-4xl">
                        Expert Beauty Services
                    </h2>
                    <div className="mt-12 grid gap-px bg-charcoal-50 sm:grid-cols-2 lg:grid-cols-4">
                        {exploreLinks.map((item) =>
                            item.external ? (
                                <a
                                    key={item.label}
                                    href={item.href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label={`${item.label} (opens in a new tab)`}
                                    className="group bg-white px-6 py-10 text-center transition hover:bg-blush-100"
                                >
                                    <span className="font-display text-xl font-semibold text-charcoal-900 transition group-hover:text-rose-600">
                                        {item.label}
                                    </span>
                                </a>
                            ) : (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="group bg-white px-6 py-10 text-center transition hover:bg-blush-100"
                                >
                                    <span className="font-display text-xl font-semibold text-charcoal-900 transition group-hover:text-rose-600">
                                        {item.label}
                                    </span>
                                </Link>
                            ),
                        )}
                    </div>
                </div>
            </section>

            {/* Flyer package menus */}
            <section className="bg-blush-100 py-20">
                <PackageMenu menu={menu} />
            </section>

            {/* Social */}
            <section className="border-y border-charcoal-50 bg-white px-6 py-16 text-center">
                <h2 className="font-display text-3xl font-semibold text-charcoal-900">
                    Follow Us on Social
                </h2>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                    {[
                        { href: salon?.instagram, label: 'Instagram' },
                        { href: salon?.facebook, label: 'Facebook' },
                        { href: salon?.tiktok, label: 'TikTok' },
                    ]
                        .filter((item) => item.href)
                        .map((item) => (
                            <a
                                key={item.label}
                                href={item.href}
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label={`${item.label} (opens in a new tab)`}
                                className="border border-charcoal-100 px-6 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-charcoal-700 transition hover:border-rose-400 hover:text-rose-600"
                            >
                                {item.label}
                            </a>
                        ))}
                </div>
            </section>

            {/* Testimonials */}
            <section className="bg-blush-100 px-6 py-20">
                <div className="mx-auto max-w-3xl">
                    <h2 className="text-center font-display text-3xl font-semibold text-charcoal-900 sm:text-4xl">
                        What Our Clients Say
                    </h2>
                    <div className="mt-12 space-y-10">
                        {testimonials.map((item) => (
                            <blockquote
                                key={item.name}
                                className="border-b border-charcoal-100 pb-10 last:border-0 last:pb-0"
                            >
                                <p className="font-display text-xl font-semibold text-charcoal-900">
                                    {item.name}
                                    <span
                                        className="ml-2 text-base text-rose-500"
                                        aria-label="5 stars"
                                    >
                                        ★★★★★
                                    </span>
                                </p>
                                <p className="mt-4 text-base leading-relaxed text-charcoal-500">
                                    “{item.quote}”
                                </p>
                            </blockquote>
                        ))}
                    </div>
                </div>
            </section>

            {/* Gallery */}
            <section id="gallery" className="bg-white px-6 py-20">
                <div className="mx-auto max-w-6xl">
                    <h2 className="text-center font-display text-3xl font-semibold text-charcoal-900 sm:text-4xl">
                        Gallery
                    </h2>
                    <div className="mt-12 grid gap-4 md:grid-cols-2">
                        <img
                            src="/images/flyer-packages.png"
                            alt={`${salonName} package menu flyer`}
                            className="h-full min-h-[280px] w-full object-cover"
                        />
                        <img
                            src="/images/flyer-laser.png"
                            alt={`${salonName} laser and HIFU flyer`}
                            className="h-full min-h-[280px] w-full object-cover"
                        />
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
