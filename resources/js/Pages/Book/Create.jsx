import { Head, useForm } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { useMemo } from 'react';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

export default function BookCreate({ services, packages }) {
    const bookables = useMemo(
        () => [
            ...services.map((s) => ({ ...s, bookable_type: 'service' })),
            ...packages.map((p) => ({ ...p, bookable_type: 'package' })),
        ],
        [services, packages],
    );

    const { data, setData, post, processing, errors } = useForm({
        bookable_type: bookables[0]?.bookable_type ?? 'service',
        bookable_id: bookables[0]?.id ?? '',
        starts_at: '',
        notes: '',
    });

    function handleBookableChange(e) {
        const [type, id] = e.target.value.split(':');
        setData((current) => ({ ...current, bookable_type: type, bookable_id: id }));
    }

    function submit(e) {
        e.preventDefault();
        post(route('book.store'));
    }

    return (
        <PublicLayout>
            <Head title="Book an Appointment" />

            <section className="mx-auto max-w-2xl px-6 py-16">
                <header>
                    <p className="text-sm font-semibold uppercase tracking-[0.25em] text-rose-500">
                        Reserve Your Spot
                    </p>
                    <h1 className="mt-3 font-display text-4xl font-semibold text-charcoal-900">
                        Book an Appointment
                    </h1>
                    <p className="mt-4 text-charcoal-500">
                        No payment needed online — settle up at the salon.
                    </p>
                </header>

                <form
                    onSubmit={submit}
                    className="mt-10 space-y-6 rounded-2xl border border-rose-100 bg-white p-8 shadow-sm"
                >
                    <div>
                        <label
                            htmlFor="bookable"
                            className="block text-sm font-medium text-charcoal-700"
                        >
                            Service or Package
                        </label>
                        <select
                            id="bookable"
                            className="mt-2 block w-full rounded-lg border-rose-200 text-charcoal-900 focus:border-rose-500 focus:ring-rose-500"
                            value={`${data.bookable_type}:${data.bookable_id}`}
                            onChange={handleBookableChange}
                        >
                            {services.length > 0 && (
                                <optgroup label="Services">
                                    {services.map((service) => (
                                        <option
                                            key={`service:${service.id}`}
                                            value={`service:${service.id}`}
                                        >
                                            {service.name} — {formatPrice(service.price)} ({service.duration_minutes} min)
                                        </option>
                                    ))}
                                </optgroup>
                            )}
                            {packages.length > 0 && (
                                <optgroup label="Packages">
                                    {packages.map((pkg) => (
                                        <option
                                            key={`package:${pkg.id}`}
                                            value={`package:${pkg.id}`}
                                        >
                                            {pkg.name} — {formatPrice(pkg.price)}
                                        </option>
                                    ))}
                                </optgroup>
                            )}
                        </select>
                        {errors.bookable_id && (
                            <p className="mt-2 text-sm text-red-600">{errors.bookable_id}</p>
                        )}
                        {errors.bookable_type && (
                            <p className="mt-2 text-sm text-red-600">{errors.bookable_type}</p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="starts_at"
                            className="block text-sm font-medium text-charcoal-700"
                        >
                            Date &amp; Time
                        </label>
                        <input
                            id="starts_at"
                            type="datetime-local"
                            className="mt-2 block w-full rounded-lg border-rose-200 text-charcoal-900 focus:border-rose-500 focus:ring-rose-500"
                            value={data.starts_at}
                            onChange={(e) => setData('starts_at', e.target.value)}
                            required
                        />
                        {errors.starts_at && (
                            <p className="mt-2 text-sm text-red-600">{errors.starts_at}</p>
                        )}
                    </div>

                    <div>
                        <label
                            htmlFor="notes"
                            className="block text-sm font-medium text-charcoal-700"
                        >
                            Notes (optional)
                        </label>
                        <textarea
                            id="notes"
                            rows={3}
                            className="mt-2 block w-full rounded-lg border-rose-200 text-charcoal-900 focus:border-rose-500 focus:ring-rose-500"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        {errors.notes && (
                            <p className="mt-2 text-sm text-red-600">{errors.notes}</p>
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={processing || bookables.length === 0}
                        className="w-full rounded-full bg-rose-500 px-6 py-3 text-sm font-semibold text-blush-50 transition hover:bg-rose-600 disabled:opacity-50"
                    >
                        Confirm Booking
                    </button>
                </form>
            </section>
        </PublicLayout>
    );
}
