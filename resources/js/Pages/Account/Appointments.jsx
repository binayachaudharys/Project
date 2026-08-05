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
    const { flash } = usePage().props;

    function cancel(appointment) {
        if (! confirm('Cancel this appointment?')) {
            return;
        }

        router.delete(route('account.appointments.cancel', appointment.id));
    }

    return (
        <PublicLayout>
            <Head title="My Appointments" />

            <section className="mx-auto max-w-3xl px-6 py-16">
                <header>
                    <p className="text-sm font-semibold uppercase tracking-[0.25em] text-rose-500">
                        Your Bookings
                    </p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-charcoal-900">
                        My Appointments
                    </h1>
                </header>

                {flash?.success && (
                    <p className="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {flash.success}
                    </p>
                )}

                {appointments.length === 0 ? (
                    <p className="mt-12 text-charcoal-500">
                        You have no appointments yet.{' '}
                        <Link href={route('book.create')} className="font-medium text-rose-500 hover:text-rose-600">
                            Book one now
                        </Link>
                        .
                    </p>
                ) : (
                    <ul className="mt-10 space-y-4">
                        {appointments.map((appointment) => (
                            <li
                                key={appointment.id}
                                className="flex items-center justify-between rounded-2xl border border-rose-100 bg-white p-6 shadow-sm"
                            >
                                <div>
                                    <p className="font-display text-lg font-semibold text-charcoal-900">
                                        {formatDateTime(appointment.starts_at)}
                                    </p>
                                    <p className="mt-1 text-sm text-charcoal-500">
                                        {appointment.staff ? `With ${appointment.staff.name}` : 'No preferred staff'}
                                    </p>
                                </div>

                                <div className="flex items-center gap-4">
                                    <span
                                        className={`rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                                            statusStyles[appointment.status] ?? 'bg-charcoal-100 text-charcoal-600'
                                        }`}
                                    >
                                        {appointment.status}
                                    </span>

                                    {['pending', 'confirmed'].includes(appointment.status) &&
                                        new Date(appointment.starts_at) > new Date() && (
                                            <button
                                                type="button"
                                                onClick={() => cancel(appointment)}
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
            </section>
        </PublicLayout>
    );
}
