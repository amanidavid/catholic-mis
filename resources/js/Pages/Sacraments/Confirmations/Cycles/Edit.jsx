import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

const toLocalInput = (v) => {
    if (!v) return '';
    return v.toString().slice(0, 10);
};

export default function ConfirmationCyclesEdit({ cycle }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: cycle?.name ?? '',
        status: cycle?.status ?? 'draft',
        registration_opens_at: toLocalInput(cycle?.registration_opens_at),
        registration_closes_at: toLocalInput(cycle?.registration_closes_at),
        late_registration_closes_at: toLocalInput(cycle?.late_registration_closes_at),
    });

    useEffect(() => {
        setData({
            name: cycle?.name ?? '',
            status: cycle?.status ?? 'draft',
            registration_opens_at: toLocalInput(cycle?.registration_opens_at),
            registration_closes_at: toLocalInput(cycle?.registration_closes_at),
            late_registration_closes_at: toLocalInput(cycle?.late_registration_closes_at),
        });
    }, [cycle?.uuid]);

    const submit = (e) => {
        e.preventDefault();
        patch(route('confirmations.cycles.update', cycle.uuid), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Edit Confirmation Cycle" />

            <div className="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Edit Confirmation Cycle</h1>
                    <p className="mt-1 text-sm text-slate-600">Update window and status.</p>
                </div>

                <form onSubmit={submit} className="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="name">Name</label>
                        <input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            disabled={processing}
                            className="block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400"
                        />
                        <InputError className="mt-2" message={errors.name} />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="opens">Opens at</label>
                            <input
                                id="opens"
                                type="date"
                                value={data.registration_opens_at}
                                onChange={(e) => setData('registration_opens_at', e.target.value)}
                                disabled={processing}
                                className="block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400"
                            />
                            <InputError className="mt-2" message={errors.registration_opens_at} />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="closes">Closes at</label>
                            <input
                                id="closes"
                                type="date"
                                value={data.registration_closes_at}
                                onChange={(e) => setData('registration_closes_at', e.target.value)}
                                disabled={processing}
                                className="block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400"
                            />
                            <InputError className="mt-2" message={errors.registration_closes_at} />
                        </div>
                        <div className="sm:col-span-2">
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="late">Late closes at</label>
                            <input
                                id="late"
                                type="date"
                                value={data.late_registration_closes_at}
                                onChange={(e) => setData('late_registration_closes_at', e.target.value)}
                                disabled={processing}
                                className="block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400"
                            />
                            <InputError className="mt-2" message={errors.late_registration_closes_at} />
                        </div>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="status">Status</label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            disabled={processing}
                            className="block h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400"
                        >
                            <option value="draft">Draft</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                            <option value="archived">Archived</option>
                        </select>
                        <InputError className="mt-2" message={errors.status} />
                    </div>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('confirmations.cycles.index'))}>
                            Back
                        </SecondaryButton>
                        <PrimaryButton type="submit" className="h-11 bg-indigo-700 hover:bg-indigo-800" disabled={processing}>
                            Save
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
