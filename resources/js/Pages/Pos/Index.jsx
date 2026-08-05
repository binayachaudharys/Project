import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import usePosCart from '@/Hooks/usePosCart';
import { useEffect, useState } from 'react';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
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

export default function PosIndex({ services, packages, products }) {
    const cart = usePosCart();
    const page = usePage();
    const errors = page.props.errors ?? {};
    const flash = page.props.flash ?? {};
    const catalogs = { services, packages, products };

    const [phone, setPhone] = useState('');
    const [customerResults, setCustomerResults] = useState([]);
    const [customer, setCustomer] = useState(null);
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [processing, setProcessing] = useState(false);
    const [paymentInitiate, setPaymentInitiate] = useState(flash.payment_initiate ?? null);
    const [staffRef, setStaffRef] = useState('');

    useEffect(() => {
        if (flash.payment_initiate) {
            setPaymentInitiate(flash.payment_initiate);
        }
    }, [flash.payment_initiate]);

    useEffect(() => {
        if (!paymentInitiate || paymentInitiate.type !== 'redirect' || paymentInitiate.method !== 'POST') {
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
            fetch(`${route('pos.customers')}?phone=${encodeURIComponent(phone)}`, {
                signal: controller.signal,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
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
                payment_method: paymentMethod,
                items: cart.lines.map((line) => ({
                    item_type: line.item_type,
                    item_id: line.item_id,
                    qty: line.qty,
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
                method: paymentMethod === 'cash' ? paymentInitiate.method : paymentMethod,
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

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800">Point of Sale</h2>}>
            <Head title="POS" />

            <div className="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div className="space-y-6 lg:col-span-2">
                    {flash.success && (
                        <p className="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{flash.success}</p>
                    )}

                    {paymentInitiate?.type === 'qr' && (
                        <section className="rounded-lg bg-white p-6 shadow">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Scan to pay</h3>
                            <p className="mt-2 break-all font-mono text-xs text-gray-600">{paymentInitiate.qr_data}</p>
                            <p className="mt-2 text-sm text-gray-500">
                                Amount: {formatPrice(paymentInitiate.meta?.amount ?? cart.total)} · Sale #{paymentInitiate.sale_id}
                            </p>
                        </section>
                    )}

                    {paymentInitiate && (
                        <section className="rounded-lg bg-white p-6 shadow">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Staff confirm (LAN fallback)
                            </h3>
                            <p className="mt-1 text-sm text-gray-500">
                                Mark paid after the customer shows wallet success on their phone.
                            </p>
                            <form onSubmit={confirmStaffPayment} className="mt-3 flex flex-col gap-2 sm:flex-row">
                                <input
                                    type="text"
                                    placeholder="Gateway ref (optional)"
                                    value={staffRef}
                                    onChange={(e) => setStaffRef(e.target.value)}
                                    className="flex-1 rounded-md border-gray-300 text-sm"
                                />
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                                >
                                    Confirm paid
                                </button>
                            </form>
                        </section>
                    )}

                    <section className="rounded-lg bg-white p-6 shadow">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Customer</h3>
                        <input
                            type="text"
                            placeholder="Search by phone (optional)"
                            className="mt-2 block w-full rounded-lg border-gray-300 text-sm"
                            value={phone}
                            onChange={(e) => {
                                setCustomer(null);
                                setPhone(e.target.value);
                            }}
                        />
                        {customerResults.length > 0 && (
                            <ul className="mt-2 divide-y divide-gray-100 rounded-lg border border-gray-100">
                                {customerResults.map((found) => (
                                    <li key={found.id}>
                                        <button
                                            type="button"
                                            className="block w-full px-3 py-2 text-left text-sm hover:bg-gray-50"
                                            onClick={() => selectCustomer(found)}
                                        >
                                            {found.name} — {found.phone}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        {customer && (
                            <p className="mt-2 text-sm text-gray-600">
                                Selling to <strong>{customer.name}</strong>{' '}
                                <button type="button" className="text-rose-500" onClick={() => setCustomer(null)}>
                                    (clear)
                                </button>
                            </p>
                        )}
                    </section>

                    {CATALOG_SECTIONS.map(({ title, type, key }) => (
                        <section key={type} className="rounded-lg bg-white p-6 shadow">
                            <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">{title}</h3>
                            <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                {catalogs[key].map((sellable) => (
                                    <button
                                        key={sellable.id}
                                        type="button"
                                        onClick={() => cart.addLine(sellable, type)}
                                        disabled={type === 'product' && sellable.stock_qty <= 0}
                                        className="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-left text-sm hover:border-rose-300 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        <span>{sellable.name}</span>
                                        <span className="font-medium text-gray-700">{formatPrice(sellable.price)}</span>
                                    </button>
                                ))}
                                {catalogs[key].length === 0 && <p className="text-sm text-gray-400">Nothing available.</p>}
                            </div>
                        </section>
                    ))}
                </div>

                <div className="lg:col-span-1">
                    <form onSubmit={submit} className="sticky top-6 space-y-4 rounded-lg bg-white p-6 shadow">
                        <h3 className="text-sm font-semibold uppercase tracking-wide text-gray-500">Cart</h3>

                        {cart.lines.length === 0 ? (
                            <p className="text-sm text-gray-400">No items added yet.</p>
                        ) : (
                            <ul className="space-y-3">
                                {cart.lines.map((line) => (
                                    <li key={`${line.item_type}:${line.item_id}`} className="flex items-center justify-between gap-2 text-sm">
                                        <div className="flex-1">
                                            <p className="font-medium text-gray-800">{line.name}</p>
                                            <p className="text-xs text-gray-400">{formatPrice(line.price)} each</p>
                                        </div>
                                        <input
                                            type="number"
                                            min="1"
                                            value={line.qty}
                                            onChange={(e) => cart.updateQty(line.item_type, line.item_id, e.target.value)}
                                            className="w-16 rounded-md border-gray-300 text-sm"
                                        />
                                        <button
                                            type="button"
                                            className="text-xs text-rose-500"
                                            onClick={() => cart.removeLine(line.item_type, line.item_id)}
                                        >
                                            Remove
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {errors.items && <p className="text-sm text-red-600">{errors.items}</p>}

                        <div className="space-y-2 border-t border-gray-100 pt-4">
                            <div className="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="0"
                                    value={cart.discount}
                                    onChange={(e) => cart.setDiscount(e.target.value)}
                                    className="w-24 rounded-md border-gray-300 text-sm"
                                />
                                <select
                                    value={cart.discountType}
                                    onChange={(e) => cart.setDiscountType(e.target.value)}
                                    className="rounded-md border-gray-300 text-sm"
                                >
                                    <option value="amount">Rs. off</option>
                                    <option value="percent">% off</option>
                                </select>
                            </div>

                            <div className="flex justify-between text-sm text-gray-600">
                                <span>Subtotal</span>
                                <span>{formatPrice(cart.subtotal)}</span>
                            </div>
                            <div className="flex justify-between text-sm text-gray-600">
                                <span>Discount</span>
                                <span>-{formatPrice(cart.discountAmount)}</span>
                            </div>
                            <div className="flex justify-between text-base font-semibold text-gray-900">
                                <span>Total</span>
                                <span>{formatPrice(cart.total)}</span>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-2">
                            {PAYMENT_METHODS.map((method) => (
                                <button
                                    key={method.value}
                                    type="button"
                                    onClick={() => setPaymentMethod(method.value)}
                                    className={`rounded-lg border px-3 py-2 text-sm font-medium ${
                                        paymentMethod === method.value
                                            ? 'border-rose-500 bg-rose-50 text-rose-600'
                                            : 'border-gray-200 text-gray-600 hover:border-rose-200'
                                    }`}
                                >
                                    {method.label}
                                </button>
                            ))}
                        </div>
                        {errors.payment_method && <p className="text-sm text-red-600">{errors.payment_method}</p>}

                        <button
                            type="submit"
                            disabled={processing || cart.lines.length === 0}
                            className="w-full rounded-full bg-rose-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-rose-600 disabled:opacity-50"
                        >
                            {paymentMethod === 'cash' ? 'Complete Cash Sale' : `Charge via ${paymentMethod}`}
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
