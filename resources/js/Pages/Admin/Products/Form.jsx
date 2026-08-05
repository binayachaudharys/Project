import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ProductForm({ product }) {
    const isEdit = Boolean(product?.id);
    const { data, setData, post, put, processing, errors } = useForm({
        name: product?.name ?? '',
        sku: product?.sku ?? '',
        price: product?.price ?? '',
        stock_qty: product?.stock_qty ?? 0,
        low_stock_threshold: product?.low_stock_threshold ?? 5,
        is_active: product?.is_active ?? true,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.products.update', product.id));
        } else {
            post(route('admin.products.store'));
        }
    };

    return (
        <AdminLayout title={isEdit ? 'Edit product' : 'New product'}>
            <Head title={isEdit ? 'Edit product' : 'New product'} />
            <div className="mb-6 flex items-center justify-between">
                <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                    {isEdit ? 'Edit product' : 'New product'}
                </h1>
                <Link href={route('admin.products.index')} className="text-sm text-rose-600">
                    Back
                </Link>
            </div>

            <form onSubmit={submit} className="max-w-xl space-y-5 rounded-2xl border border-rose-100 bg-white p-6">
                <div>
                    <InputLabel htmlFor="name" value="Name" />
                    <TextInput
                        id="name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    <InputError message={errors.name} className="mt-1" />
                </div>
                <div>
                    <InputLabel htmlFor="sku" value="SKU" />
                    <TextInput
                        id="sku"
                        className="mt-1 block w-full"
                        value={data.sku}
                        onChange={(e) => setData('sku', e.target.value)}
                    />
                    <InputError message={errors.sku} className="mt-1" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="price" value="Price" />
                        <TextInput
                            id="price"
                            type="number"
                            step="0.01"
                            className="mt-1 block w-full"
                            value={data.price}
                            onChange={(e) => setData('price', e.target.value)}
                            required
                        />
                        <InputError message={errors.price} className="mt-1" />
                    </div>
                    {!isEdit && (
                        <div>
                            <InputLabel htmlFor="stock_qty" value="Initial stock" />
                            <TextInput
                                id="stock_qty"
                                type="number"
                                className="mt-1 block w-full"
                                value={data.stock_qty}
                                onChange={(e) => setData('stock_qty', e.target.value)}
                            />
                            <InputError message={errors.stock_qty} className="mt-1" />
                        </div>
                    )}
                    <div>
                        <InputLabel htmlFor="low_stock_threshold" value="Low stock at" />
                        <TextInput
                            id="low_stock_threshold"
                            type="number"
                            className="mt-1 block w-full"
                            value={data.low_stock_threshold}
                            onChange={(e) => setData('low_stock_threshold', e.target.value)}
                        />
                        <InputError message={errors.low_stock_threshold} className="mt-1" />
                    </div>
                </div>
                <label className="flex items-center gap-2 text-sm text-charcoal-700">
                    <input
                        type="checkbox"
                        checked={Boolean(data.is_active)}
                        onChange={(e) => setData('is_active', e.target.checked)}
                    />
                    Active
                </label>
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 hover:bg-rose-600 disabled:opacity-50"
                >
                    {isEdit ? 'Save' : 'Create'}
                </button>
            </form>
        </AdminLayout>
    );
}
