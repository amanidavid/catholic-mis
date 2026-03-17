import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import Modal from '@/Components/Modal';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import axios from 'axios';

export default function BaptismsShow({ baptism, marriageCertificate, schedule, scheduleChanges }) {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const can = (permissionName) => Array.isArray(permissions) && permissions.includes(permissionName);

    const baptismData = baptism?.data ?? baptism;

    const [rejectOpen, setRejectOpen] = useState(false);
    const [scheduleOpen, setScheduleOpen] = useState(false);
    const [editBaptismOpen, setEditBaptismOpen] = useState(false);
    const [changeSubjectOpen, setChangeSubjectOpen] = useState(false);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState('');
    const [previewTitle, setPreviewTitle] = useState('Document preview');

    const submitForm = useForm({
        sponsor_role: '',
        sponsor_member_uuid: '',
        sponsor_full_name: '',
        sponsor_parish_name: '',
        sponsor_phone: '',
        sponsor_email: '',

        parents_marriage_certificate: null,
        sponsor_confirmation_certificate: null,
        birth_certificate: null,
    });

    const approveForm = useForm({});
    const rejectForm = useForm({ reason: '' });
    const editBaptismForm = useForm({
        birth_date: baptismData?.birth_date ?? '',
        birth_town: baptismData?.birth_town ?? '',
        residence: baptismData?.residence ?? '',
    });

    const changeSubjectForm = useForm({
        family_uuid: baptismData?.family?.uuid ?? '',
        member_uuid: baptismData?.member?.uuid ?? '',
    });

    const [subjectParents, setSubjectParents] = useState({ father: null, mother: null });
    const scheduleForm = useForm({
        scheduled_for: schedule?.scheduled_for ? schedule.scheduled_for.replace(' ', 'T') : '',
        location_text: schedule?.location_text ?? '',
        reason: '',
    });
    const completeForm = useForm({});
    const issueForm = useForm({});

    const childName = useMemo(() => {
        const m = baptismData?.member;
        if (!m) return '—';
        return [m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' ');
    }, [baptismData]);

    const basicComplete = !!baptismData?.id;

    const sponsorComplete = useMemo(() => {
        const hasExisting = (baptismData?.sponsors ?? []).length > 0;
        if (hasExisting) return true;
        const hasMember = !!(submitForm.data.sponsor_member_uuid && submitForm.data.sponsor_member_uuid.toString().trim() !== '');
        const hasName = !!(submitForm.data.sponsor_full_name && submitForm.data.sponsor_full_name.toString().trim() !== '');
        return hasMember || hasName;
    }, [baptismData, submitForm.data.sponsor_member_uuid, submitForm.data.sponsor_full_name]);

    const attachmentsComplete = useMemo(() => {
        const hasMarriageExisting = (baptismData?.attachments ?? []).some((a) => a?.type === 'parents_marriage_certificate') || !!marriageCertificate;
        const hasSponsorConfirmExisting = (baptismData?.attachments ?? []).some((a) => a?.type === 'sponsor_confirmation_certificate');

        const hasMarriage = hasMarriageExisting || !!submitForm.data.parents_marriage_certificate;
        const hasSponsorConfirm = hasSponsorConfirmExisting || !!submitForm.data.sponsor_confirmation_certificate;

        return hasMarriage && hasSponsorConfirm;
    }, [baptismData, marriageCertificate, submitForm.data.parents_marriage_certificate, submitForm.data.sponsor_confirmation_certificate]);

    const attachmentsByType = useMemo(() => {
        const all = Array.isArray(baptismData?.attachments) ? baptismData.attachments : [];
        const byType = {
            parents_marriage_certificate: [],
            sponsor_confirmation_certificate: [],
            birth_certificate: [],
        };

        all.forEach((a) => {
            const t = (a?.type ?? '').toString();
            if (byType[t]) byType[t].push(a);
        });

        Object.keys(byType).forEach((k) => {
            byType[k] = byType[k].slice().sort((x, y) => (y?.id ?? 0) - (x?.id ?? 0));
        });

        return byType;
    }, [baptismData]);

    const fetchSubjectParents = async (familyUuid) => {
        if (!familyUuid) {
            setSubjectParents({ father: null, mother: null });
            return;
        }

        try {
            const res = await axios.get(route('families.parents-lookup'), {
                params: { family_uuid: familyUuid },
            });
            const d = res?.data?.data;
            setSubjectParents({ father: d?.father ?? null, mother: d?.mother ?? null });
        } catch {
            setSubjectParents({ father: null, mother: null });
        }
    };

    const onChangeSubject = (e) => {
        e?.preventDefault();
        changeSubjectForm.post(route('baptisms.change-subject', baptismData.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setChangeSubjectOpen(false);
                router.reload({ only: ['baptism', 'marriageCertificate'] });
            },
        });
    };

    const AttachmentList = ({ type, title, hint, fileField, error }) => {
        const items = attachmentsByType?.[type] ?? [];

        return (
            <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div className="text-sm font-semibold text-slate-900">{title}</div>
                {hint ? <p className="mt-1 text-xs text-slate-500">{hint}</p> : null}

                {items.length === 0 ? (
                    <div className="mt-4 text-sm text-rose-700">Not uploaded.</div>
                ) : (
                    <div className="mt-4 space-y-2">
                        {items.map((a) => (
                            <div key={a.uuid} className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div className="text-sm font-semibold text-slate-900">{a.original_name}</div>
                                    <div className="mt-0.5 text-xs text-slate-500">{a.created_at ?? ''}</div>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <SecondaryButton
                                        type="button"
                                        className="h-10"
                                        onClick={() => openPreview(
                                            route('baptisms.attachments.download', { baptism: baptismData.uuid, attachment: a.uuid, disposition: 'inline' }),
                                            a.original_name
                                        )}
                                    >
                                        Preview
                                    </SecondaryButton>
                                    <SecondaryButton
                                        type="button"
                                        className="h-10"
                                        onClick={() => window.location.assign(route('baptisms.attachments.download', { baptism: baptismData.uuid, attachment: a.uuid }))}
                                    >
                                        Download
                                    </SecondaryButton>
                                    {canEditDraft && (
                                        <SecondaryButton
                                            type="button"
                                            className="h-10 border border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100"
                                            onClick={() => removeAttachment(a.uuid)}
                                        >
                                            Remove
                                        </SecondaryButton>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {canEditDraft && (
                    <form
                        className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const file = submitForm.data?.[fileField];
                            if (!file) return;
                            router.post(
                                route('baptisms.attachments.store', baptismData.uuid),
                                { type, file },
                                {
                                    forceFormData: true,
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        submitForm.setData(fileField, null);
                                        router.reload({ only: ['baptism', 'marriageCertificate'] });
                                    },
                                }
                            );
                        }}
                    >
                        <div className="flex-1">
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor={`${type}_add`}>Add document</label>
                            <input
                                id={`${type}_add`}
                                type="file"
                                accept="application/pdf"
                                disabled={submitForm.processing}
                                onChange={(e) => submitForm.setData(fileField, e.target.files?.[0] ?? null)}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                            />
                            <InputError className="mt-2" message={error} />
                        </div>
                        <div className="sm:pl-2">
                            <PrimaryButton type="submit" className="h-11" disabled={submitForm.processing || !submitForm.data?.[fileField]}>
                                Add
                            </PrimaryButton>
                        </div>
                    </form>
                )}
            </div>
        );
    };

    const allComplete = basicComplete && sponsorComplete && attachmentsComplete;

    const onSubmitRequest = (e) => {
        e?.preventDefault();
        submitForm.post(route('baptisms.submit', baptismData.uuid), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const onSaveDraft = (e) => {
        e?.preventDefault();
        submitForm.post(route('baptisms.draft.save', baptismData.uuid), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const onSaveBaptismDetails = (e) => {
        e?.preventDefault();
        editBaptismForm.post(route('baptisms.draft.save', baptismData.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setEditBaptismOpen(false);
                router.reload({ only: ['baptism'] });
            },
        });
    };

    const removeSponsor = (sponsorUuid) => {
        router.delete(route('baptisms.sponsors.destroy', { baptism: baptismData.uuid, sponsor: sponsorUuid }), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['baptism'] });
            },
        });
    };

    const removeAttachment = (attachmentUuid) => {
        router.delete(route('baptisms.attachments.destroy', { baptism: baptismData.uuid, attachment: attachmentUuid }), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['baptism', 'marriageCertificate'] });
            },
        });
    };

    const canEditDraft = can('baptisms.request.edit')
        && ['draft', 'submitted', 'rejected'].includes(baptismData?.status);
    const canSubmit = can('baptisms.request.submit') && baptismData?.status === 'draft';

    const canApprove = can('baptisms.approve') && baptismData?.status === 'submitted';
    const canReject = can('baptisms.reject') && ['submitted', 'approved'].includes(baptismData?.status);
    const canSchedule = can('baptisms.schedule.manage') && baptismData?.status === 'approved';
    const canComplete = can('baptisms.schedule.manage') && baptismData?.status === 'approved' && !!schedule?.scheduled_for;
    const canIssue = can('baptisms.issue') && baptismData?.status === 'completed';
    const canViewCertificate = can('certificates.view') && baptismData?.status === 'issued';

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

    const approve = () => {
        approveForm.post(route('baptisms.approve', baptismData.uuid), { preserveScroll: true });
    };

    const reject = (e) => {
        e.preventDefault();
        rejectForm.post(route('baptisms.reject', baptismData.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setRejectOpen(false);
                rejectForm.reset('reason');
            },
        });
    };

    const saveSchedule = (e) => {
        e.preventDefault();
        scheduleForm.post(route('baptisms.schedule', baptismData.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                setScheduleOpen(false);
                scheduleForm.reset('reason');
                router.reload({ only: ['schedule', 'scheduleChanges', 'baptism'] });
            },
        });
    };

    const complete = () => {
        completeForm.post(route('baptisms.complete', baptismData.uuid), { preserveScroll: true });
    };

    const issue = () => {
        issueForm.post(route('baptisms.issue', baptismData.uuid), { preserveScroll: true });
    };

    const openPreview = (url, title) => {
        setPreviewUrl(url);
        setPreviewTitle(title || 'Document preview');
        setPreviewOpen(true);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Baptism Request" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Baptism Request</h1>
                        <p className="mt-1 text-sm text-slate-600">Upload required documents, then submit for parish review.</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('baptisms.index'))}>
                            Back
                        </SecondaryButton>
                    </div>
                </div>

                <div className="mb-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${statusMeta(baptismData?.status).cls}`}>{statusMeta(baptismData?.status).label}</span>
                            {baptismData?.certificate_no && (
                                <span className="text-xs font-semibold text-slate-700">Certificate: {baptismData.certificate_no}</span>
                            )}
                        </div>
                        <div className="flex flex-wrap items-center justify-end gap-2">
                            {canApprove && (
                                <PrimaryButton type="button" className="h-11 bg-emerald-700 hover:bg-emerald-800" disabled={approveForm.processing} onClick={approve}>
                                    Approve
                                </PrimaryButton>
                            )}
                            {canReject && (
                                <SecondaryButton type="button" className="h-11 border border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100" onClick={() => setRejectOpen(true)}>
                                    Reject
                                </SecondaryButton>
                            )}
                            {canSchedule && (
                                <SecondaryButton type="button" className="h-11" onClick={() => setScheduleOpen(true)}>
                                    Schedule
                                </SecondaryButton>
                            )}
                            {canComplete && (
                                <PrimaryButton
                                    type="button"
                                    className="h-11 bg-slate-900 hover:bg-slate-950"
                                    disabled={completeForm.processing}
                                    onClick={complete}
                                >
                                    Mark completed
                                </PrimaryButton>
                            )}
                            {canIssue && (
                                <PrimaryButton type="button" className="h-11 bg-indigo-700 hover:bg-indigo-800" disabled={issueForm.processing} onClick={issue}>
                                    Issue certificate
                                </PrimaryButton>
                            )}
                            {canViewCertificate && (
                                <SecondaryButton type="button" className="h-11" onClick={() => router.get(route('baptisms.certificate', baptismData.uuid))}>
                                    View certificate
                                </SecondaryButton>
                            )}
                        </div>
                    </div>

                    {baptismData?.status === 'rejected' && baptismData?.rejection_reason && (
                        <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900">
                            <div className="text-xs font-semibold uppercase tracking-wide">Rejection reason</div>
                            <div className="mt-1 whitespace-pre-wrap">{baptismData.rejection_reason}</div>
                        </div>
                    )}

                    <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Schedule</div>
                        {schedule ? (
                            <div className="mt-2 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                                <div><span className="text-slate-500">Scheduled for:</span> <span className="font-semibold text-slate-900">{schedule.scheduled_for}</span></div>
                                <div><span className="text-slate-500">Location:</span> <span className="text-slate-900">{schedule.location_parish?.name ?? schedule.location_text ?? '—'}</span></div>
                            </div>
                        ) : (
                            <div className="mt-2 text-sm text-slate-600">Not scheduled yet.</div>
                        )}

                        {(scheduleChanges ?? []).length > 0 && (
                            <div className="mt-4">
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Change history</div>
                                <div className="mt-2 space-y-2">
                                    {scheduleChanges.map((c) => (
                                        <div key={c.id} className="rounded-lg border border-slate-200 bg-white p-3 text-sm">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div className="font-semibold text-slate-900">{c.new_scheduled_for ?? '—'}</div>
                                                <div className="text-xs text-slate-500">{c.created_at}{c.changed_by?.name ? ` • ${c.changed_by.name}` : ''}</div>
                                            </div>
                                            {c.reason && <div className="mt-1 text-xs text-slate-600">{c.reason}</div>}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                    <div className={canEditDraft ? 'lg:col-span-2' : 'lg:col-span-5'}>
                        <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="flex items-center justify-between gap-3">
                                <div className="text-sm font-semibold text-slate-900">Request details</div>
                                {canEditDraft && (
                                    <div className="flex items-center gap-2">
                                        <SecondaryButton type="button" className="h-10" onClick={() => {
                                            changeSubjectForm.setData('family_uuid', baptismData?.family?.uuid ?? '');
                                            changeSubjectForm.setData('member_uuid', baptismData?.member?.uuid ?? '');
                                            fetchSubjectParents(baptismData?.family?.uuid ?? '');
                                            setChangeSubjectOpen(true);
                                        }}>
                                            Change child
                                        </SecondaryButton>
                                        <SecondaryButton type="button" className="h-10" onClick={() => {
                                            editBaptismForm.setData('birth_date', baptismData?.birth_date ?? '');
                                            editBaptismForm.setData('birth_town', baptismData?.birth_town ?? '');
                                            editBaptismForm.setData('residence', baptismData?.residence ?? '');
                                            setEditBaptismOpen(true);
                                        }}>
                                            Edit
                                        </SecondaryButton>
                                    </div>
                                )}
                            </div>
                            <dl className="mt-4 space-y-3 text-sm">
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Child</dt>
                                    <dd className="text-right font-semibold text-slate-900">{childName}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Father</dt>
                                    <dd className="text-right text-slate-900">
                                        <div className="font-semibold text-slate-900">{baptismData?.father?.full_name ?? '—'}</div>
                                        <div className="text-xs text-slate-500">
                                            {baptismData?.father?.phone ? baptismData.father.phone : '—'}
                                            {baptismData?.father?.email ? ` • ${baptismData.father.email}` : ''}
                                        </div>
                                    </dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Mother</dt>
                                    <dd className="text-right text-slate-900">
                                        <div className="font-semibold text-slate-900">{baptismData?.mother?.full_name ?? '—'}</div>
                                        <div className="text-xs text-slate-500">
                                            {baptismData?.mother?.phone ? baptismData.mother.phone : '—'}
                                            {baptismData?.mother?.email ? ` • ${baptismData.mother.email}` : ''}
                                        </div>
                                    </dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Parish</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.parish?.name ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Zone</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.zone?.name ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Jumuiya</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.origin_jumuiya?.name ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Family</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.family?.family_name ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Status</dt>
                                    <dd className="text-right font-semibold text-slate-900">{baptismData?.status ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Created</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.created_at ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Birth date</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.birth_date ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Birth town</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.birth_town ?? '—'}</dd>
                                </div>
                                <div className="flex items-start justify-between gap-3">
                                    <dt className="text-slate-500">Residence</dt>
                                    <dd className="text-right text-slate-900">{baptismData?.residence ?? '—'}</dd>
                                </div>
                            </dl>
                        </div>

                        <div className="mt-6">
                            <AttachmentList
                                type="parents_marriage_certificate"
                                title="Parents marriage certificate"
                                hint="Required before submission. This is for the parents (not the sponsor). PDF only, max 3MB."
                                fileField="parents_marriage_certificate"
                                error={submitForm.errors.parents_marriage_certificate}
                            />
                        </div>

                        <div className="mt-6">
                            <AttachmentList
                                type="sponsor_confirmation_certificate"
                                title="Sponsor confirmation certificate"
                                hint="Required before submission. This is for the sponsor (not the parents). PDF only, max 3MB."
                                fileField="sponsor_confirmation_certificate"
                                error={submitForm.errors.sponsor_confirmation_certificate}
                            />
                        </div>

                        <div className="mt-6">
                            <AttachmentList
                                type="birth_certificate"
                                title="Birth certificate"
                                hint="Optional. PDF only, max 3MB."
                                fileField="birth_certificate"
                                error={submitForm.errors.birth_certificate}
                            />
                        </div>

                        <div className="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                            <div className="text-sm font-semibold text-slate-900">Sponsors</div>
                            <p className="mt-1 text-xs text-slate-500">At least one sponsor is required before submission.</p>

                            <div className="mt-4 space-y-2">
                                {(baptismData?.sponsors ?? []).length === 0 ? (
                                    <div className="text-sm text-rose-700">No sponsor added.</div>
                                ) : (
                                    (baptismData?.sponsors ?? []).map((s) => (
                                        <div key={s.id} className="flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                            <div>
                                                <div className="text-sm font-semibold text-slate-900">
                                                    {s?.member
                                                        ? [s.member.first_name, s.member.middle_name, s.member.last_name].filter(Boolean).join(' ')
                                                        : (s.full_name ?? '—')}
                                                </div>
                                                <div className="mt-0.5 text-xs text-slate-500">
                                                    {(s.role ?? '').toString()}
                                                    {s.parish_name ? ` • ${s.parish_name}` : ''}
                                                </div>
                                                <div className="mt-1 text-xs text-slate-600">
                                                    {(s?.member?.phone ?? s?.phone) ? (s?.member?.phone ?? s?.phone) : '—'}
                                                    {(s?.member?.email ?? s?.email) ? ` • ${s?.member?.email ?? s?.email}` : ''}
                                                </div>
                                            </div>
                                            {canEditDraft && (
                                                <button
                                                    type="button"
                                                    onClick={() => removeSponsor(s.uuid)}
                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                                                    title="Remove"
                                                >
                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            )}
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>

                    {canEditDraft && (
                        <div className="lg:col-span-3">
                            <div className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                                <div className="text-sm font-semibold text-slate-900">Edit request</div>
                                <p className="mt-1 text-xs text-slate-500">You can edit while the request is not approved. If it was rejected, fix the issues then submit again.</p>

                                <form onSubmit={onSubmitRequest} className="mt-4 space-y-4">
                                    {submitForm.errors.draft && (
                                        <div className="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800">
                                            {submitForm.errors.draft}
                                        </div>
                                    )}
                                    {submitForm.errors.submit && (
                                        <div className="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-semibold text-rose-800">
                                            {submitForm.errors.submit}
                                        </div>
                                    )}

                                    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-600">Sponsor</div>
                                        <div className="mt-3 grid gap-4 md:grid-cols-2">
                                            <div className="md:col-span-2">
                                                <SearchableMemberSelect
                                                    id="submit_sponsor_member_uuid"
                                                    label="Sponsor member (optional)"
                                                    value={submitForm.data.sponsor_member_uuid}
                                                    onChange={(uuid) => submitForm.setData('sponsor_member_uuid', uuid)}
                                                    disabled={submitForm.processing}
                                                    error={submitForm.errors.sponsor_member_uuid}
                                                />
                                            </div>

                                            <div className="md:col-span-2">
                                                <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="submit_sponsor_full_name">Sponsor full name (if not a member)</label>
                                                <input
                                                    id="submit_sponsor_full_name"
                                                    type="text"
                                                    value={submitForm.data.sponsor_full_name}
                                                    onChange={(e) => submitForm.setData('sponsor_full_name', e.target.value)}
                                                    disabled={submitForm.processing}
                                                    className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                />
                                                <InputError className="mt-2" message={submitForm.errors.sponsor_full_name} />
                                            </div>

                                            <div>
                                                <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="submit_sponsor_role">Role (optional)</label>
                                                <input
                                                    id="submit_sponsor_role"
                                                    type="text"
                                                    value={submitForm.data.sponsor_role}
                                                    onChange={(e) => submitForm.setData('sponsor_role', e.target.value)}
                                                    disabled={submitForm.processing}
                                                    className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                />
                                                <InputError className="mt-2" message={submitForm.errors.sponsor_role} />
                                            </div>

                                            <div>
                                                <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="submit_sponsor_parish">Parish (optional)</label>
                                                <input
                                                    id="submit_sponsor_parish"
                                                    type="text"
                                                    value={submitForm.data.sponsor_parish_name}
                                                    onChange={(e) => submitForm.setData('sponsor_parish_name', e.target.value)}
                                                    disabled={submitForm.processing}
                                                    className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                />
                                                <InputError className="mt-2" message={submitForm.errors.sponsor_parish_name} />
                                            </div>

                                            <div>
                                                <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="submit_sponsor_phone">Phone (optional)</label>
                                                <input
                                                    id="submit_sponsor_phone"
                                                    type="text"
                                                    value={submitForm.data.sponsor_phone}
                                                    onChange={(e) => submitForm.setData('sponsor_phone', e.target.value)}
                                                    disabled={submitForm.processing}
                                                    className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                />
                                                <InputError className="mt-2" message={submitForm.errors.sponsor_phone} />
                                            </div>

                                            <div>
                                                <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="submit_sponsor_email">Email (optional)</label>
                                                <input
                                                    id="submit_sponsor_email"
                                                    type="email"
                                                    value={submitForm.data.sponsor_email}
                                                    onChange={(e) => submitForm.setData('sponsor_email', e.target.value)}
                                                    disabled={submitForm.processing}
                                                    className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                                                />
                                                <InputError className="mt-2" message={submitForm.errors.sponsor_email} />
                                            </div>
                                        </div>

                                        <InputError className="mt-2" message={submitForm.errors.sponsor_member_uuid} />
                                    </div>

                                    {!allComplete && (
                                        <div className="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                                            Provide sponsor details and upload the required documents before submitting.
                                        </div>
                                    )}

                                    <div className="mt-4 flex flex-wrap items-center justify-end gap-2">
                                        <SecondaryButton
                                            type="button"
                                            className="h-11"
                                            disabled={submitForm.processing}
                                            onClick={onSaveDraft}
                                        >
                                            Save draft
                                        </SecondaryButton>
                                        {canSubmit && (
                                            <PrimaryButton
                                                type="submit"
                                                className="h-11 bg-indigo-700 hover:bg-indigo-800"
                                                disabled={submitForm.processing || !allComplete}
                                            >
                                                Submit
                                            </PrimaryButton>
                                        )}
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Modal show={rejectOpen} onClose={() => setRejectOpen(false)} maxWidth="md">
                <form onSubmit={reject} className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Reject request</div>
                    <p className="mt-1 text-sm text-slate-600">Provide a clear reason. This will be visible to the requester.</p>

                    <div className="mt-4">
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

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" className="h-11" onClick={() => setRejectOpen(false)}>Cancel</SecondaryButton>
                        <PrimaryButton type="submit" className="h-11 bg-rose-700 hover:bg-rose-800" disabled={rejectForm.processing}>Reject</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={scheduleOpen} onClose={() => setScheduleOpen(false)} maxWidth="md">
                <form onSubmit={saveSchedule} className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Schedule baptism</div>
                    <p className="mt-1 text-sm text-slate-600">Set the date/time (must be at least tomorrow) and optionally add a reason (for change history).</p>

                    <div className="mt-4 space-y-4">
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
                            <InputError className="mt-2" message={scheduleForm.errors.location_text} />
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
                            <InputError className="mt-2" message={scheduleForm.errors.reason} />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-2">
                        <SecondaryButton type="button" className="h-11" onClick={() => setScheduleOpen(false)}>Cancel</SecondaryButton>
                        <PrimaryButton type="submit" className="h-11 bg-indigo-700 hover:bg-indigo-800" disabled={scheduleForm.processing}>Save schedule</PrimaryButton>
                    </div>
                </form>
            </Modal>

            <Modal show={previewOpen} onClose={() => setPreviewOpen(false)} maxWidth="5xl">
                <div className="p-6">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <div className="text-sm font-semibold text-slate-900">{previewTitle}</div>
                        </div>
                        <SecondaryButton type="button" className="h-10" onClick={() => setPreviewOpen(false)}>
                            Close
                        </SecondaryButton>
                    </div>

                    <div className="mt-4 overflow-hidden rounded-xl border border-slate-200">
                        {previewUrl ? (
                            <iframe title={previewTitle} src={previewUrl} className="h-[75vh] w-full bg-white" />
                        ) : (
                            <div className="p-6 text-sm text-slate-600">No document selected.</div>
                        )}
                    </div>
                </div>
            </Modal>

            <Modal show={editBaptismOpen} onClose={() => setEditBaptismOpen(false)}>
                <div className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Edit baptism request</div>
                    <p className="mt-1 text-sm text-slate-600">Update the baptism request details. Allowed only before approval.</p>

                    <form onSubmit={onSaveBaptismDetails} className="mt-4 space-y-4">
                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="edit_birth_date">Birth date</label>
                            <input
                                id="edit_birth_date"
                                type="date"
                                value={editBaptismForm.data.birth_date}
                                onChange={(e) => editBaptismForm.setData('birth_date', e.target.value)}
                                disabled={editBaptismForm.processing}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                            />
                            <InputError className="mt-2" message={editBaptismForm.errors.birth_date} />
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="edit_birth_town">Birth town</label>
                            <input
                                id="edit_birth_town"
                                type="text"
                                value={editBaptismForm.data.birth_town}
                                onChange={(e) => editBaptismForm.setData('birth_town', e.target.value)}
                                disabled={editBaptismForm.processing}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                            />
                            <InputError className="mt-2" message={editBaptismForm.errors.birth_town} />
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700" htmlFor="edit_residence">Residence</label>
                            <input
                                id="edit_residence"
                                type="text"
                                value={editBaptismForm.data.residence}
                                onChange={(e) => editBaptismForm.setData('residence', e.target.value)}
                                disabled={editBaptismForm.processing}
                                className="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm"
                            />
                            <InputError className="mt-2" message={editBaptismForm.errors.residence} />
                        </div>

                        <div className="flex items-center justify-end gap-2 pt-2">
                            <SecondaryButton type="button" className="h-11" onClick={() => setEditBaptismOpen(false)}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" className="h-11" disabled={editBaptismForm.processing}>
                                Save
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={changeSubjectOpen} onClose={() => setChangeSubjectOpen(false)}>
                <div className="p-6">
                    <div className="text-lg font-semibold text-slate-900">Change family & child</div>
                    <p className="mt-1 text-sm text-slate-600">This will clear sponsors and uploaded documents so you can start again with the correct child.</p>

                    <form onSubmit={onChangeSubject} className="mt-4 space-y-4">
                        <div>
                            <SearchableFamilySelect
                                id="change_family_uuid"
                                label="Family"
                                value={changeSubjectForm.data.family_uuid}
                                onChange={(uuid) => {
                                    changeSubjectForm.setData('family_uuid', uuid);
                                    changeSubjectForm.setData('member_uuid', '');
                                    fetchSubjectParents(uuid);
                                }}
                                disabled={changeSubjectForm.processing}
                                error={changeSubjectForm.errors.family_uuid}
                            />
                        </div>

                        <div>
                            <SearchableMemberSelect
                                id="change_member_uuid"
                                label="Child"
                                value={changeSubjectForm.data.member_uuid}
                                onChange={(uuid) => changeSubjectForm.setData('member_uuid', uuid)}
                                familyUuid={changeSubjectForm.data.family_uuid}
                                excludeUuids={[subjectParents?.father?.uuid, subjectParents?.mother?.uuid].filter(Boolean)}
                                disabled={changeSubjectForm.processing || !changeSubjectForm.data.family_uuid}
                                error={changeSubjectForm.errors.member_uuid}
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Father</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{subjectParents?.father?.name ?? '—'}</div>
                                <div className="mt-1 text-xs text-slate-500">{subjectParents?.father?.marital_status ?? ''}</div>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Mother</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{subjectParents?.mother?.name ?? '—'}</div>
                                <div className="mt-1 text-xs text-slate-500">{subjectParents?.mother?.marital_status ?? ''}</div>
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-2 pt-2">
                            <SecondaryButton type="button" className="h-11" onClick={() => setChangeSubjectOpen(false)}>
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton type="submit" className="h-11" disabled={changeSubjectForm.processing}>
                                Save
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
