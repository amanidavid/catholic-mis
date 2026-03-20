import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function MarriagesShow({ marriage, schedule, scheduleChanges }) {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const can = (permissionName) => Array.isArray(permissions) && permissions.includes(permissionName);

    const marriageData = marriage?.data ?? marriage;

    const canEditDraft = can('marriages.request.edit') && ['draft', 'submitted', 'rejected'].includes(marriageData?.status);
    const canSubmit = can('marriages.request.submit') && marriageData?.status === 'draft';
    const canApprove = can('marriages.approve') && marriageData?.status === 'submitted';
    const canReject = can('marriages.reject') && ['submitted', 'approved'].includes(marriageData?.status);
    const canSchedule = can('marriages.schedule.manage') && marriageData?.status === 'approved';
    const canComplete = can('marriages.schedule.manage') && marriageData?.status === 'approved';
    const canIssue = can('marriages.issue') && marriageData?.status === 'completed';

    const [rejectOpen, setRejectOpen] = useState(false);
    const [scheduleOpen, setScheduleOpen] = useState(false);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState('');

    const rejectForm = useForm({ reason: '' });
    const scheduleForm = useForm({
        scheduled_for: schedule?.scheduled_for ? schedule.scheduled_for.replace(' ', 'T') : '',
        location_text: schedule?.location_text ?? '',
        reason: '',
    });

    const uploadForm = useForm({ type: '', file: null });

    const parentsByParty = useMemo(() => {
        const items = Array.isArray(marriageData?.parents) ? marriageData.parents : [];
        const byParty = { groom: null, bride: null };
        items.forEach((p) => {
            if (p?.party === 'groom') byParty.groom = p;
            if (p?.party === 'bride') byParty.bride = p;
        });
        return byParty;
    }, [marriageData]);

    const draftForm = useForm({
        marriage_date: marriageData?.marriage_date ?? '',
        marriage_time: marriageData?.marriage_time ?? '',
        wedding_type: marriageData?.wedding_type ?? '',
        bride_external_full_name: marriageData?.bride_external?.full_name ?? '',
        bride_external_phone: marriageData?.bride_external?.phone ?? '',
        bride_external_address: marriageData?.bride_external?.address ?? '',
        bride_external_home_parish_name: marriageData?.bride_external?.home_parish_name ?? '',
        bride_external_zone_name: marriageData?.bride_external?.zone_name ?? '',
        bride_external_jumuiya_name: marriageData?.bride_external?.jumuiya_name ?? '',
        male_witness_name: marriageData?.witnesses?.male?.name ?? '',
        male_witness_phone: marriageData?.witnesses?.male?.phone ?? '',
        male_witness_address: marriageData?.witnesses?.male?.address ?? '',
        male_witness_relationship: marriageData?.witnesses?.male?.relationship ?? '',
        female_witness_name: marriageData?.witnesses?.female?.name ?? '',
        female_witness_phone: marriageData?.witnesses?.female?.phone ?? '',
        female_witness_address: marriageData?.witnesses?.female?.address ?? '',
        female_witness_relationship: marriageData?.witnesses?.female?.relationship ?? '',
        sponsors: Array.isArray(marriageData?.sponsors) ? marriageData.sponsors.map((s) => ({
            role: s.role ?? '',
            full_name: s.full_name ?? '',
            phone: s.phone ?? '',
            address: s.address ?? '',
            relationship: s.relationship ?? '',
            notes: s.notes ?? '',
        })) : [],
        groom_parents: {
            father_name: parentsByParty?.groom?.father_name ?? '',
            father_phone: parentsByParty?.groom?.father_phone ?? '',
            father_religion: parentsByParty?.groom?.father_religion ?? '',
            father_is_alive: parentsByParty?.groom?.father_is_alive ?? null,
            mother_name: parentsByParty?.groom?.mother_name ?? '',
            mother_phone: parentsByParty?.groom?.mother_phone ?? '',
            mother_religion: parentsByParty?.groom?.mother_religion ?? '',
            mother_is_alive: parentsByParty?.groom?.mother_is_alive ?? null,
        },
        bride_parents: {
            father_name: parentsByParty?.bride?.father_name ?? '',
            father_phone: parentsByParty?.bride?.father_phone ?? '',
            father_religion: parentsByParty?.bride?.father_religion ?? '',
            father_is_alive: parentsByParty?.bride?.father_is_alive ?? null,
            mother_name: parentsByParty?.bride?.mother_name ?? '',
            mother_phone: parentsByParty?.bride?.mother_phone ?? '',
            mother_religion: parentsByParty?.bride?.mother_religion ?? '',
            mother_is_alive: parentsByParty?.bride?.mother_is_alive ?? null,
        },
    });

    const addSponsor = () => {
        draftForm.setData('sponsors', [...(draftForm.data.sponsors ?? []), { role: '', full_name: '', phone: '', address: '', relationship: '', notes: '' }]);
    };

    const removeSponsor = (idx) => {
        const next = [...(draftForm.data.sponsors ?? [])];
        next.splice(idx, 1);
        draftForm.setData('sponsors', next);
    };

    const saveDraft = (e) => {
        e.preventDefault();
        draftForm.post(route('marriages.draft.save', marriageData.uuid), { preserveScroll: true });
    };

    const attachmentsByType = useMemo(() => {
        const all = Array.isArray(marriageData?.attachments) ? marriageData.attachments : [];
        const byType = {};
        all.forEach((a) => {
            const t = (a?.type ?? '').toString();
            byType[t] = a;
        });
        return byType;
    }, [marriageData]);

    const requiredTypes = useMemo(() => {
        const req = ['groom_baptism_certificate', 'bride_baptism_certificate'];
        const isExternalBride = !marriageData?.bride?.id;
        if (isExternalBride) {
            req.push('bride_home_parish_letter');
        } else if (marriageData?.bride_parish?.id && marriageData?.groom_parish?.id && marriageData.bride_parish.id !== marriageData.groom_parish.id) {
            req.push('bride_home_parish_letter');
        }
        return req;
    }, [marriageData]);

    const missingRequirements = useMemo(() => {
        const missing = [];

        const groomP = parentsByParty?.groom;
        const brideP = parentsByParty?.bride;

        const groomFatherOk = (groomP?.father_name ?? '').toString().trim() !== '';
        const groomMotherOk = (groomP?.mother_name ?? '').toString().trim() !== '';
        const brideFatherOk = (brideP?.father_name ?? '').toString().trim() !== '';
        const brideMotherOk = (brideP?.mother_name ?? '').toString().trim() !== '';

        if (!groomFatherOk) missing.push('Groom father details');
        if (!groomMotherOk) missing.push('Groom mother details');
        if (!brideFatherOk) missing.push('Bride father details');
        if (!brideMotherOk) missing.push('Bride mother details');

        requiredTypes.forEach((t) => {
            if (!attachmentsByType?.[t]) {
                if (t === 'groom_baptism_certificate') missing.push('Groom baptism certificate');
                else if (t === 'bride_baptism_certificate') missing.push('Bride baptism certificate');
                else if (t === 'bride_home_parish_letter') missing.push('Bride home parish letter');
                else missing.push(t);
            }
        });

        return missing;
    }, [attachmentsByType, parentsByParty, requiredTypes]);

    const upload = (e) => {
        e.preventDefault();
        uploadForm.post(route('marriages.attachments.store', marriageData.uuid), { preserveScroll: true });
    };

    const removeAttachment = (attachmentUuid) => {
        router.delete(route('marriages.attachments.destroy', { marriage: marriageData.uuid, attachment: attachmentUuid }), { preserveScroll: true });
    };

    const refresh = () => {
        router.get(route('marriages.show', marriageData.uuid), {}, { preserveScroll: true, replace: true });
    };

    const submit = () => {
        router.post(route('marriages.submit', marriageData.uuid), {}, {
            preserveScroll: true,
            onSuccess: () => refresh(),
        });
    };

    const approve = () => {
        router.post(route('marriages.approve', marriageData.uuid), {}, {
            preserveScroll: true,
            onSuccess: () => refresh(),
        });
    };

    const reject = (e) => {
        e.preventDefault();
        rejectForm.post(route('marriages.reject', marriageData.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setRejectOpen(false);
                refresh();
            },
        });
    };

    const saveSchedule = (e) => {
        e.preventDefault();
        scheduleForm.post(route('marriages.schedule', marriageData.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setScheduleOpen(false);
                refresh();
            },
        });
    };

    const complete = () => {
        router.post(route('marriages.complete', marriageData.uuid), {}, {
            preserveScroll: true,
            onSuccess: () => refresh(),
        });
    };

    const issue = () => {
        router.post(route('marriages.issue', marriageData.uuid), {}, {
            preserveScroll: true,
            onSuccess: () => refresh(),
        });
    };

    const AttachmentRow = ({ type, label, required }) => {
        const item = attachmentsByType?.[type];
        return (
            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <div className="text-sm font-semibold text-slate-900">{label}</div>
                        <div className="mt-1 text-xs text-slate-500">Type: {type}{required ? ' (required)' : ' (optional)'}</div>
                        <div className="mt-2 text-xs text-slate-600">{item ? item.original_name : 'Not uploaded.'}</div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {item ? (
                            <>
                                <SecondaryButton
                                    type="button"
                                    className="h-10"
                                    onClick={() => {
                                        setPreviewUrl(`${route('marriages.attachments.download', { marriage: marriageData.uuid, attachment: item.uuid })}?disposition=inline`);
                                        setPreviewOpen(true);
                                    }}
                                >
                                    Preview
                                </SecondaryButton>
                                <SecondaryButton type="button" className="h-10" onClick={() => window.location.assign(route('marriages.attachments.download', { marriage: marriageData.uuid, attachment: item.uuid }))}>
                                    Download
                                </SecondaryButton>
                                {canEditDraft ? (
                                    <SecondaryButton type="button" className="h-10 border border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100" onClick={() => removeAttachment(item.uuid)}>
                                        Remove
                                    </SecondaryButton>
                                ) : null}
                            </>
                        ) : null}
                    </div>
                </div>
            </div>
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Marriage Request" />

            <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Marriage Request</h1>
                        <p className="mt-1 text-sm text-slate-600">Upload required documents, then submit for parish review.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('marriages.index'))}>
                            Back
                        </SecondaryButton>
                        {canSubmit ? (
                            <PrimaryButton type="button" className="h-11 bg-indigo-700 hover:bg-indigo-800" onClick={submit}>
                                Submit
                            </PrimaryButton>
                        ) : null}
                        {canApprove ? (
                            <PrimaryButton type="button" className="h-11 bg-emerald-600 hover:bg-emerald-700" onClick={approve}>
                                Approve
                            </PrimaryButton>
                        ) : null}
                        {canReject ? (
                            <SecondaryButton type="button" className="h-11 border border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100" onClick={() => setRejectOpen(true)}>
                                Reject
                            </SecondaryButton>
                        ) : null}
                        {canSchedule ? (
                            <SecondaryButton type="button" className="h-11" onClick={() => setScheduleOpen(true)}>
                                Schedule
                            </SecondaryButton>
                        ) : null}
                        {canComplete ? (
                            <SecondaryButton type="button" className="h-11" onClick={complete}>
                                Mark completed
                            </SecondaryButton>
                        ) : null}
                        {canIssue ? (
                            <PrimaryButton type="button" className="h-11 bg-indigo-700 hover:bg-indigo-800" onClick={issue}>
                                Issue
                            </PrimaryButton>
                        ) : null}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <div className="space-y-6 lg:col-span-4">
                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="text-sm font-semibold text-slate-900">Request details</div>
                            <dl className="mt-4 space-y-3 text-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Status</dt>
                                    <dd className="text-right font-semibold text-slate-900">{marriageData.status}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Groom</dt>
                                    <dd className="text-right font-semibold text-slate-900">{marriageData?.groom?.full_name ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Bride</dt>
                                    <dd className="text-right font-semibold text-slate-900">
                                        {marriageData?.bride?.full_name ?? marriageData?.bride_external?.full_name ?? '—'}
                                    </dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Groom parish</dt>
                                    <dd className="text-right text-slate-900">{marriageData?.groom_parish?.name ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Bride parish</dt>
                                    <dd className="text-right text-slate-900">{marriageData?.bride_parish?.name ?? marriageData?.bride_external?.home_parish_name ?? '—'}</dd>
                                </div>
                                {marriageData?.rejection_reason ? (
                                    <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                                        {marriageData.rejection_reason}
                                    </div>
                                ) : null}
                            </dl>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="text-sm font-semibold text-slate-900">Affiliations</div>
                            <p className="mt-1 text-xs text-slate-500">Bride and groom can belong to different Christian Communities, zones, and parishes.</p>

                            {!marriageData?.bride?.id || (marriageData?.bride_parish?.id && marriageData?.groom_parish?.id && marriageData.bride_parish.id !== marriageData.groom_parish.id) ? (
                                <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                    External parish case: bride parish differs from groom parish. Bride home parish letter is required before submission.
                                </div>
                            ) : null}

                            <div className="mt-4 space-y-4">
                                <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div className="text-sm font-semibold text-slate-900">Groom</div>
                                    <dl className="mt-3 grid gap-2 text-sm">
                                        <div className="grid grid-cols-2 gap-3">
                                            <dt className="text-slate-500">Jumuiya</dt>
                                            <dd className="text-right font-medium text-slate-900">{marriageData?.groom_jumuiya?.name ?? '—'}</dd>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <dt className="text-slate-500">Zone</dt>
                                            <dd className="text-right font-medium text-slate-900">{marriageData?.groom_zone?.name ?? '—'}</dd>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <dt className="text-slate-500">Parish</dt>
                                            <dd className="text-right font-medium text-slate-900">{marriageData?.groom_parish?.name ?? '—'}</dd>
                                        </div>
                                    </dl>
                                </div>

                                <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div className="text-sm font-semibold text-slate-900">Bride</div>
                                    <dl className="mt-3 grid gap-2 text-sm">
                                        <div className="grid grid-cols-2 gap-3">
                                            <dt className="text-slate-500">Jumuiya</dt>
                                            <dd className="text-right font-medium text-slate-900">{marriageData?.bride_jumuiya?.name ?? '—'}</dd>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <dt className="text-slate-500">Zone</dt>
                                            <dd className="text-right font-medium text-slate-900">{marriageData?.bride_zone?.name ?? '—'}</dd>
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <dt className="text-slate-500">Parish</dt>
                                            <dd className="text-right font-medium text-slate-900">{marriageData?.bride_parish?.name ?? marriageData?.bride_external?.home_parish_name ?? '—'}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <div className="text-sm font-semibold text-slate-900">Submit readiness</div>
                                    <p className="mt-1 text-xs text-slate-500">
                                        You can only submit after required parents details and required documents are provided.
                                    </p>
                                </div>
                            </div>

                            {missingRequirements.length === 0 ? (
                                <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                                    Ready to submit.
                                </div>
                            ) : (
                                <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                    Missing:
                                    <div className="mt-2 text-xs text-amber-900">
                                        {missingRequirements.join(', ')}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="text-sm font-semibold text-slate-900">Upload documents</div>
                            <p className="mt-1 text-xs text-slate-500">One file per type. Re-upload will replace the previous file.</p>

                            <form onSubmit={upload} className="mt-4 space-y-3">
                                <div>
                                    <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="doc_type">Document type</label>
                                    <select
                                        id="doc_type"
                                        value={uploadForm.data.type}
                                        onChange={(e) => uploadForm.setData('type', e.target.value)}
                                        disabled={uploadForm.processing || !canEditDraft}
                                        className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    >
                                        <option value="">Select type...</option>
                                        <option value="groom_baptism_certificate">Groom baptism certificate (required)</option>
                                        <option value="bride_baptism_certificate">Bride baptism certificate (required)</option>
                                        <option value="groom_confirmation_certificate">Groom confirmation certificate (optional)</option>
                                        <option value="bride_confirmation_certificate">Bride confirmation certificate (optional)</option>
                                        <option value="bride_home_parish_letter">Bride home parish letter (required if external parish)</option>
                                        <option value="groom_id_document">Groom ID document (optional)</option>
                                        <option value="bride_id_document">Bride ID document (optional)</option>
                                    </select>
                                    <InputError className="mt-2" message={uploadForm.errors.type} />
                                </div>
                                <div>
                                    <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="doc_file">File (PDF)</label>
                                    <input
                                        id="doc_file"
                                        type="file"
                                        accept="application/pdf"
                                        disabled={uploadForm.processing || !canEditDraft}
                                        onChange={(e) => uploadForm.setData('file', e.target.files?.[0] ?? null)}
                                        className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                    />
                                    <InputError className="mt-2" message={uploadForm.errors.file} />
                                </div>
                                <div className="flex items-center justify-end">
                                    <PrimaryButton type="submit" className="h-11" disabled={uploadForm.processing || !canEditDraft}>
                                        Upload
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div className="lg:col-span-8">
                        <div className="space-y-4">
                            <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <div className="text-sm font-semibold text-slate-900">Marriage details (draft)</div>
                                        <p className="mt-1 text-xs text-slate-500">Fill in these details and save draft. Required fields must be completed before submission.</p>
                                    </div>
                                    {canEditDraft ? (
                                        <PrimaryButton type="button" className="h-11" onClick={saveDraft} disabled={draftForm.processing}>
                                            Save draft
                                        </PrimaryButton>
                                    ) : null}
                                </div>

                                <form onSubmit={saveDraft} className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="marriage_date">Preferred date</label>
                                        <input
                                            id="marriage_date"
                                            type="date"
                                            value={draftForm.data.marriage_date}
                                            onChange={(e) => draftForm.setData('marriage_date', e.target.value)}
                                            disabled={!canEditDraft}
                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                        />
                                        <InputError className="mt-2" message={draftForm.errors.marriage_date} />
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="marriage_time">Preferred time</label>
                                        <input
                                            id="marriage_time"
                                            type="time"
                                            value={draftForm.data.marriage_time}
                                            onChange={(e) => draftForm.setData('marriage_time', e.target.value)}
                                            disabled={!canEditDraft}
                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                        />
                                        <InputError className="mt-2" message={draftForm.errors.marriage_time} />
                                    </div>
                                    <div>
                                        <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="wedding_type">Wedding type</label>
                                        <input
                                            id="wedding_type"
                                            type="text"
                                            value={draftForm.data.wedding_type}
                                            onChange={(e) => draftForm.setData('wedding_type', e.target.value)}
                                            disabled={!canEditDraft}
                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                            placeholder="e.g. Mass, Blessing"
                                        />
                                        <InputError className="mt-2" message={draftForm.errors.wedding_type} />
                                    </div>

                                    <div className="sm:col-span-3">
                                        {!marriageData?.bride ? (
                                            <div className="rounded-xl border border-slate-200 p-4">
                                                <div className="text-sm font-semibold text-slate-900">External bride details</div>
                                                <div className="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                    <div className="sm:col-span-2">
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Full name</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_external_full_name}
                                                            onChange={(e) => draftForm.setData('bride_external_full_name', e.target.value)}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Phone</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_external_phone}
                                                            onChange={(e) => draftForm.setData('bride_external_phone', e.target.value)}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Home parish name</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_external_home_parish_name}
                                                            onChange={(e) => draftForm.setData('bride_external_home_parish_name', e.target.value)}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Zone (optional)</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_external_zone_name}
                                                            onChange={(e) => draftForm.setData('bride_external_zone_name', e.target.value)}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                        <InputError className="mt-2" message={draftForm.errors.bride_external_zone_name} />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Jumuiya (optional)</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_external_jumuiya_name}
                                                            onChange={(e) => draftForm.setData('bride_external_jumuiya_name', e.target.value)}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                        <InputError className="mt-2" message={draftForm.errors.bride_external_jumuiya_name} />
                                                    </div>
                                                    <div className="sm:col-span-2">
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Address</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_external_address}
                                                            onChange={(e) => draftForm.setData('bride_external_address', e.target.value)}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        ) : null}

                                        <div className="mt-2 grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div className="rounded-xl border border-slate-200 p-4">
                                                <div className="text-sm font-semibold text-slate-900">Groom parents</div>
                                                <div className="mt-3 grid grid-cols-1 gap-3">
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Father full name</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.groom_parents.father_name}
                                                            onChange={(e) => draftForm.setData('groom_parents', { ...draftForm.data.groom_parents, father_name: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Father phone (optional)</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.groom_parents.father_phone}
                                                            onChange={(e) => draftForm.setData('groom_parents', { ...draftForm.data.groom_parents, father_phone: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                        <InputError className="mt-2" message={draftForm.errors['groom_parents.father_phone']} />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Mother full name</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.groom_parents.mother_name}
                                                            onChange={(e) => draftForm.setData('groom_parents', { ...draftForm.data.groom_parents, mother_name: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Mother phone (optional)</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.groom_parents.mother_phone}
                                                            onChange={(e) => draftForm.setData('groom_parents', { ...draftForm.data.groom_parents, mother_phone: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                        <InputError className="mt-2" message={draftForm.errors['groom_parents.mother_phone']} />
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="rounded-xl border border-slate-200 p-4">
                                                <div className="text-sm font-semibold text-slate-900">Bride parents</div>
                                                <div className="mt-3 grid grid-cols-1 gap-3">
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Father full name</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_parents.father_name}
                                                            onChange={(e) => draftForm.setData('bride_parents', { ...draftForm.data.bride_parents, father_name: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Father phone (optional)</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_parents.father_phone}
                                                            onChange={(e) => draftForm.setData('bride_parents', { ...draftForm.data.bride_parents, father_phone: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                        <InputError className="mt-2" message={draftForm.errors['bride_parents.father_phone']} />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Mother full name</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_parents.mother_name}
                                                            onChange={(e) => draftForm.setData('bride_parents', { ...draftForm.data.bride_parents, mother_name: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="mb-1 block text-xs font-semibold text-slate-700">Mother phone (optional)</label>
                                                        <input
                                                            type="text"
                                                            value={draftForm.data.bride_parents.mother_phone}
                                                            onChange={(e) => draftForm.setData('bride_parents', { ...draftForm.data.bride_parents, mother_phone: e.target.value })}
                                                            disabled={!canEditDraft}
                                                            className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                        />
                                                        <InputError className="mt-2" message={draftForm.errors['bride_parents.mother_phone']} />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mt-4 rounded-xl border border-slate-200 p-4">
                                            <div className="text-sm font-semibold text-slate-900">Witnesses (Best man & Maid of honor)</div>
                                            <div className="mt-3 grid grid-cols-1 gap-4 md:grid-cols-2">
                                                <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                    <div className="text-sm font-semibold text-slate-900">Best man</div>
                                                    <div className="mt-3 grid grid-cols-1 gap-3">
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Full name</label>
                                                            <input type="text" value={draftForm.data.male_witness_name} onChange={(e) => draftForm.setData('male_witness_name', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.male_witness_name} />
                                                        </div>
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Phone</label>
                                                            <input type="text" value={draftForm.data.male_witness_phone} onChange={(e) => draftForm.setData('male_witness_phone', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.male_witness_phone} />
                                                        </div>
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Relationship</label>
                                                            <input type="text" value={draftForm.data.male_witness_relationship} onChange={(e) => draftForm.setData('male_witness_relationship', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.male_witness_relationship} />
                                                        </div>
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Address</label>
                                                            <input type="text" value={draftForm.data.male_witness_address} onChange={(e) => draftForm.setData('male_witness_address', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.male_witness_address} />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                    <div className="text-sm font-semibold text-slate-900">Maid of honor</div>
                                                    <div className="mt-3 grid grid-cols-1 gap-3">
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Full name</label>
                                                            <input type="text" value={draftForm.data.female_witness_name} onChange={(e) => draftForm.setData('female_witness_name', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.female_witness_name} />
                                                        </div>
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Phone</label>
                                                            <input type="text" value={draftForm.data.female_witness_phone} onChange={(e) => draftForm.setData('female_witness_phone', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.female_witness_phone} />
                                                        </div>
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Relationship</label>
                                                            <input type="text" value={draftForm.data.female_witness_relationship} onChange={(e) => draftForm.setData('female_witness_relationship', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.female_witness_relationship} />
                                                        </div>
                                                        <div>
                                                            <label className="mb-1 block text-xs font-semibold text-slate-700">Address</label>
                                                            <input type="text" value={draftForm.data.female_witness_address} onChange={(e) => draftForm.setData('female_witness_address', e.target.value)} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                            <InputError className="mt-2" message={draftForm.errors.female_witness_address} />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="mt-4 rounded-xl border border-slate-200 p-4">
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <div className="text-sm font-semibold text-slate-900">Sponsors / Mentor couple (optional)</div>
                                                    <p className="mt-1 text-xs text-slate-500">Add marriage mentors or sponsor names if required by your parish.</p>
                                                </div>
                                                {canEditDraft ? (
                                                    <SecondaryButton type="button" className="h-10" onClick={addSponsor}>
                                                        Add sponsor
                                                    </SecondaryButton>
                                                ) : null}
                                            </div>

                                            {(draftForm.data.sponsors ?? []).length === 0 ? (
                                                <div className="mt-4 text-sm text-slate-600">No sponsors added.</div>
                                            ) : (
                                                <div className="mt-4 space-y-3">
                                                    {(draftForm.data.sponsors ?? []).map((s, idx) => (
                                                        <div key={idx} className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                            <div className="flex items-start justify-between gap-4">
                                                                <div className="text-sm font-semibold text-slate-900">Sponsor #{idx + 1}</div>
                                                                {canEditDraft ? (
                                                                    <SecondaryButton type="button" className="h-10" onClick={() => removeSponsor(idx)}>
                                                                        Remove
                                                                    </SecondaryButton>
                                                                ) : null}
                                                            </div>

                                                            <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                                                                <div>
                                                                    <label className="mb-1 block text-xs font-semibold text-slate-700">Role</label>
                                                                    <input type="text" value={s.role} onChange={(e) => {
                                                                        const next = [...draftForm.data.sponsors];
                                                                        next[idx] = { ...next[idx], role: e.target.value };
                                                                        draftForm.setData('sponsors', next);
                                                                    }} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" placeholder="e.g. Mentor couple" />
                                                                    <InputError className="mt-2" message={draftForm.errors[`sponsors.${idx}.role`]} />
                                                                </div>
                                                                <div>
                                                                    <label className="mb-1 block text-xs font-semibold text-slate-700">Full name</label>
                                                                    <input type="text" value={s.full_name} onChange={(e) => {
                                                                        const next = [...draftForm.data.sponsors];
                                                                        next[idx] = { ...next[idx], full_name: e.target.value };
                                                                        draftForm.setData('sponsors', next);
                                                                    }} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                                    <InputError className="mt-2" message={draftForm.errors[`sponsors.${idx}.full_name`]} />
                                                                </div>
                                                                <div>
                                                                    <label className="mb-1 block text-xs font-semibold text-slate-700">Phone</label>
                                                                    <input type="text" value={s.phone} onChange={(e) => {
                                                                        const next = [...draftForm.data.sponsors];
                                                                        next[idx] = { ...next[idx], phone: e.target.value };
                                                                        draftForm.setData('sponsors', next);
                                                                    }} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                                    <InputError className="mt-2" message={draftForm.errors[`sponsors.${idx}.phone`]} />
                                                                </div>
                                                                <div>
                                                                    <label className="mb-1 block text-xs font-semibold text-slate-700">Relationship</label>
                                                                    <input type="text" value={s.relationship} onChange={(e) => {
                                                                        const next = [...draftForm.data.sponsors];
                                                                        next[idx] = { ...next[idx], relationship: e.target.value };
                                                                        draftForm.setData('sponsors', next);
                                                                    }} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                                    <InputError className="mt-2" message={draftForm.errors[`sponsors.${idx}.relationship`]} />
                                                                </div>
                                                                <div className="md:col-span-2">
                                                                    <label className="mb-1 block text-xs font-semibold text-slate-700">Address</label>
                                                                    <input type="text" value={s.address} onChange={(e) => {
                                                                        const next = [...draftForm.data.sponsors];
                                                                        next[idx] = { ...next[idx], address: e.target.value };
                                                                        draftForm.setData('sponsors', next);
                                                                    }} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" />
                                                                    <InputError className="mt-2" message={draftForm.errors[`sponsors.${idx}.address`]} />
                                                                </div>
                                                                <div className="md:col-span-2">
                                                                    <label className="mb-1 block text-xs font-semibold text-slate-700">Notes</label>
                                                                    <textarea value={s.notes} onChange={(e) => {
                                                                        const next = [...draftForm.data.sponsors];
                                                                        next[idx] = { ...next[idx], notes: e.target.value };
                                                                        draftForm.setData('sponsors', next);
                                                                    }} disabled={!canEditDraft} className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm" rows={2} />
                                                                    <InputError className="mt-2" message={draftForm.errors[`sponsors.${idx}.notes`]} />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>

                                        <div className="mt-4 flex justify-end">
                                            {canEditDraft ? (
                                                <PrimaryButton type="submit" className="h-11" disabled={draftForm.processing}>
                                                    Save draft
                                                </PrimaryButton>
                                            ) : null}
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div className="text-sm font-semibold text-slate-900">Required documents</div>
                                <div className="mt-4 space-y-3">
                                    <AttachmentRow type="groom_baptism_certificate" label="Groom baptism certificate" required />
                                    <AttachmentRow type="bride_baptism_certificate" label="Bride baptism certificate" required />
                                    {requiredTypes.includes('bride_home_parish_letter') ? (
                                        <AttachmentRow type="bride_home_parish_letter" label="Bride home parish letter" required />
                                    ) : null}
                                </div>
                            </div>

                            <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div className="text-sm font-semibold text-slate-900">Optional documents</div>
                                <div className="mt-4 space-y-3">
                                    <AttachmentRow type="groom_confirmation_certificate" label="Groom confirmation certificate" />
                                    <AttachmentRow type="bride_confirmation_certificate" label="Bride confirmation certificate" />
                                    <AttachmentRow type="groom_id_document" label="Groom ID document" />
                                    <AttachmentRow type="bride_id_document" label="Bride ID document" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={rejectOpen} onClose={() => setRejectOpen(false)}>
                <div className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Reject request</div>
                    <form onSubmit={reject} className="mt-4 space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="reject_reason">Reason</label>
                            <textarea
                                id="reject_reason"
                                value={rejectForm.data.reason}
                                onChange={(e) => rejectForm.setData('reason', e.target.value)}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                rows={4}
                            />
                            <InputError className="mt-2" message={rejectForm.errors.reason} />
                        </div>
                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton type="button" className="h-11" onClick={() => setRejectOpen(false)}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" className="h-11" disabled={rejectForm.processing}>
                                Reject
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={scheduleOpen} onClose={() => setScheduleOpen(false)}>
                <div className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Schedule marriage</div>
                    <form onSubmit={saveSchedule} className="mt-4 space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="scheduled_for">Scheduled for</label>
                            <input
                                id="scheduled_for"
                                type="datetime-local"
                                value={scheduleForm.data.scheduled_for}
                                onChange={(e) => scheduleForm.setData('scheduled_for', e.target.value)}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                            />
                            <InputError className="mt-2" message={scheduleForm.errors.scheduled_for} />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="location_text">Location (optional)</label>
                            <input
                                id="location_text"
                                type="text"
                                value={scheduleForm.data.location_text}
                                onChange={(e) => scheduleForm.setData('location_text', e.target.value)}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="schedule_reason">Reason (optional)</label>
                            <textarea
                                id="schedule_reason"
                                value={scheduleForm.data.reason}
                                onChange={(e) => scheduleForm.setData('reason', e.target.value)}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                rows={3}
                            />
                        </div>
                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton type="button" className="h-11" onClick={() => setScheduleOpen(false)}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" className="h-11" disabled={scheduleForm.processing}>
                                Save
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal
                show={previewOpen}
                maxWidth="2xl"
                onClose={() => {
                    setPreviewOpen(false);
                    window.setTimeout(() => setPreviewUrl(''), 150);
                }}
            >
                <div className="p-4">
                    <div className="flex items-center justify-between gap-3">
                        <div className="text-lg font-semibold text-slate-900">Document preview</div>
                        <SecondaryButton
                            type="button"
                            className="h-10"
                            onClick={() => {
                                setPreviewOpen(false);
                                window.setTimeout(() => setPreviewUrl(''), 150);
                            }}
                        >
                            Close
                        </SecondaryButton>
                    </div>

                    <div className="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
                        {previewUrl ? (
                            <iframe title="Document preview" src={previewUrl} className="h-[75vh] w-full" />
                        ) : (
                            <div className="p-6 text-sm text-slate-600">Loading...</div>
                        )}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
