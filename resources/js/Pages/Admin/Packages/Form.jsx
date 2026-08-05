import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function PackageForm({ package: pkg, services }) {
    const isEdit = Boolean(pkg?.id);
    const { data, setData, post, put, processing, errors } = useForm({
        name: pkg?.name ?? '',
        description: pkg?.description ?? '',
        price: pkg?.price ?? '',
        is_active: pkg?.is_active ?? true,
        service_ids: (pkg?.services || []).map((s) => s.id),
    });

    const toggleService = (id) => {
        const next = data.service_ids.includes(id)
            ? data.service_ids.filter((sid) => sid !== id)
            : [...data.service_ids, id];
        setData('service_ids', next);
    };

    const submit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('admin.packages.update', pkg.id));
        } else {
            post(route('admin.packages.store'));
        }
    };

    return (
        <AdminLayout title={isEdit ? 'Edit package' : 'New package'}>
            <Head title={isEdit ? 'Edit package' : 'New package'} />
            <div className="mb-6 flex items-center justify-between">
                <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                    {isEdit ? 'Edit package' : 'New package'}
                </h1>
                <Link href={route('admin.packages.index')} className="text-sm text-rose-600">
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
                <fieldset>
                    <legend className="text-sm font-medium text-charcoal-700">Services</legend>
                    <ul className="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-lg border border-rose-100 p-3">
                        {services.map((service) => (
                            <li key={service.id}>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={data.service_ids.includes(service.id)}
                                        onChange={() => toggleService(service.id)}
                                    />
                                    {service.name}
                                </label>
                            </li>
                        ))}
                    </ul>
                    <InputError message={errors.service_ids} className="mt-1" />
                </fieldset>
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
