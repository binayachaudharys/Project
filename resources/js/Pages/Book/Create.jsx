import { Head, Link, useForm, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { formatMoney } from '@/lib/money';
import { useMemo } from 'react';

function formatServicePrice(service, currency) {
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

    return formatMoney(value, currency);
}

function groupByCategory(items) {
    const groups = new Map();

    items.forEach((item) => {
        const key = item.category || 'Services';
        if (!groups.has(key)) {
            groups.set(key, []);
        }
        groups.get(key).push(item);
    });

    return Array.from(groups.entries());
}

function SelectOption({ selected, title, meta, onToggle }) {
    const label = [title, meta].filter(Boolean).join(', ');

    return (
        <button
            type="button"
            onClick={onToggle}
            aria-pressed={selected}
            aria-label={`${label}${selected ? ', selected' : ', not selected'}. Activate to ${selected ? 'remove' : 'add'}`}
            title={selected ? 'Tap to remove' : 'Tap to select'}
            className={`w-full border px-2.5 py-2 text-left transition ${
                selected
                    ? 'border-rose-500 bg-rose-50 ring-1 ring-rose-500'
                    : 'border-charcoal-100 bg-white hover:border-rose-300'
            }`}
        >
            <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium leading-snug text-charcoal-900">
                        {title}
                    </p>
                    {meta ? (
                        <p className="mt-0.5 truncate text-[11px] leading-snug text-charcoal-500">
                            {meta}
                        </p>
                    ) : null}
                </div>
                <span
                    className={`mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center border text-[9px] ${
                        selected
                            ? 'border-rose-500 bg-rose-500 text-white'
                            : 'border-charcoal-200 bg-white text-transparent'
                    }`}
                    aria-hidden="true"
                >
                    ✓
                </span>
            </div>
        </button>
    );
}

export default function BookCreate({ services, packages }) {
    const { salonContact } = usePage().props;
    const salon = salonContact ?? {};
    const currency = salon.currency || 'Rs';
    const whatsappHref = salon.whatsapp
        ? `https://wa.me/${salon.whatsapp}`
        : null;

    const bookables = useMemo(
        () => [
            ...services.map((s) => ({ ...s, bookable_type: 'service' })),
            ...packages.map((p) => ({ ...p, bookable_type: 'package' })),
        ],
        [services, packages],
    );

    const serviceGroups = useMemo(
        () => groupByCategory(services),
        [services],
    );

    const { data, setData, post, processing, errors } = useForm({
        items: [],
        starts_at: '',
        notes: '',
    });

    const hasSelection = data.items.length > 0;

    function isSelected(type, id) {
        return data.items.some(
            (item) =>
                item.bookable_type === type &&
                String(item.bookable_id) === String(id),
        );
    }

    function toggleItem(type, id) {
        const exists = isSelected(type, id);

        if (exists) {
            setData(
                'items',
                data.items.filter(
                    (item) =>
                        !(
                            item.bookable_type === type &&
                            String(item.bookable_id) === String(id)
                        ),
                ),
            );
            return;
        }

        setData('items', [
            ...data.items,
            { bookable_type: type, bookable_id: Number(id) },
        ]);
    }

    const selectedLabels = useMemo(() => {
        return data.items
            .map((item) => {
                if (item.bookable_type === 'package') {
                    const pkg = packages.find(
                        (entry) =>
                            String(entry.id) === String(item.bookable_id),
                    );
                    return pkg
                        ? `${pkg.name} — ${formatMoney(pkg.price, currency)}`
                        : null;
                }

                const service = services.find(
                    (entry) => String(entry.id) === String(item.bookable_id),
                );
                return service
                    ? `${service.name} — ${formatServicePrice(service, currency)}`
                    : null;
            })
            .filter(Boolean);
    }, [currency, data.items, packages, services]);

    function submit(e) {
        e.preventDefault();
        if (!hasSelection) {
            return;
        }
        post(route('book.store'));
    }

    return (
        <PublicLayout>
            <Head title="Book an Appointment" />

            <section className="relative isolate overflow-hidden bg-charcoal-900 px-6 py-16 text-center sm:py-20">
                <div
                    className="absolute inset-0 -z-10 bg-gradient-to-b from-charcoal-900 via-charcoal-900/95 to-charcoal-900"
                    aria-hidden="true"
                />
                <p className="font-display text-3xl font-semibold text-white sm:text-5xl">
                    {salon.name || 'Pretty Salon Nepal'}
                </p>
                <p className="mt-4 font-display text-xl italic text-white/85 sm:text-2xl">
                    {salon.tagline || 'A Whole New You'}
                </p>
                <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
                    <span className="bg-rose-500 px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white">
                        Book Now
                    </span>
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
            </section>

            <section className="bg-blush-100 px-6 py-16">
                <div className="mx-auto max-w-3xl">
                    <header className="text-center">
                        <h1 className="font-display text-3xl font-semibold text-charcoal-900 sm:text-4xl">
                            Book an Appointment
                        </h1>
                        <p className="mt-4 text-charcoal-500">
                            Tap options to select or deselect. You can choose
                            multiple services or packages — they book
                            back-to-back from your start time.
                        </p>
                    </header>

                    <form
                        onSubmit={submit}
                        className="mt-10 space-y-8 bg-white p-6 sm:p-8"
                    >
                        {serviceGroups.map(([category, items]) => (
                            <div key={category}>
                                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-charcoal-500">
                                    {category}
                                </p>
                                <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                    {items.map((service) => (
                                        <SelectOption
                                            key={`service:${service.id}`}
                                            selected={isSelected(
                                                'service',
                                                service.id,
                                            )}
                                            title={service.name}
                                            meta={`${formatServicePrice(service, currency)} · ${service.duration_minutes} min`}
                                            onToggle={() =>
                                                toggleItem(
                                                    'service',
                                                    service.id,
                                                )
                                            }
                                        />
                                    ))}
                                </div>
                            </div>
                        ))}

                        {packages.length > 0 ? (
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-charcoal-500">
                                    Packages
                                </p>
                                <div className="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                    {packages.map((pkg) => (
                                        <SelectOption
                                            key={`package:${pkg.id}`}
                                            selected={isSelected(
                                                'package',
                                                pkg.id,
                                            )}
                                            title={pkg.name}
                                            meta={formatMoney(pkg.price, currency)}
                                            onToggle={() =>
                                                toggleItem('package', pkg.id)
                                            }
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        {bookables.length === 0 ? (
                            <p className="text-sm text-charcoal-500">
                                No bookable services or packages are available
                                yet.
                            </p>
                        ) : null}

                        {selectedLabels.length > 0 ? (
                            <div className="border border-rose-100 bg-rose-50/60 px-4 py-3">
                                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-rose-600">
                                    Selected ({selectedLabels.length})
                                </p>
                                <ul className="mt-2 space-y-1 text-sm text-charcoal-700">
                                    {selectedLabels.map((label) => (
                                        <li key={label}>• {label}</li>
                                    ))}
                                </ul>
                            </div>
                        ) : (
                            <p className="text-sm text-charcoal-500">
                                Select at least one option to continue.
                            </p>
                        )}

                        {errors.items ? (
                            <p className="text-sm text-red-600">{errors.items}</p>
                        ) : null}
                        {Object.keys(errors)
                            .filter((key) => key.startsWith('items.'))
                            .map((key) => (
                                <p key={key} className="text-sm text-red-600">
                                    {errors[key]}
                                </p>
                            ))}

                        <div>
                            <label
                                htmlFor="starts_at"
                                className="block text-xs font-semibold uppercase tracking-[0.14em] text-charcoal-500"
                            >
                                Start date &amp; time
                            </label>
                            <input
                                id="starts_at"
                                type="datetime-local"
                                className="mt-2 block w-full border-charcoal-100 text-charcoal-900 focus:border-rose-500 focus:ring-rose-500"
                                value={data.starts_at}
                                onChange={(e) =>
                                    setData('starts_at', e.target.value)
                                }
                                required
                            />
                            {errors.starts_at ? (
                                <p className="mt-2 text-sm text-red-600">
                                    {errors.starts_at}
                                </p>
                            ) : null}
                        </div>

                        <div>
                            <label
                                htmlFor="notes"
                                className="block text-xs font-semibold uppercase tracking-[0.14em] text-charcoal-500"
                            >
                                Notes (optional)
                            </label>
                            <textarea
                                id="notes"
                                rows={3}
                                className="mt-2 block w-full border-charcoal-100 text-charcoal-900 focus:border-rose-500 focus:ring-rose-500"
                                value={data.notes}
                                onChange={(e) =>
                                    setData('notes', e.target.value)
                                }
                                placeholder="Colour goals, allergies, preferred stylist…"
                            />
                            {errors.notes ? (
                                <p className="mt-2 text-sm text-red-600">
                                    {errors.notes}
                                </p>
                            ) : null}
                        </div>

                        <button
                            type="submit"
                            disabled={
                                processing ||
                                bookables.length === 0 ||
                                !hasSelection
                            }
                            className="w-full bg-rose-500 px-6 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-rose-600 disabled:opacity-50"
                        >
                            Confirm Booking
                            {hasSelection ? ` (${data.items.length})` : ''}
                        </button>

                        <p className="text-center text-sm text-charcoal-500">
                            Prefer messaging?{' '}
                            {whatsappHref ? (
                                <a
                                    href={whatsappHref}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="font-semibold text-rose-600 hover:text-rose-700"
                                >
                                    Contact Me on WhatsApp
                                </a>
                            ) : (
                                'Call the salon.'
                            )}
                        </p>

                        <p className="text-center text-sm text-charcoal-300">
                            <Link
                                href={route('account.appointments')}
                                className="underline-offset-2 hover:underline"
                            >
                                View my appointments
                            </Link>
                        </p>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
