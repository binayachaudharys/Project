import { Link, usePage } from '@inertiajs/react';
import { useId, useState } from 'react';

function formatHour(value) {
    if (!value) {
        return '';
    }

    const [hour, minute] = String(value).split(':').map(Number);
    if (Number.isNaN(hour)) {
        return value;
    }

    const period = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    const displayMinute = String(minute ?? 0).padStart(2, '0');

    return `${displayHour}:${displayMinute} ${period}`;
}

function galleryHref() {
    return `${route('home')}#gallery`;
}

export default function PublicLayout({ children }) {
    const { auth, salonContact } = usePage().props;
    const salon = salonContact ?? {};
    const salonName = salon.name || 'Pretty Salon Nepal';
    const bookHref = auth?.user ? route('book.create') : route('login');
    const whatsappHref = salon.whatsapp
        ? `https://wa.me/${salon.whatsapp}`
        : null;
    const [menuOpen, setMenuOpen] = useState(false);
    const mobileNavId = useId();

    const hoursLabel = `${formatHour(salon.open)} – ${formatHour(salon.close)}`;
    const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    const socialLinks = [
        { label: 'Instagram', href: salon.instagram },
        { label: 'Facebook', href: salon.facebook },
        { label: 'TikTok', href: salon.tiktok },
    ].filter((item) => item.href);

    return (
        <div className="flex min-h-screen flex-col bg-white font-sans text-charcoal-700">
            <header className="sticky top-0 z-50 border-b border-charcoal-50/80 bg-white/95 backdrop-blur">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3 sm:px-6 sm:py-4">
                    <Link
                        href={route('home')}
                        className="flex min-w-0 items-center gap-3"
                        onClick={() => setMenuOpen(false)}
                        aria-label={`${salonName} home`}
                    >
                        {salon.logo ? (
                            <img
                                src={salon.logo}
                                alt=""
                                className="h-11 w-auto sm:h-12"
                                width={176}
                                height={88}
                            />
                        ) : null}
                        <span className="truncate font-display text-lg font-semibold tracking-tight text-charcoal-900 sm:text-xl">
                            {salonName}
                        </span>
                    </Link>

                    <nav
                        className="hidden items-center gap-7 lg:flex"
                        aria-label="Primary"
                    >
                        <Link
                            href={route('home')}
                            className="text-[13px] font-semibold uppercase tracking-[0.14em] text-charcoal-500 transition hover:text-rose-500"
                        >
                            Home
                        </Link>
                        <Link
                            href={route('packages.index')}
                            className="text-[13px] font-semibold uppercase tracking-[0.14em] text-charcoal-500 transition hover:text-rose-500"
                        >
                            Packages
                        </Link>
                        <Link
                            href={galleryHref()}
                            className="text-[13px] font-semibold uppercase tracking-[0.14em] text-charcoal-500 transition hover:text-rose-500"
                        >
                            Gallery
                        </Link>
                        <Link
                            href={bookHref}
                            className="rounded-sm bg-rose-500 px-5 py-2.5 text-[13px] font-semibold uppercase tracking-[0.12em] text-white transition hover:bg-rose-600"
                        >
                            Book Now
                        </Link>
                    </nav>

                    <button
                        type="button"
                        className="inline-flex h-10 w-10 items-center justify-center border border-charcoal-100 text-charcoal-700 lg:hidden"
                        aria-expanded={menuOpen}
                        aria-controls={mobileNavId}
                        aria-label={menuOpen ? 'Close menu' : 'Open menu'}
                        onClick={() => setMenuOpen((open) => !open)}
                    >
                        <span className="flex flex-col gap-1.5" aria-hidden="true">
                            <span
                                className={`block h-0.5 w-5 bg-current transition ${menuOpen ? 'translate-y-2 rotate-45' : ''}`}
                            />
                            <span
                                className={`block h-0.5 w-5 bg-current transition ${menuOpen ? 'opacity-0' : ''}`}
                            />
                            <span
                                className={`block h-0.5 w-5 bg-current transition ${menuOpen ? '-translate-y-2 -rotate-45' : ''}`}
                            />
                        </span>
                    </button>
                </div>

                {menuOpen ? (
                    <nav
                        id={mobileNavId}
                        className="border-t border-charcoal-50 bg-white px-5 py-4 lg:hidden"
                        aria-label="Mobile"
                    >
                        <div className="flex flex-col gap-1">
                            <Link
                                href={route('home')}
                                className="py-2 text-sm font-semibold uppercase tracking-[0.14em] text-charcoal-700"
                                onClick={() => setMenuOpen(false)}
                            >
                                Home
                            </Link>
                            <Link
                                href={route('packages.index')}
                                className="py-2 text-sm font-semibold uppercase tracking-[0.14em] text-charcoal-700"
                                onClick={() => setMenuOpen(false)}
                            >
                                Packages
                            </Link>
                            <Link
                                href={galleryHref()}
                                className="py-2 text-sm font-semibold uppercase tracking-[0.14em] text-charcoal-700"
                                onClick={() => setMenuOpen(false)}
                            >
                                Gallery
                            </Link>
                            <Link
                                href={bookHref}
                                className="mt-2 inline-flex justify-center bg-rose-500 px-5 py-3 text-sm font-semibold uppercase tracking-[0.12em] text-white"
                                onClick={() => setMenuOpen(false)}
                            >
                                Book Now
                            </Link>
                        </div>
                    </nav>
                ) : null}
            </header>

            <main id="main-content" className="flex-1" tabIndex={-1}>
                {children}
            </main>

            <footer className="bg-[#1a1716] text-white" aria-label="Site footer">
                <div className="mx-auto grid max-w-6xl gap-10 px-5 py-16 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <h2 className="text-xs font-semibold uppercase tracking-[0.22em] text-rose-300">
                            About
                        </h2>
                        <p className="mt-4 font-display text-2xl font-semibold text-white">
                            {salonName}
                        </p>
                        <p className="mt-3 text-sm leading-relaxed text-white/65">
                            Independently located at {salon.address}. Ladies
                            hair, skin, laser, HIFU, and nail care.
                        </p>
                        <Link
                            href={bookHref}
                            className="mt-6 inline-flex bg-rose-500 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.14em] text-white transition hover:bg-rose-600"
                        >
                            Book Online
                        </Link>
                    </div>

                    <div>
                        <h2 className="text-xs font-semibold uppercase tracking-[0.22em] text-rose-300">
                            Come Visit
                        </h2>
                        <p className="mt-4 text-sm font-semibold text-white">
                            {salonName}
                        </p>
                        <p className="mt-2 text-sm leading-relaxed text-white/65">
                            {salon.address}
                        </p>
                        {salon.phone ? (
                            <a
                                href={`tel:${salon.phone}`}
                                className="mt-4 block text-sm text-white/80 transition hover:text-white"
                                aria-label={`Call ${salon.phone}`}
                            >
                                {salon.phone}
                            </a>
                        ) : null}
                        {whatsappHref ? (
                            <a
                                href={whatsappHref}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mt-2 block text-sm text-white/80 transition hover:text-white"
                                aria-label="Chat on WhatsApp (opens in a new tab)"
                            >
                                WhatsApp
                            </a>
                        ) : null}
                    </div>

                    <div>
                        <h2 className="text-xs font-semibold uppercase tracking-[0.22em] text-rose-300">
                            Hours
                        </h2>
                        <ul className="mt-4 space-y-2 text-sm text-white/70">
                            {weekdays.map((day) => (
                                <li
                                    key={day}
                                    className="flex justify-between gap-4"
                                >
                                    <span>{day}</span>
                                    <span>{hoursLabel}</span>
                                </li>
                            ))}
                            <li className="flex justify-between gap-4">
                                <span>Sun</span>
                                <span>Closed</span>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h2 className="text-xs font-semibold uppercase tracking-[0.22em] text-rose-300">
                            Follow
                        </h2>
                        <ul className="mt-4 space-y-3 text-sm">
                            {socialLinks.map((item) => (
                                <li key={item.label}>
                                    <a
                                        href={item.href}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-white/80 transition hover:text-white"
                                        aria-label={`${item.label} (opens in a new tab)`}
                                    >
                                        {item.label}
                                    </a>
                                </li>
                            ))}
                            <li>
                                <Link
                                    href={galleryHref()}
                                    className="text-white/80 transition hover:text-white"
                                >
                                    Gallery
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div className="border-t border-white/10 px-5 py-5 text-center text-xs text-white/45 sm:px-6">
                    <p>
                        © {new Date().getFullYear()} {salonName}
                    </p>
                </div>
            </footer>
        </div>
    );
}
