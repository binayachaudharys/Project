import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function ServiceForm({ service }) {
    const isEdit = Boolean(service?.id);
    const { data, setData, post, put, processing, errors } = useForm({
        name: service?.name ?? '',
        category: service?.category ?? '',
        description: service?.description ?? '',
        duration_minutes: service?.duration_minutes ?? 30,
        price: service?.price ?? '',
        is_active: service?.is_active ?? true,
    });

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.services.update', service.id));
        } else {
            post(route('admin.services.store'));
        }
    };

    return (
        <AdminLayout title={isEdit ? 'Edit service' : 'New service'}>
            <Head title={isEdit ? 'Edit service' : 'New service'} />
            <div className="mb-6 flex items-center justify-between">
                <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                    {isEdit ? 'Edit service' : 'New service'}
                </h1>
                <Link href={route('admin.services.index')} className="text-sm text-rose-600">
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
                    <InputLabel htmlFor="category" value="Category" />
                    <TextInput
                        id="category"
                        className="mt-1 block w-full"
                        value={data.category}
                        onChange={(e) => setData('category', e.target.value)}
                        placeholder="Hair Treatments"
                    />
                    <InputError message={errors.category} className="mt-1" />
                </div>
                <div>
                    <InputLabel htmlFor="description" value="Description" />
                    <textarea
                        id="description"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows={3}
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                    />
                    <InputError message={errors.description} className="mt-1" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="duration_minutes" value="Duration (min)" />
                        <TextInput
                            id="duration_minutes"
                            type="number"
                            className="mt-1 block w-full"
                            value={data.duration_minutes}
                            onChange={(e) => setData('duration_minutes', e.target.value)}
                            required
                        />
                        <InputError message={errors.duration_minutes} className="mt-1" />
                    </div>
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
