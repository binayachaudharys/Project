import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';

export default function SettingsIndex({ settings }) {
    const { data, setData, put, processing, errors } = useForm({
        salon_open: settings.salon_open ?? '10:00',
        salon_close: settings.salon_close ?? '19:00',
        slot_minutes: settings.slot_minutes ?? 30,
        package_duration: settings.package_duration ?? 60,
        auto_confirm: Boolean(settings.auto_confirm),
        max_concurrent: settings.max_concurrent ?? 1,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.settings.update'));
    };

    return (
        <AdminLayout title="Settings">
            <Head title="Admin · Settings" />
            <h1 className="font-display text-3xl font-semibold text-charcoal-900">
                Salon settings
            </h1>

            <form
                onSubmit={submit}
                className="mt-8 max-w-xl space-y-5 rounded-2xl border border-rose-100 bg-white p-6"
            >
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="salon_open" value="Open" />
                        <TextInput
                            id="salon_open"
                            type="time"
                            className="mt-1 block w-full"
                            value={data.salon_open}
                            onChange={(e) => setData('salon_open', e.target.value)}
                            required
                        />
                        <InputError message={errors.salon_open} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="salon_close" value="Close" />
                        <TextInput
                            id="salon_close"
                            type="time"
                            className="mt-1 block w-full"
                            value={data.salon_close}
                            onChange={(e) => setData('salon_close', e.target.value)}
                            required
                        />
                        <InputError message={errors.salon_close} className="mt-1" />
                    </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel htmlFor="slot_minutes" value="Slot minutes" />
                        <TextInput
                            id="slot_minutes"
                            type="number"
                            className="mt-1 block w-full"
                            value={data.slot_minutes}
                            onChange={(e) => setData('slot_minutes', e.target.value)}
                            required
                        />
                        <InputError message={errors.slot_minutes} className="mt-1" />
                    </div>
                    <div>
                        <InputLabel htmlFor="package_duration" value="Package duration" />
                        <TextInput
                            id="package_duration"
                            type="number"
                            className="mt-1 block w-full"
                            value={data.package_duration}
                            onChange={(e) => setData('package_duration', e.target.value)}
                            required
                        />
                        <InputError message={errors.package_duration} className="mt-1" />
                    </div>
                </div>
                <div>
                    <InputLabel htmlFor="max_concurrent" value="Max concurrent appointments" />
                    <TextInput
                        id="max_concurrent"
                        type="number"
                        className="mt-1 block w-full"
                        value={data.max_concurrent}
                        onChange={(e) => setData('max_concurrent', e.target.value)}
                        required
                    />
                    <InputError message={errors.max_concurrent} className="mt-1" />
                </div>
                <label className="flex items-center gap-2 text-sm text-charcoal-700">
                    <input
                        type="checkbox"
                        checked={Boolean(data.auto_confirm)}
                        onChange={(e) => setData('auto_confirm', e.target.checked)}
                    />
                    Auto-confirm bookings
                </label>
                <InputError message={errors.auto_confirm} className="mt-1" />
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-full bg-rose-500 px-5 py-2 text-sm font-semibold text-blush-50 hover:bg-rose-600 disabled:opacity-50"
                >
                    Save settings
                </button>
            </form>
        </AdminLayout>
    );
}
