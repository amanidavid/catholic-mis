import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

export default function BaptismsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        family_uuid: '',
        member_uuid: '',
    });

    const [parents, setParents] = useState({ father: null, mother: null });

    useEffect(() => {
        let cancelled = false;

        const run = async () => {
            if (!data.family_uuid) {
                setParents({ father: null, mother: null });
                return;
            }

            try {
                const res = await axios.get(route('families.parents-lookup'), {
                    params: { family_uuid: data.family_uuid },
                });
                if (cancelled) return;
                const d = res?.data?.data;
                setParents({ father: d?.father ?? null, mother: d?.mother ?? null });
            } catch {
                if (cancelled) return;
                setParents({ father: null, mother: null });
            }
        };

        run();
        return () => {
            cancelled = true;
        };
    }, [data.family_uuid]);

    const submit = (e) => {
        e.preventDefault();
        post(route('baptisms.store'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Create Baptism Request" />

            <div className="mx-auto max-w-3xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6">
                    <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Create Baptism Request</h1>
                    <p className="mt-1 text-sm text-slate-600">Step 1: Select family and child, then save draft to continue.</p>
                </div>

                <form onSubmit={submit} className="space-y-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
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
                            label="Child"
                            value={data.member_uuid}
                            onChange={(uuid) => setData('member_uuid', uuid)}
                            familyUuid={data.family_uuid}
                            excludeUuids={[parents?.father?.uuid, parents?.mother?.uuid].filter(Boolean)}
                            disabled={processing || !data.family_uuid}
                            error={errors.member_uuid}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Father</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{parents?.father?.name ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-500">{parents?.father?.marital_status ?? ''}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Mother</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{parents?.mother?.name ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-500">{parents?.mother?.marital_status ?? ''}</div>
                        </div>
                    </div>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('baptisms.index'))}>
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
