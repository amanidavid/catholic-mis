import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, useForm } from '@inertiajs/react';

export default function MarriagesCreate() {
    const { data, setData, post, processing, errors } = useForm({
        groom_member_uuid: '',
        bride_mode: 'registered',
        bride_member_uuid: '',
        bride_external_full_name: '',
        bride_external_phone: '',
        bride_external_address: '',
        bride_external_home_parish_name: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('marriages.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Marriage Request" />

            <div className="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Create Marriage Request</h1>
                    <p className="mt-1 text-sm text-slate-600">Step 1: Select groom and bride, then save draft to continue.</p>
                </div>

                <form onSubmit={submit} className="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <SearchableMemberSelect
                            id="groom_member_uuid"
                            label="Groom"
                            value={data.groom_member_uuid}
                            onChange={(uuid) => setData('groom_member_uuid', uuid)}
                            disabled={processing}
                            error={errors.groom_member_uuid}
                        />
                        <InputError className="mt-2" message={errors.groom_member_uuid} />
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="bride_mode">
                            Bride
                        </label>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="radio"
                                    name="bride_mode"
                                    value="registered"
                                    checked={data.bride_mode === 'registered'}
                                    onChange={() => {
                                        setData((prev) => ({
                                            ...prev,
                                            bride_mode: 'registered',
                                            bride_external_full_name: '',
                                            bride_external_phone: '',
                                            bride_external_address: '',
                                            bride_external_home_parish_name: '',
                                        }));
                                    }}
                                    disabled={processing}
                                />
                                Registered bride
                            </label>
                            <label className="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="radio"
                                    name="bride_mode"
                                    value="external"
                                    checked={data.bride_mode === 'external'}
                                    onChange={() => {
                                        setData((prev) => ({
                                            ...prev,
                                            bride_mode: 'external',
                                            bride_member_uuid: '',
                                        }));
                                    }}
                                    disabled={processing}
                                />
                                External bride (not registered)
                            </label>
                        </div>

                        {data.bride_mode === 'registered' ? (
                            <div className="mt-3">
                                <SearchableMemberSelect
                                    id="bride_member_uuid"
                                    label="Select registered bride"
                                    value={data.bride_member_uuid}
                                    onChange={(uuid) => setData('bride_member_uuid', uuid)}
                                    allowExternal
                                    disabled={processing}
                                    error={errors.bride_member_uuid}
                                />
                                <InputError className="mt-2" message={errors.bride_member_uuid} />
                            </div>
                        ) : (
                            <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="bride_external_full_name">
                                        External bride full name
                                    </label>
                                    <input
                                        id="bride_external_full_name"
                                        type="text"
                                        value={data.bride_external_full_name}
                                        onChange={(e) => setData('bride_external_full_name', e.target.value)}
                                        disabled={processing}
                                        className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    />
                                    <InputError className="mt-2" message={errors.bride_external_full_name} />
                                </div>

                                <div>
                                    <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="bride_external_phone">
                                        Phone
                                    </label>
                                    <input
                                        id="bride_external_phone"
                                        type="text"
                                        value={data.bride_external_phone}
                                        onChange={(e) => setData('bride_external_phone', e.target.value)}
                                        disabled={processing}
                                        className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    />
                                    <InputError className="mt-2" message={errors.bride_external_phone} />
                                </div>

                                <div>
                                    <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="bride_external_home_parish_name">
                                        Home parish name
                                    </label>
                                    <input
                                        id="bride_external_home_parish_name"
                                        type="text"
                                        value={data.bride_external_home_parish_name}
                                        onChange={(e) => setData('bride_external_home_parish_name', e.target.value)}
                                        disabled={processing}
                                        className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    />
                                    <InputError className="mt-2" message={errors.bride_external_home_parish_name} />
                                </div>

                                <div className="sm:col-span-2">
                                    <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="bride_external_address">
                                        Address
                                    </label>
                                    <input
                                        id="bride_external_address"
                                        type="text"
                                        value={data.bride_external_address}
                                        onChange={(e) => setData('bride_external_address', e.target.value)}
                                        disabled={processing}
                                        className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    />
                                    <InputError className="mt-2" message={errors.bride_external_address} />
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('marriages.index'))}>
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton type="submit" className="h-11 bg-indigo-700 hover:bg-indigo-800" disabled={processing}>
                            Save Draft
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
