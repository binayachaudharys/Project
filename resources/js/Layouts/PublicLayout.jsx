import { Link, usePage } from '@inertiajs/react';

const navLinks = [
    { href: route('home'), label: 'Home' },
    { href: route('services.index'), label: 'Services' },
    { href: route('packages.index'), label: 'Packages' },
];

export default function PublicLayout({ children }) {
    const { auth } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col bg-blush-50 font-sans text-charcoal-700">
            <header className="border-b border-rose-100 bg-blush-50/90 backdrop-blur">
                <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                    <Link
                        href={route('home')}
                        className="font-display text-xl font-semibold tracking-tight text-charcoal-900"
                    >
                        Pretty Girls{' '}
                        <span className="text-rose-500">Ladies Salon</span>
                    </Link>

                    <nav className="flex items-center gap-6">
                        {navLinks.map((link) => (
                            <Link
                                key={link.label}
                                href={link.href}
                                className="text-sm font-medium text-charcoal-500 transition hover:text-rose-500"
                            >
                                {link.label}
                            </Link>
                        ))}

                        {auth?.user ? (
                            <>
                                <Link
                                    href={route('account.appointments')}
                                    className="text-sm font-medium text-charcoal-500 transition hover:text-rose-500"
                                >
                                    My Appointments
                                </Link>
                                <Link
                                    href={route('book.create')}
                                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 transition hover:bg-rose-600"
                                >
                                    Book Now
                                </Link>
                            </>
                        ) : (
                            <Link
                                href={route('login')}
                                className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 transition hover:bg-rose-600"
                            >
                                Login
                            </Link>
                        )}
                    </nav>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t border-rose-100 bg-charcoal-900 py-8 text-center text-sm text-blush-100/80">
                <p>
                    &copy; {new Date().getFullYear()} Pretty Girls Ladies
                    Salon. All rights reserved.
                </p>
                <p className="mt-1 text-blush-100/50">
                    Built by Art Developer and Market As pot
                </p>
            </footer>
        </div>
    );
}
