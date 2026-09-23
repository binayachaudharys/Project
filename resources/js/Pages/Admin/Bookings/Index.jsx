import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

function formatWhen(value) {
    return new Date(value).toLocaleString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

const statusClass = {
    pending: 'bg-amber-100 text-amber-800',
    confirmed: 'bg-emerald-100 text-emerald-800',
    completed: 'bg-charcoal-100 text-charcoal-700',
    cancelled: 'bg-red-100 text-red-700',
};

export default function BookingsIndex({ appointments, filters }) {
    const [status, setStatus] = useState(filters.status ?? '');
    const [date, setDate] = useState(filters.date ?? '');

    function applyFilters(e) {
        e.preventDefault();
        router.get(
            route('admin.bookings.index'),
            {
                status: status || undefined,
                date: date || undefined,
            },
            { preserveState: true },
        );
    }

    function setBookingStatus(appointment, nextStatus) {
        if (
            nextStatus === 'cancelled' &&
            !confirm('Cancel this booking?')
        ) {
            return;
        }

        router.post(
            route('admin.bookings.status', appointment.id),
            { status: nextStatus },
            { preserveScroll: true },
        );
    }

    return (
        <AdminLayout title="Bookings">
            <Head title="Admin · Bookings" />

            <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                Bookings
            </h1>
            <p className="mt-2 text-charcoal-500">
                Confirm, set pending, or cancel. Confirm also creates a billing
                invoice.
            </p>

            <form
                onSubmit={applyFilters}
                className="mt-6 flex flex-wrap items-end gap-4"
            >
                <label className="text-sm">
                    <span className="text-charcoal-500">Status</span>
                    <select
                        className="mt-1 block rounded-md border-gray-300 shadow-sm"
                        value={status}
                        onChange={(e) => setStatus(e.target.value)}
                    >
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </label>
                <label className="text-sm">
                    <span className="text-charcoal-500">Date</span>
                    <input
                        type="date"
                        className="mt-1 block rounded-md border-gray-300 shadow-sm"
                        value={date}
                        onChange={(e) => setDate(e.target.value)}
                    />
                </label>
                <button
                    type="submit"
                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-white hover:bg-rose-600"
                >
                    Filter
                </button>
            </form>

            <div className="mt-8 overflow-x-auto rounded-2xl border border-rose-100 bg-white">
                <table className="min-w-full divide-y divide-rose-100 text-sm">
                    <thead className="bg-blush-50 text-left text-charcoal-500">
                        <tr>
                            <th className="px-4 py-3 font-medium">When</th>
                            <th className="px-4 py-3 font-medium">Customer</th>
                            <th className="px-4 py-3 font-medium">Service</th>
                            <th className="px-4 py-3 font-medium">Status</th>
                            <th className="px-4 py-3 font-medium">Billing</th>
                            <th className="px-4 py-3 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-rose-50">
                        {appointments.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={6}
                                    className="px-4 py-8 text-center text-charcoal-500"
                                >
                                    No bookings found.
                                </td>
                            </tr>
                        ) : (
                            appointments.map((appointment) => (
                                <tr key={appointment.id}>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        {formatWhen(appointment.starts_at)}
                                    </td>
                                    <td className="px-4 py-3">
                                        <p className="font-medium text-charcoal-900">
                                            {appointment.customer?.name ?? '—'}
                                        </p>
                                        <p className="text-xs text-charcoal-500">
                                            {appointment.customer?.phone ?? ''}
                                        </p>
                                    </td>
                                    <td className="px-4 py-3">
                                        {appointment.bookable?.name ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${
                                                statusClass[appointment.status] ??
                                                'bg-charcoal-100 text-charcoal-700'
                                            }`}
                                        >
                                            {appointment.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-xs text-charcoal-500">
                                        {appointment.sale ? (
                                            <span>
                                                {appointment.sale.sale_number}
                                                <br />
                                                <span className="capitalize">
                                                    {String(
                                                        appointment.sale.status,
                                                    ).replace('_', ' ')}
                                                </span>
                                            </span>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-2">
                                            {appointment.status !==
                                                'confirmed' &&
                                            appointment.status !==
                                                'completed' &&
                                            appointment.status !==
                                                'cancelled' ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setBookingStatus(
                                                            appointment,
                                                            'confirmed',
                                                        )
                                                    }
                                                    className="rounded bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                                                >
                                                    Confirm
                                                </button>
                                            ) : null}
                                            {appointment.status ===
                                                'confirmed' &&
                                            !appointment.sale ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setBookingStatus(
                                                            appointment,
                                                            'confirmed',
                                                        )
                                                    }
                                                    className="rounded bg-rose-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-600"
                                                >
                                                    Add to billing
                                                </button>
                                            ) : null}
                                            {appointment.status !==
                                                'pending' &&
                                            appointment.status !==
                                                'completed' &&
                                            appointment.status !==
                                                'cancelled' ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setBookingStatus(
                                                            appointment,
                                                            'pending',
                                                        )
                                                    }
                                                    className="rounded bg-amber-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-amber-600"
                                                >
                                                    Pending
                                                </button>
                                            ) : null}
                                            {appointment.status !==
                                                'cancelled' &&
                                            appointment.status !==
                                                'completed' ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setBookingStatus(
                                                            appointment,
                                                            'cancelled',
                                                        )
                                                    }
                                                    className="rounded bg-red-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-600"
                                                >
                                                    Cancel
                                                </button>
                                            ) : null}
                                        </div>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
