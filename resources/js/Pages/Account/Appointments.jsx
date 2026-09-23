import { Head, Link, router, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

function formatDateTime(value) {
    return new Date(value).toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

const statusStyles = {
    pending: 'bg-amber-100 text-amber-700',
    confirmed: 'bg-emerald-100 text-emerald-700',
    completed: 'bg-charcoal-100 text-charcoal-600',
    cancelled: 'bg-red-100 text-red-700',
};

export default function AccountAppointments({ appointments }) {
    const { flash, salonContact, auth } = usePage().props;
    const salon = salonContact ?? {};
    const bookHref = auth?.user ? route('book.create') : route('login');
    const whatsappHref = salon.whatsapp
        ? `https://wa.me/${salon.whatsapp}`
        : null;

    function cancel(appointment) {
        if (!confirm('Cancel this appointment?')) {
            return;
        }

        router.delete(route('account.appointments.cancel', appointment.id));
    }

    return (
        <PublicLayout>
            <Head title="My Appointments" />

            <section className="relative isolate overflow-hidden bg-charcoal-900 px-6 py-16 text-center sm:py-20">
                <p className="font-display text-3xl font-semibold text-white sm:text-5xl">
                    {salon.name || 'Pretty Salon Nepal'}
                </p>
                <p className="mt-4 font-display text-xl italic text-white/85 sm:text-2xl">
                    {salon.tagline || 'A Whole New You'}
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
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
                            rel="noreferrer"
                            className="border border-white/70 px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-white/10"
                        >
                            Contact Me
                        </a>
                    ) : null}
                </div>
            </section>

            <section className="bg-blush-100 px-6 py-16">
                <div className="mx-auto max-w-3xl">
                    <header className="text-center">
                        <h1 className="font-display text-3xl font-semibold text-charcoal-900 sm:text-4xl">
                            Your Appointments
                        </h1>
                        <p className="mt-4 text-charcoal-500">
                            Review upcoming visits or book your next look.
                        </p>
                    </header>

                    {flash?.success ? (
                        <p className="mt-8 border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-700">
                            {flash.success}
                        </p>
                    ) : null}

                    {appointments.length === 0 ? (
                        <div className="mt-12 bg-white px-6 py-12 text-center">
                            <p className="text-charcoal-500">
                                You have no appointments yet.
                            </p>
                            <Link
                                href={bookHref}
                                className="mt-6 inline-flex bg-rose-500 px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-rose-600"
                            >
                                Book Now
                            </Link>
                        </div>
                    ) : (
                        <ul className="mt-10 divide-y divide-charcoal-50 border-y border-charcoal-50 bg-white">
                            {appointments.map((appointment) => (
                                <li
                                    key={appointment.id}
                                    className="flex flex-col gap-4 px-5 py-6 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                    <p className="font-display text-lg font-semibold text-charcoal-900">
                                        {formatDateTime(
                                            appointment.starts_at,
                                        )}
                                    </p>
                                    <p className="mt-1 text-sm font-medium text-charcoal-700">
                                        {appointment.bookable?.name ??
                                            'Service / package'}
                                    </p>
                                    <p className="mt-1 text-sm text-charcoal-500">
                                        {appointment.staff
                                            ? `With ${appointment.staff.name}`
                                            : 'No preferred staff'}
                                    </p>
                                </div>

                                    <div className="flex items-center gap-4">
                                        <span
                                            className={`px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                                                statusStyles[
                                                    appointment.status
                                                ] ??
                                                'bg-charcoal-100 text-charcoal-600'
                                            }`}
                                        >
                                            {appointment.status}
                                        </span>

                                        {['pending', 'confirmed'].includes(
                                            appointment.status,
                                        ) &&
                                            new Date(appointment.starts_at) >
                                                new Date() && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        cancel(appointment)
                                                    }
                                                    className="text-sm font-medium text-red-500 hover:text-red-600"
                                                >
                                                    Cancel
                                                </button>
                                            )}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}
