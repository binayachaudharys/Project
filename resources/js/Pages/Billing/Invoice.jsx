import { Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';

function formatMoney(value) {
    return `Rs. ${Number(value ?? 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function formatWhen(value) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('en-NP', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export default function Invoice({
    sale,
    salon,
    backRoute = 'admin.billing.index',
}) {
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('print') === '1') {
            const timer = window.setTimeout(() => window.print(), 250);
            return () => window.clearTimeout(timer);
        }

        return undefined;
    }, []);

    const status = String(sale.status ?? '').replace('_', ' ');
    const payment = sale.payments?.[0];

    return (
        <div className="min-h-screen bg-charcoal-50 font-sans text-charcoal-800 print:bg-white">
            <Head title={`Invoice ${sale.sale_number}`} />

            <div className="mx-auto max-w-3xl px-4 py-6 print:max-w-none print:px-0 print:py-0">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
                    <Link
                        href={route(backRoute)}
                        className="text-sm font-medium text-rose-600 hover:text-rose-700"
                    >
                        ← Back to billing
                    </Link>
                    <button
                        type="button"
                        onClick={() => window.print()}
                        className="rounded-full bg-rose-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-600"
                    >
                        Print invoice
                    </button>
                </div>

                <article className="rounded-2xl border border-rose-100 bg-white p-6 shadow-sm sm:p-10 print:rounded-none print:border-0 print:p-0 print:shadow-none">
                    <header className="flex flex-wrap items-start justify-between gap-6 border-b border-charcoal-50 pb-6">
                        <div>
                            {salon?.logo ? (
                                <img
                                    src={salon.logo}
                                    alt=""
                                    className="mb-3 h-12 w-auto"
                                />
                            ) : null}
                            <h1 className="font-display text-2xl font-semibold text-charcoal-900">
                                {salon?.name || 'Pretty Salon'}
                            </h1>
                            {salon?.address ? (
                                <p className="mt-2 max-w-xs text-sm text-charcoal-500">
                                    {salon.address}
                                </p>
                            ) : null}
                            {salon?.phone ? (
                                <p className="mt-1 text-sm text-charcoal-500">
                                    {salon.phone}
                                </p>
                            ) : null}
                        </div>
                        <div className="text-right">
                            <p className="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600">
                                Tax invoice
                            </p>
                            <p className="mt-2 font-display text-xl font-semibold text-charcoal-900">
                                {sale.sale_number}
                            </p>
                            <p className="mt-2 text-sm text-charcoal-500">
                                {formatWhen(sale.created_at)}
                            </p>
                            <p className="mt-1 text-sm capitalize text-charcoal-500">
                                Status: {status}
                            </p>
                        </div>
                    </header>

                    <section className="mt-6 grid gap-6 sm:grid-cols-2">
                        <div>
                            <h2 className="text-xs font-semibold uppercase tracking-[0.16em] text-charcoal-300">
                                Bill to
                            </h2>
                            <p className="mt-2 font-medium text-charcoal-900">
                                {sale.customer?.name ?? 'Walk-in customer'}
                            </p>
                            {sale.customer?.phone ? (
                                <p className="mt-1 text-sm text-charcoal-500">
                                    {sale.customer.phone}
                                </p>
                            ) : null}
                        </div>
                        <div className="sm:text-right">
                            <h2 className="text-xs font-semibold uppercase tracking-[0.16em] text-charcoal-300">
                                Details
                            </h2>
                            <p className="mt-2 text-sm text-charcoal-600">
                                Served by: {sale.staff?.name ?? '—'}
                            </p>
                            <p className="mt-1 text-sm text-charcoal-600">
                                Source:{' '}
                                {sale.appointment_id
                                    ? 'Appointment'
                                    : 'Walk-in'}
                            </p>
                            {sale.appointment?.starts_at ? (
                                <p className="mt-1 text-sm text-charcoal-600">
                                    Appointment:{' '}
                                    {formatWhen(sale.appointment.starts_at)}
                                </p>
                            ) : null}
                            {payment ? (
                                <p className="mt-1 text-sm capitalize text-charcoal-600">
                                    Payment: {payment.method}
                                </p>
                            ) : null}
                        </div>
                    </section>

                    <table className="mt-8 w-full text-left text-sm">
                        <thead>
                            <tr className="border-b border-charcoal-100 text-charcoal-500">
                                <th className="pb-3 font-medium">Item</th>
                                <th className="pb-3 text-right font-medium">
                                    Qty
                                </th>
                                <th className="pb-3 text-right font-medium">
                                    Rate
                                </th>
                                <th className="pb-3 text-right font-medium">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-charcoal-50">
                            {(sale.items ?? []).map((item) => (
                                <tr key={item.id}>
                                    <td className="py-3 text-charcoal-800">
                                        {item.name_snapshot}
                                    </td>
                                    <td className="py-3 text-right text-charcoal-600">
                                        {item.qty}
                                    </td>
                                    <td className="py-3 text-right text-charcoal-600">
                                        {formatMoney(item.unit_price)}
                                    </td>
                                    <td className="py-3 text-right font-medium text-charcoal-900">
                                        {formatMoney(item.line_total)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <section className="mt-6 ml-auto max-w-xs space-y-2 text-sm">
                        <div className="flex justify-between text-charcoal-600">
                            <span>Subtotal</span>
                            <span>{formatMoney(sale.subtotal)}</span>
                        </div>
                        <div className="flex justify-between text-charcoal-600">
                            <span>Discount</span>
                            <span>-{formatMoney(sale.discount)}</span>
                        </div>
                        {Number(sale.tax) > 0 ? (
                            <div className="flex justify-between text-charcoal-600">
                                <span>
                                    VAT {Number(sale.tax_rate)}%
                                    {sale.prices_include_vat
                                        ? ' (included)'
                                        : ''}
                                </span>
                                <span>{formatMoney(sale.tax)}</span>
                            </div>
                        ) : null}
                        <div className="flex justify-between border-t border-charcoal-100 pt-3 text-base font-semibold text-charcoal-900">
                            <span>Total</span>
                            <span>{formatMoney(sale.total)}</span>
                        </div>
                    </section>

                    <footer className="mt-10 border-t border-charcoal-50 pt-4 text-center text-xs text-charcoal-300">
                        Thank you for visiting {salon?.name || 'Pretty Salon'}.
                        {salon?.tagline ? ` ${salon.tagline}.` : ''}
                    </footer>
                </article>
            </div>

            <style>{`
                @media print {
                    @page { margin: 12mm; }
                    body { background: white !important; }
                }
            `}</style>
        </div>
    );
}
