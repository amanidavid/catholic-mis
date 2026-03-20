import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import SecondaryButton from '@/Components/SecondaryButton';
import FloatingSelect from '@/Components/FloatingSelect';
import { Head, router, useForm } from '@inertiajs/react';

export default function CommunionsCreate({ cycles, default_cycle_uuid }) {
    const { data, setData, post, processing, errors } = useForm({
        cycle_uuid: default_cycle_uuid ?? '',
        family_uuid: '',
        member_uuid: '',
        is_transfer: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('communions.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Communion Registration" />

            <div className="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Create Communion Registration</h1>
                    <p className="mt-1 text-sm text-slate-600">Step 1: Select cycle, family, and candidate, then save draft to continue.</p>
                </div>

                <form onSubmit={submit} className="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div>
                        <FloatingSelect
                            id="cycle_uuid"
                            label="Program cycle"
                            value={data.cycle_uuid}
                            onChange={(e) => setData('cycle_uuid', e.target.value)}
                            disabled={processing}
                            error={errors.cycle_uuid}
                        >
                            <option value="">Select cycle...</option>
                            {(cycles ?? []).map((c) => (
                                <option key={c.uuid} value={c.uuid}>{c.name}</option>
                            ))}
                        </FloatingSelect>
                    </div>

                    <div>
                        <SearchableFamilySelect
                            id="family_uuid"
                            label="Family"
                            value={data.family_uuid}
                            onChange={(uuid) => {
                                setData('family_uuid', uuid);
                                setData('member_uuid', '');
                            }}
                            disabled={processing}
                            error={errors.family_uuid}
                        />
                    </div>

                    <div>
                        <SearchableMemberSelect
                            id="member_uuid"
                            label="Candidate"
                            value={data.member_uuid}
                            onChange={(uuid) => setData('member_uuid', uuid)}
                            familyUuid={data.family_uuid}
                            disabled={processing || !data.family_uuid}
                            error={errors.member_uuid}
                        />
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <label className="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                            <input
                                type="checkbox"
                                checked={!!data.is_transfer}
                                onChange={(e) => setData('is_transfer', e.target.checked)}
                                disabled={processing}
                            />
                            Transfer candidate (studied elsewhere)
                        </label>
                        <p className="mt-2 text-xs text-slate-600">If checked, a parish letter will be required before submission.</p>
                    </div>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('communions.index'))}>
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
