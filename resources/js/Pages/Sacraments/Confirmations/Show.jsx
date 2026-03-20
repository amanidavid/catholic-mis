import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import FloatingSelect from '@/Components/FloatingSelect';
import FloatingFileInput from '@/Components/FloatingFileInput';
import FloatingTextarea from '@/Components/FloatingTextarea';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function ConfirmationsShow({ registration, cycles }) {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const can = (permissionName) => Array.isArray(permissions) && permissions.includes(permissionName);

    const r = registration?.data ?? registration;

    const [rejectOpen, setRejectOpen] = useState(false);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState('');
    const [previewTitle, setPreviewTitle] = useState('Document preview');

    const rejectForm = useForm({ reason: '' });
    const uploadForm = useForm({ type: '', file: null });

    const draftForm = useForm({
        cycle_uuid: r?.cycle?.uuid ?? '',
        family_uuid: r?.family?.uuid ?? '',
        member_uuid: r?.member?.uuid ?? '',
    });

    const attachmentsByType = useMemo(() => {
        const all = Array.isArray(r?.attachments) ? r.attachments : [];
        const byType = {};
        all.forEach((a) => {
            const t = (a?.type ?? '').toString();
            byType[t] = byType[t] ? [...byType[t], a] : [a];
        });
        Object.keys(byType).forEach((k) => {
            byType[k] = (byType[k] ?? []).slice().sort((x, y) => (y?.id ?? 0) - (x?.id ?? 0));
        });
        return byType;
    }, [r]);

    const statusMeta = (status) => {
        const s = (status ?? '').toString().toLowerCase();
        if (s === 'draft') return { label: 'Draft', cls: 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' };
        if (s === 'submitted') return { label: 'Submitted', cls: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' };
        if (s === 'approved') return { label: 'Approved', cls: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' };
        if (s === 'completed') return { label: 'Completed', cls: 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200' };
        if (s === 'rejected') return { label: 'Rejected', cls: 'bg-rose-50 text-rose-800 ring-1 ring-rose-200' };
        if (s === 'issued') return { label: 'Issued', cls: 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200' };
        return { label: status ?? '—', cls: 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' };
    };

    const canEditDraft = can('confirmations.register') && ['draft', 'rejected'].includes((r?.status ?? '').toString().toLowerCase());
    const canSubmit = can('confirmations.register') && ['draft', 'rejected'].includes((r?.status ?? '').toString().toLowerCase());

    const hasAnyAttachment = (Array.isArray(r?.attachments) ? r.attachments : []).length > 0;

    const canApprove = can('confirmations.approve') && (r?.status ?? '') === 'submitted';
    const canReject = can('confirmations.reject') && (r?.status ?? '') === 'submitted';
    const canComplete = can('confirmations.complete') && (r?.status ?? '') === 'approved';
    const canIssue = can('confirmations.issue') && (r?.status ?? '') === 'completed';

    const upload = (e) => {
        e.preventDefault();
        uploadForm.post(route('program-registrations.attachments.store', r.uuid), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                uploadForm.reset('type', 'file');
                router.reload({ only: ['registration'] });
            },
        });
    };

    const removeAttachment = (attachmentUuid) => {
        router.delete(route('program-registrations.attachments.destroy', { registration: r.uuid, attachment: attachmentUuid }), {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['registration'] }),
        });
    };

    const saveDraft = (e) => {
        e.preventDefault();
        draftForm.post(route('confirmations.draft.save', r.uuid), {
            preserveScroll: true,
            onSuccess: () => router.reload({ only: ['registration', 'cycles'] }),
        });
    };

    const submitRegistration = () => {
        router.post(route('confirmations.submit', r.uuid), {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['registration'] }) });
    };

    const approve = () => {
        router.post(route('confirmations.approve', r.uuid), {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['registration'] }) });
    };

    const reject = (e) => {
        e.preventDefault();
        rejectForm.post(route('confirmations.reject', r.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setRejectOpen(false);
                rejectForm.reset('reason');
                router.reload({ only: ['registration'] });
            },
        });
    };

    const complete = () => {
        router.post(route('confirmations.complete', r.uuid), {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['registration'] }) });
    };

    const issue = () => {
        router.post(route('confirmations.issue', r.uuid), {}, { preserveScroll: true, onSuccess: () => router.reload({ only: ['registration'] }) });
    };

    const openPreview = (attachment) => {
        const url = `${route('program-registrations.attachments.download', { registration: r.uuid, attachment: attachment.uuid })}?disposition=inline`;
        setPreviewUrl(url);
        setPreviewTitle(attachment?.original_name ?? 'Document preview');
        setPreviewOpen(true);
    };

    const AttachmentList = ({ type, title, required, hint }) => {
        const items = attachmentsByType?.[type] ?? [];

        return (
            <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div>
                    <div className="text-sm font-semibold text-slate-900">{title}</div>
                    <div className="mt-1 text-xs text-slate-500">Type: {type}{required ? ' (required)' : ' (optional)'}</div>
                    {hint ? <div className="mt-1 text-xs text-slate-500">{hint}</div> : null}
                </div>

                {items.length === 0 ? (
                    <div className="mt-4 text-sm text-slate-600">Not uploaded.</div>
                ) : (
                    <div className="mt-4 space-y-2">
                        {items.map((a) => (
                            <div key={a.uuid} className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div className="text-sm font-semibold text-slate-900">{a.original_name}</div>
                                    <div className="mt-0.5 text-xs text-slate-500">{a.created_at ?? ''}</div>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <SecondaryButton type="button" className="h-10" onClick={() => openPreview(a)}>
                                        Preview
                                    </SecondaryButton>
                                    <SecondaryButton
                                        type="button"
                                        className="h-10"
                                        onClick={() => window.location.assign(route('program-registrations.attachments.download', { registration: r.uuid, attachment: a.uuid }))}
                                    >
                                        Download
                                    </SecondaryButton>
                                    {canEditDraft ? (
                                        <SecondaryButton
                                            type="button"
                                            className="h-10 border border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100"
                                            onClick={() => removeAttachment(a.uuid)}
                                        >
                                            Remove
                                        </SecondaryButton>
                                    ) : null}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        );
    };

    const candidateName = r?.member?.full_name ?? '—';

    return (
        <AuthenticatedLayout>
            <Head title="Confirmation Registration" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Confirmation Registration</h1>
                        <p className="mt-1 text-sm text-slate-600">Submit for parish review after required prerequisites are satisfied.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('confirmations.index'))}>
                            Back
                        </SecondaryButton>
                        {canSubmit ? (
                            <PrimaryButton type="button" className="h-11 bg-indigo-700 hover:bg-indigo-800" onClick={submitRegistration}>
                                Submit
                            </PrimaryButton>
                        ) : null}
                        {canApprove ? (
                            <PrimaryButton type="button" className="h-11 bg-emerald-700 hover:bg-emerald-800" onClick={approve}>
                                Approve
                            </PrimaryButton>
                        ) : null}
                        {canReject ? (
                            <SecondaryButton type="button" className="h-11 border border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100" onClick={() => setRejectOpen(true)}>
                                Reject
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

                <div className="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${statusMeta(r?.status).cls}`}>{statusMeta(r?.status).label}</span>
                        </div>
                        <div className="text-xs text-slate-500">Created: {r?.created_at ?? '—'}</div>
                    </div>

                    {r?.status === 'rejected' && r?.rejection_reason ? (
                        <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <div className="text-xs font-semibold uppercase tracking-wide">Rejection reason</div>
                            <div className="mt-1 whitespace-pre-wrap">{r.rejection_reason}</div>
                        </div>
                    ) : null}

                    <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Candidate</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{candidateName}</div>
                            <div className="mt-1 text-xs text-slate-600">Family: {r?.family?.family_name ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-600">Jumuiya: {r?.origin_jumuiya?.name ?? '—'}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Cycle</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{r?.cycle?.name ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-600">Opens: {r?.cycle?.registration_opens_at ?? '—'}</div>
                            <div className="mt-1 text-xs text-slate-600">Closes: {r?.cycle?.registration_closes_at ?? '—'}</div>
                        </div>
                    </div>

                    {canEditDraft ? (
                        <form onSubmit={saveDraft} className="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Draft details</div>
                            {hasAnyAttachment ? (
                                <div className="mt-2 text-xs text-amber-900">Attachments already uploaded: cycle, family, and candidate cannot be changed.</div>
                            ) : null}

                            <div className="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                <FloatingSelect
                                    id="draft_cycle_uuid"
                                    label="Program cycle"
                                    value={draftForm.data.cycle_uuid}
                                    onChange={(e) => draftForm.setData('cycle_uuid', e.target.value)}
                                    disabled={draftForm.processing || hasAnyAttachment}
                                    error={draftForm.errors.cycle_uuid}
                                >
                                    <option value="">Select cycle...</option>
                                    {(cycles ?? []).map((c) => (
                                        <option key={c.uuid} value={c.uuid}>{c.name}</option>
                                    ))}
                                </FloatingSelect>

                                <div>
                                    <SearchableFamilySelect
                                        id="draft_family_uuid"
                                        label="Family"
                                        value={draftForm.data.family_uuid}
                                        onChange={(uuid) => {
                                            draftForm.setData('family_uuid', uuid);
                                            draftForm.setData('member_uuid', '');
                                        }}
                                        disabled={draftForm.processing || hasAnyAttachment}
                                        error={draftForm.errors.family_uuid}
                                    />
                                </div>
                            </div>

                            <div className="mt-4">
                                <SearchableMemberSelect
                                    id="draft_member_uuid"
                                    label="Candidate"
                                    value={draftForm.data.member_uuid}
                                    onChange={(uuid) => draftForm.setData('member_uuid', uuid)}
                                    familyUuid={draftForm.data.family_uuid}
                                    disabled={draftForm.processing || hasAnyAttachment || !draftForm.data.family_uuid}
                                    error={draftForm.errors.member_uuid}
                                />
                            </div>

                            <div className="mt-4 flex items-center justify-end">
                                <PrimaryButton type="submit" className="h-11" disabled={draftForm.processing}>
                                    Save draft changes
                                </PrimaryButton>
                            </div>
                        </form>
                    ) : null}

                    <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        If First Communion was completed in another parish, a transfer approval letter must be uploaded before submission.
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                    <div className="lg:col-span-3 space-y-6">
                        <AttachmentList
                            type="transfer_approval_letter"
                            title="Transfer approval letter"
                            required={false}
                            hint="Required only if First Communion was completed in another parish."
                        />
                    </div>

                    <div className="lg:col-span-2 space-y-6">
                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="text-sm font-semibold text-slate-900">Upload document</div>
                            <p className="mt-1 text-xs text-slate-500">One file per type. Re-upload replaces the previous file.</p>

                            <form onSubmit={upload} className="mt-4 space-y-3">
                                <div>
                                    <FloatingSelect
                                        id="doc_type"
                                        label="Document type"
                                        value={uploadForm.data.type}
                                        onChange={(e) => uploadForm.setData('type', e.target.value)}
                                        disabled={uploadForm.processing || !canEditDraft}
                                        error={uploadForm.errors.type}
                                    >
                                        <option value="">Select type...</option>
                                        <option value="transfer_approval_letter">Transfer approval letter</option>
                                    </FloatingSelect>
                                </div>

                                <div>
                                    <FloatingFileInput
                                        id="doc_file"
                                        label="File (PDF)"
                                        accept="application/pdf"
                                        disabled={uploadForm.processing || !canEditDraft}
                                        onChange={(e) => uploadForm.setData('file', e.target.files?.[0] ?? null)}
                                        error={uploadForm.errors.file}
                                    />
                                </div>

                                <div className="flex items-center justify-end">
                                    <PrimaryButton type="submit" className="h-11" disabled={uploadForm.processing || !canEditDraft}>
                                        Upload
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <Modal show={rejectOpen} onClose={() => setRejectOpen(false)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-semibold text-slate-900">Reject registration</h2>
                    <p className="mt-1 text-sm text-slate-600">Provide a reason that will be visible to the leader.</p>

                    <form onSubmit={reject} className="mt-4 space-y-3">
                        <div>
                            <FloatingTextarea
                                id="reason"
                                label="Reason"
                                value={rejectForm.data.reason}
                                onChange={(e) => rejectForm.setData('reason', e.target.value)}
                                error={rejectForm.errors.reason}
                                rows={4}
                            />
                        </div>

                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton type="button" className="h-11" onClick={() => setRejectOpen(false)}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" className="h-11 bg-rose-700 hover:bg-rose-800" disabled={rejectForm.processing}>
                                Reject
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={previewOpen} onClose={() => setPreviewOpen(false)} maxWidth="4xl">
                <div className="p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-slate-900">{previewTitle}</h2>
                        <SecondaryButton type="button" className="h-11" onClick={() => setPreviewOpen(false)}>
                            Close
                        </SecondaryButton>
                    </div>

                    <div className="mt-4 overflow-hidden rounded-xl border border-slate-200">
                        {previewUrl ? <iframe title="preview" src={previewUrl} className="h-[75vh] w-full" /> : null}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
