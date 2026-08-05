import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

export default function StaffIndex({ staff }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.staff.store'), {
            onSuccess: () => reset(),
        });
    };

    return (
        <AdminLayout title="Staff">
            <Head title="Admin · Staff" />
            <h1 className="font-display text-3xl font-semibold text-charcoal-900">Staff</h1>

            <div className="mt-8 grid gap-8 lg:grid-cols-2">
                <div className="overflow-hidden rounded-2xl border border-rose-100 bg-white">
                    <table className="min-w-full divide-y divide-rose-100 text-sm">
                        <thead className="bg-blush-50 text-left text-charcoal-500">
                            <tr>
                                <th className="px-4 py-3 font-medium">Name</th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                <th className="px-4 py-3 font-medium">Role</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-rose-50">
                            {staff.map((user) => (
                                <tr key={user.id}>
                                    <td className="px-4 py-3 font-medium text-charcoal-900">
                                        {user.name}
                                    </td>
                                    <td className="px-4 py-3">{user.email}</td>
                                    <td className="px-4 py-3 capitalize">{user.role}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-2xl border border-rose-100 bg-white p-6"
                >
                    <h2 className="font-display text-xl font-semibold text-charcoal-900">
                        Add staff user
                    </h2>
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
                        <InputLabel htmlFor="email" value="Email" />
                        <TextInput
                            id="email"
                            type="email"
                            className="mt-1 block w-full"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        <InputError message={errors.email} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="phone" value="Phone" />
                        <TextInput
                            id="phone"
                            className="mt-1 block w-full"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                        />
                        <InputError message={errors.phone} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="password" value="Password" />
                        <TextInput
                            id="password"
                            type="password"
                            className="mt-1 block w-full"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                        />
                        <InputError message={errors.password} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="password_confirmation" value="Confirm password" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            className="mt-1 block w-full"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 hover:bg-rose-600 disabled:opacity-50"
                    >
                        Create staff
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
