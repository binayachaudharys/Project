import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

function formatTime(value) {
    return new Date(value).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    });
}

export default function StaffToday({ appointments }) {
    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Today&apos;s Appointments</h2>}>
            <Head title="Today" />

            <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
                {appointments.length === 0 ? (
                    <p className="text-gray-500">No appointments scheduled for today.</p>
                ) : (
                    <div className="overflow-hidden rounded-lg bg-white shadow">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Time</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Staff</th>
                                    <th className="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 bg-white">
                                {appointments.map((appointment) => (
                                    <tr key={appointment.id}>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {formatTime(appointment.starts_at)} – {formatTime(appointment.ends_at)}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                            {appointment.customer?.name ?? '—'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {appointment.staff?.name ?? 'Unassigned'}
                                        </td>
                                        <td className="whitespace-nowrap px-6 py-4 text-sm capitalize text-gray-500">
                                            {appointment.status}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
