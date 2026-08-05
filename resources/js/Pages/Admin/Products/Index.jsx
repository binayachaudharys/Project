import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';

function formatPrice(price) {
    return `Rs. ${Number(price).toLocaleString('en-IN')}`;
}

function StockAdjust({ product }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        delta: 1,
        reason: 'manual_adjust',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.products.stock', product.id), {
            preserveScroll: true,
            onSuccess: () => reset('delta'),
        });
    };

    return (
        <form onSubmit={submit} className="flex flex-wrap items-center gap-2">
            <input
                type="number"
                className="w-20 rounded-md border-gray-300 text-sm shadow-sm"
                value={data.delta}
                onChange={(e) => setData('delta', e.target.value)}
                required
            />
            <button
                type="submit"
                disabled={processing}
                className="rounded-full bg-blush-100 px-3 py-1 text-xs font-semibold text-rose-700 hover:bg-blush-200"
            >
                Adjust
            </button>
            {errors.delta && <span className="text-xs text-red-600">{errors.delta}</span>}
        </form>
    );
}

export default function ProductsIndex({ products }) {
    const destroy = (id) => {
        if (confirm('Delete this product?')) {
            router.delete(route('admin.products.destroy', id));
        }
    };

    return (
        <AdminLayout title="Products">
            <Head title="Admin · Products" />
            <div className="flex items-center justify-between gap-4">
                <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                    Products
                </h1>
                <Link
                    href={route('admin.products.create')}
                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 hover:bg-rose-600"
                >
                    Add product
                </Link>
            </div>

            <div className="mt-8 overflow-hidden rounded-2xl border border-rose-100 bg-white">
                <table className="min-w-full divide-y divide-rose-100 text-sm">
                    <thead className="bg-blush-50 text-left text-charcoal-500">
                        <tr>
                            <th className="px-4 py-3 font-medium">Name</th>
                            <th className="px-4 py-3 font-medium">SKU</th>
                            <th className="px-4 py-3 font-medium">Price</th>
                            <th className="px-4 py-3 font-medium">Stock</th>
                            <th className="px-4 py-3 font-medium">Adjust</th>
                            <th className="px-4 py-3 font-medium" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-rose-50">
                        {products.map((product) => (
                            <tr key={product.id}>
                                <td className="px-4 py-3 font-medium text-charcoal-900">
                                    {product.name}
                                    {product.stock_qty <= product.low_stock_threshold && (
                                        <span className="ml-2 text-xs text-rose-600">Low</span>
                                    )}
                                </td>
                                <td className="px-4 py-3">{product.sku || '—'}</td>
                                <td className="px-4 py-3">{formatPrice(product.price)}</td>
                                <td className="px-4 py-3">{product.stock_qty}</td>
                                <td className="px-4 py-3">
                                    <StockAdjust product={product} />
                                </td>
                                <td className="px-4 py-3 text-right space-x-3">
                                    <Link
                                        href={route('admin.products.edit', product.id)}
                                        className="text-rose-600 hover:text-rose-700"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        type="button"
                                        onClick={() => destroy(product.id)}
                                        className="text-charcoal-400 hover:text-charcoal-700"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                        {products.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-4 py-8 text-center text-charcoal-500">
                                    No products yet.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}
