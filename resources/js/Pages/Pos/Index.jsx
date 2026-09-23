import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import usePosCart from '@/Hooks/usePosCart';
import { useEffect, useState } from 'react';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

const PAYMENT_METHODS = [
    { value: 'cash', label: 'Cash' },
    { value: 'esewa', label: 'eSewa' },
    { value: 'khalti', label: 'Khalti' },
    { value: 'fonepay', label: 'Fonepay' },
];

const CATALOG_SECTIONS = [
    { title: 'Services', type: 'service', key: 'services' },
    { title: 'Packages', type: 'package', key: 'packages' },
    { title: 'Products', type: 'product', key: 'products' },
];

export default function PosIndex({ services, packages, products, vat }) {
    const vatEnabled = vat?.enabled ?? true;
    const vatRate = Number(vat?.rate ?? 13);
    const vatInclusiveDefault = Boolean(vat?.inclusive);

    const cart = usePosCart({
        vatEnabled,
        vatRate,
        vatInclusive: vatInclusiveDefault,
    });
    const page = usePage();
    const errors = page.props.errors ?? {};
    const flash = page.props.flash ?? {};
    const role = page.props.auth?.user?.role;
    const isOwner = role === 'owner';
    const catalogs = { services, packages, products };

    const [phone, setPhone] = useState('');
    const [customerResults, setCustomerResults] = useState([]);
    const [customer, setCustomer] = useState(null);
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [processing, setProcessing] = useState(false);
    const [paymentInitiate, setPaymentInitiate] = useState(
        flash.payment_initiate ?? null,
    );
    const [staffRef, setStaffRef] = useState('');

    useEffect(() => {
        if (flash.payment_initiate) {
            setPaymentInitiate(flash.payment_initiate);
        }
    }, [flash.payment_initiate]);

    useEffect(() => {
        if (
            !paymentInitiate ||
            paymentInitiate.type !== 'redirect' ||
            paymentInitiate.method !== 'POST'
        ) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = paymentInitiate.url;
        form.style.display = 'none';

        Object.entries(paymentInitiate.params ?? {}).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value ?? '';
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }, [paymentInitiate]);

    useEffect(() => {
        if (phone.trim().length < 2) {
            setCustomerResults([]);
            return;
        }

        const controller = new AbortController();
        const timeout = setTimeout(() => {
            fetch(
                `${route('pos.customers')}?phone=${encodeURIComponent(phone)}`,
                {
                    signal: controller.signal,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                },
            )
                .then((response) => (response.ok ? response.json() : []))
                .then(setCustomerResults)
                .catch(() => {});
        }, 300);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [phone]);

    function selectCustomer(found) {
        setCustomer(found);
        setCustomerResults([]);
        setPhone(found.phone ?? '');
    }

    function submit(e) {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route('pos.checkout'),
            {
                customer_id: customer?.id ?? null,
                discount: cart.discount,
                discount_type: cart.discountType,
                prices_include_vat: cart.pricesIncludeVat,
                payment_method: paymentMethod,
                items: cart.lines.map((line) => ({
                    item_type: line.item_type,
                    item_id: line.item_id,
                    qty: line.qty,
                    unit_price: line.price,
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    cart.clear();
                    setCustomer(null);
                    setPhone('');
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    function confirmStaffPayment(e) {
        e.preventDefault();
        if (!paymentInitiate?.sale_id) {
            return;
        }

        setProcessing(true);
        router.post(
            route('pos.payments.staff-confirm', paymentInitiate.sale_id),
            {
                method:
                    paymentMethod === 'cash'
                        ? paymentInitiate.method
                        : paymentMethod,
                gateway_reference: staffRef || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setPaymentInitiate(null);
                    setStaffRef('');
                },
                onFinish: () => setProcessing(false),
            },
        );
    }

    const content = (
        <>
            <Head title="New invoice" />

            <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                        New invoice
                    </h1>
                    <p className="mt-1 text-sm text-charcoal-500">
                        Add services, packages, or products to the cart. Edit
                        line prices for custom invoices
                        {vatEnabled
                            ? ` · Nepal VAT ${vatRate}%`
                            : ''}
                        .
                    </p>
                </div>
                <Link
                    href={
                        isOwner
                            ? route('admin.billing.index')
                            : route('staff.billing.index')
                    }
                    className="text-sm font-medium text-rose-600 hover:text-rose-700"
                >
                    Open billing list →
                </Link>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    {flash.success && (
                        <p className="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {flash.success}
                        </p>
                    )}

                    {paymentInitiate?.type === 'qr' && (
                        <section className="rounded-2xl border border-rose-100 bg-white p-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-charcoal-500">
                                Scan to pay
                            </h3>
                            <p className="mt-2 break-all font-mono text-xs text-charcoal-600">
                                {paymentInitiate.qr_data}
                            </p>
                            <p className="mt-2 text-sm text-charcoal-500">
                                Amount:{' '}
                                {formatPrice(
                                    paymentInitiate.meta?.amount ?? cart.total,
                                )}{' '}
                                · Sale #{paymentInitiate.sale_id}
                            </p>
                        </section>
                    )}

                    {paymentInitiate && (
                        <section className="rounded-2xl border border-rose-100 bg-white p-6">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-charcoal-500">
                                Staff confirm (LAN fallback)
                            </h3>
                            <p className="mt-1 text-sm text-charcoal-500">
                                Mark paid after the customer shows wallet
                                success on their phone.
                            </p>
                            <form
                                onSubmit={confirmStaffPayment}
                                className="mt-3 flex flex-col gap-2 sm:flex-row"
                            >
                                <input
                                    type="text"
                                    placeholder="Gateway ref (optional)"
                                    value={staffRef}
                                    onChange={(e) =>
                                        setStaffRef(e.target.value)
                                    }
                                    className="flex-1 rounded-md border-charcoal-100 text-sm"
                                />
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-lg bg-charcoal-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                                >
                                    Confirm paid
                                </button>
                            </form>
                        </section>
                    )}

                    <section className="rounded-2xl border border-rose-100 bg-white p-6">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-charcoal-500">
                            Customer
                        </h3>
                        <input
                            type="text"
                            placeholder="Search by phone (optional)"
                            className="mt-2 block w-full rounded-lg border-charcoal-100 text-sm"
                            value={phone}
                            onChange={(e) => {
                                setCustomer(null);
                                setPhone(e.target.value);
                            }}
                        />
                        {customerResults.length > 0 && (
                            <ul className="mt-2 divide-y divide-charcoal-50 rounded-lg border border-charcoal-50">
                                {customerResults.map((found) => (
                                    <li key={found.id}>
                                        <button
                                            type="button"
                                            className="block w-full px-3 py-2 text-left text-sm hover:bg-blush-50"
                                            onClick={() =>
                                                selectCustomer(found)
                                            }
                                        >
                                            {found.name} — {found.phone}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {customer && (
                            <p className="mt-2 text-sm text-charcoal-600">
                                Selling to <strong>{customer.name}</strong>{' '}
                                <button
                                    type="button"
                                    className="text-rose-500"
                                    onClick={() => setCustomer(null)}
                                >
                                    (clear)
                                </button>
                            </p>
                        )}
                    </section>

                    {CATALOG_SECTIONS.map(({ title, type, key }) => (
                        <section
                            key={type}
                            className="rounded-2xl border border-rose-100 bg-white p-6"
                        >
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-charcoal-500">
                                {title}
                            </h3>
                            <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                {catalogs[key].map((sellable) => (
                                    <button
                                        key={sellable.id}
                                        type="button"
                                        onClick={() =>
                                            cart.addLine(sellable, type)
                                        }
                                        disabled={
                                            type === 'product' &&
                                            sellable.stock_qty <= 0
                                        }
                                        className="flex items-center justify-between rounded-lg border border-charcoal-50 px-4 py-3 text-left text-sm hover:border-rose-300 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <span>{sellable.name}</span>
                                        <span className="font-medium text-charcoal-700">
                                            {formatPrice(sellable.price)}
                                        </span>
                                    </button>
                                ))}
                                {catalogs[key].length === 0 && (
                                    <p className="text-sm text-charcoal-300">
                                        Nothing available.
                                    </p>
                                )}
                            </div>
                        </section>
                    ))}
                </div>

                <div className="lg:col-span-1">
                    <form
                        onSubmit={submit}
                        className="sticky top-6 space-y-4 rounded-2xl border border-rose-100 bg-white p-6"
                    >
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-charcoal-500">
                            Cart / invoice
                        </h3>

                        {cart.lines.length === 0 ? (
                            <p className="text-sm text-charcoal-300">
                                No items added yet.
                            </p>
                        ) : (
                            <ul className="space-y-4">
                                {cart.lines.map((line) => (
                                    <li
                                        key={`${line.item_type}:${line.item_id}`}
                                        className="space-y-2 text-sm"
                                    >
                                        <div className="flex items-start justify-between gap-2">
                                            <p className="font-medium text-charcoal-800">
                                                {line.name}
                                            </p>
                                            <button
                                                type="button"
                                                className="text-xs text-rose-500"
                                                onClick={() =>
                                                    cart.removeLine(
                                                        line.item_type,
                                                        line.item_id,
                                                    )
                                                }
                                            >
                                                Remove
                                            </button>
                                        </div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <label className="block text-[11px] uppercase tracking-wide text-charcoal-300">
                                                Qty
                                                <input
                                                    type="number"
                                                    min="1"
                                                    value={line.qty}
                                                    onChange={(e) =>
                                                        cart.updateQty(
                                                            line.item_type,
                                                            line.item_id,
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="mt-1 w-full rounded-md border-charcoal-100 text-sm"
                                                />
                                            </label>
                                            <label className="block text-[11px] uppercase tracking-wide text-charcoal-300">
                                                Unit price
                                                <input
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={line.price}
                                                    onChange={(e) =>
                                                        cart.updatePrice(
                                                            line.item_type,
                                                            line.item_id,
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="mt-1 w-full rounded-md border-charcoal-100 text-sm"
                                                    aria-label={`Invoice price for ${line.name}`}
                                                />
                                            </label>
                                        </div>
                                        {Number(line.price) !==
                                        Number(line.catalog_price) ? (
                                            <p className="text-[11px] text-amber-700">
                                                Catalog{' '}
                                                {formatPrice(
                                                    line.catalog_price,
                                                )}{' '}
                                                · custom invoice price
                                            </p>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        )}

                        {errors.items && (
                            <p className="text-sm text-red-600">
                                {errors.items}
                            </p>
                        )}

                        <div className="space-y-2 border-t border-charcoal-50 pt-4">
                            <div className="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="0"
                                    value={cart.discount}
                                    onChange={(e) =>
                                        cart.setDiscount(e.target.value)
                                    }
                                    className="w-24 rounded-md border-charcoal-100 text-sm"
                                    aria-label="Discount"
                                />
                                <select
                                    value={cart.discountType}
                                    onChange={(e) =>
                                        cart.setDiscountType(e.target.value)
                                    }
                                    className="rounded-md border-charcoal-100 text-sm"
                                >
                                    <option value="amount">Rs. off</option>
                                    <option value="percent">% off</option>
                                </select>
                            </div>

                            {vatEnabled ? (
                                <label className="flex items-center gap-2 text-xs text-charcoal-600">
                                    <input
                                        type="checkbox"
                                        checked={cart.pricesIncludeVat}
                                        onChange={(e) =>
                                            cart.setPricesIncludeVat(
                                                e.target.checked,
                                            )
                                        }
                                    />
                                    Prices include VAT ({vatRate}%)
                                </label>
                            ) : null}

                            <div className="flex justify-between text-sm text-charcoal-600">
                                <span>Subtotal</span>
                                <span>{formatPrice(cart.subtotal)}</span>
                            </div>
                            <div className="flex justify-between text-sm text-charcoal-600">
                                <span>Discount</span>
                                <span>
                                    -{formatPrice(cart.discountAmount)}
                                </span>
                            </div>
                            {vatEnabled ? (
                                <div className="flex justify-between text-sm text-charcoal-600">
                                    <span>
                                        VAT {vatRate}%
                                        {cart.pricesIncludeVat
                                            ? ' (incl.)'
                                            : ''}
                                    </span>
                                    <span>{formatPrice(cart.tax)}</span>
                                </div>
                            ) : null}
                            <div className="flex justify-between text-base font-semibold text-charcoal-900">
                                <span>Total</span>
                                <span>{formatPrice(cart.total)}</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            {PAYMENT_METHODS.map((method) => (
                                <button
                                    key={method.value}
                                    type="button"
                                    onClick={() =>
                                        setPaymentMethod(method.value)
                                    }
                                    className={`rounded-lg border px-3 py-2 text-sm font-medium ${
                                        paymentMethod === method.value
                                            ? 'border-rose-500 bg-rose-50 text-rose-600'
                                            : 'border-charcoal-50 text-charcoal-600 hover:border-rose-200'
                                    }`}
                                >
                                    {method.label}
                                </button>
                            ))}
                        </div>
                        {errors.payment_method && (
                            <p className="text-sm text-red-600">
                                {errors.payment_method}
                            </p>
                        )}

                        <button
                            type="submit"
                            disabled={processing || cart.lines.length === 0}
                            className="w-full rounded-full bg-rose-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-rose-600 disabled:opacity-50"
                        >
                            {paymentMethod === 'cash'
                                ? 'Complete cash invoice'
                                : `Charge via ${paymentMethod}`}
                        </button>
                    </form>
                </div>
            </div>
        </>
    );

    if (isOwner) {
        return <AdminLayout title="New invoice">{content}</AdminLayout>;
    }

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold text-gray-800">
                    New invoice
                </h2>
            }
        >
            <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                {content}
            </div>
        </AuthenticatedLayout>
    );
}
