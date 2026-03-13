import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import Spinner from '@/Components/Spinner';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function LeadershipAssignmentsIndex({ leaderships, filters, jumuiyas, roles }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canView = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leaderships.view'), [permissions]);
    const canCreate = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leaderships.create'), [permissions]);
    const canUpdate = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leaderships.update'), [permissions]);
    const canDelete = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leaderships.delete'), [permissions]);

    const list = leaderships?.data ?? leaderships ?? [];

    const [activeOnly, setActiveOnly] = useState(!!filters?.active_only);
    const [jumuiyaUuid, setJumuiyaUuid] = useState(filters?.jumuiya_uuid ?? '');

    const applyFilters = () => {
        router.get(
            route('jumuiya-leaderships.index'),
            {
                jumuiya_uuid: jumuiyaUuid || undefined,
                active_only: activeOnly || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    useEffect(() => {
        applyFilters();
    }, [activeOnly, jumuiyaUuid]);

    const [assignOpen, setAssignOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const closeAssign = () => {
        setAssignOpen(false);
        clearAssignErrors();
    };

    const closeEdit = () => {
        setEditOpen(false);
        setEditing(null);
        clearEditErrors();
    };

    const {
        data: assignData,
        setData: setAssignData,
        post,
        processing: assignProcessing,
        errors: assignErrors,
        clearErrors: clearAssignErrors,
        reset: resetAssign,
    } = useForm({
        zone_uuid: '',
        jumuiya_uuid: jumuiyaUuid ?? '',
        member_uuid: '',
        role_uuid: '',
        start_date: '',
        end_date: '',
        is_active: true,
        create_login: false,
    });

    const {
        data: editData,
        setData: setEditData,
        patch,
        processing: editProcessing,
        errors: editErrors,
        clearErrors: clearEditErrors,
        reset: resetEdit,
    } = useForm({
        end_date: '',
        is_active: true,
    });

    useEffect(() => {
        if (!assignOpen) return;
        clearAssignErrors();
        resetAssign();
        setAssignData({
            zone_uuid: '',
            jumuiya_uuid: jumuiyaUuid ?? '',
            member_uuid: '',
            role_uuid: '',
            start_date: '',
            end_date: '',
            is_active: true,
            create_login: false,
        });
    }, [assignOpen]);

    useEffect(() => {
        if (!editOpen || !editing) return;
        clearEditErrors();
        resetEdit();
        setEditData({
            end_date: editing?.end_date ?? '',
            is_active: editing?.is_active ?? true,
        });
    }, [editOpen, editing?.uuid]);

    const openAssign = () => {
        setAssignOpen(true);
    };

    const openEdit = (row) => {
        setEditing(row);
        setEditOpen(true);
    };

    const submitAssign = (e) => {
        e.preventDefault();
        post(route('jumuiya-leaderships.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeAssign();
            },
        });
    };

    const submitEdit = (e) => {
        e.preventDefault();
        if (!editing) return;
        patch(route('jumuiya-leaderships.update', editing.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                closeEdit();
            },
        });
    };

    return (
        <AuthenticatedLayout header="Leadership Assignments">
            <Head title="Leadership Assignments" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Leadership Assignments</h1>
                        <p className="mt-1 text-sm text-slate-500">Assign leadership roles to members per Christian Community.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={openAssign}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                Assign leader
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    {!canView ? (
                        <div className="text-sm font-semibold text-rose-600">You do not have permission to view leadership assignments.</div>
                    ) : (
                        <>
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                <div className="grid w-full gap-3 sm:grid-cols-2 lg:max-w-3xl">
                                    <FloatingSelect
                                        id="leadership_jumuiya_filter"
                                        label="Christian Community"
                                        value={jumuiyaUuid}
                                        onChange={(e) => setJumuiyaUuid(e.target.value)}
                                    >
                                        <option value="">All Christian Communities</option>
                                        {(jumuiyas ?? []).map((j) => (
                                            <option key={j.uuid} value={j.uuid}>{j.name}</option>
                                        ))}
                                    </FloatingSelect>

                                    <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input
                                            type="checkbox"
                                            checked={activeOnly}
                                            onChange={(e) => setActiveOnly(e.target.checked)}
                                            className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        Active only
                                    </label>
                                </div>

                                <div className="flex items-center gap-2">
                                    <SecondaryButton
                                        type="button"
                                        onClick={() => {
                                            setJumuiyaUuid('');
                                            setActiveOnly(false);
                                        }}
                                        className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                                    >
                                        Reset
                                    </SecondaryButton>
                                </div>
                            </div>

                            <div className="mt-6 overflow-x-auto">
                                <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                                    <table className="min-w-full divide-y divide-slate-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">#</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Christian Community</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Member</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Role</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Start</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">End</th>
                                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                                                {(canUpdate || canDelete) && (
                                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                                                )}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100 bg-white">
                                            {(list ?? []).map((row, idx) => (
                                                <LeadershipRow
                                                    key={row.uuid}
                                                    row={row}
                                                    striped={idx % 2 === 1}
                                                    index={idx + 1}
                                                    canUpdate={canUpdate}
                                                    canDelete={canDelete}
                                                    onEdit={() => openEdit(row)}
                                                />
                                            ))}

                                            {(list ?? []).length === 0 && (
                                                <tr>
                                                    <td
                                                        colSpan={(canUpdate || canDelete) ? 8 : 7}
                                                        className="px-4 py-10 text-center text-sm text-slate-500"
                                                    >
                                                        No leadership assignments found.
                                                    </td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </>
                    )}
                </section>
            </div>

            <Modal show={assignOpen} onClose={closeAssign} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title="Assign leader"
                        subtitle="Assign a role to a member in a Christian Community."
                        onClose={closeAssign}
                        showRequiredNote
                    />

                    <form onSubmit={submitAssign} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <SearchableZoneSelect
                                id="assign_zone_uuid"
                                label="Zone"
                                value={assignData.zone_uuid}
                                onChange={(uuid) => {
                                    setAssignData('zone_uuid', uuid);
                                    setAssignData('jumuiya_uuid', '');
                                    setAssignData('member_uuid', '');
                                }}
                                error={assignErrors.zone_uuid}
                                className="md:col-span-2"
                            />

                            <SearchableJumuiyaSelect
                                id="assign_jumuiya_uuid"
                                label="Christian Community"
                                value={assignData.jumuiya_uuid}
                                onChange={(uuid) => {
                                    setAssignData('jumuiya_uuid', uuid);
                                    setAssignData('member_uuid', '');
                                }}
                                zoneUuid={assignData.zone_uuid}
                                disabled={!assignData.zone_uuid}
                                error={assignErrors.jumuiya_uuid}
                                className="md:col-span-2"
                            />

                            <SearchableMemberSelect
                                id="assign_member_uuid"
                                label="Member"
                                value={assignData.member_uuid}
                                onChange={(uuid) => setAssignData('member_uuid', uuid)}
                                jumuiyaUuid={assignData.jumuiya_uuid}
                                disabled={!assignData.jumuiya_uuid}
                                error={assignErrors.member_uuid}
                            />

                            <FloatingSelect
                                id="assign_role_uuid"
                                label="Role"
                                required
                                value={assignData.role_uuid}
                                onChange={(e) => setAssignData('role_uuid', e.target.value)}
                                error={assignErrors.role_uuid}
                            >
                                <option value="">Select role</option>
                                {(roles ?? []).map((r) => (
                                    <option key={r.uuid} value={r.uuid}>{r.name}</option>
                                ))}
                            </FloatingSelect>

                            <FloatingInput
                                id="assign_start_date"
                                label="Start date"
                                type="date"
                                required
                                value={assignData.start_date}
                                onChange={(e) => setAssignData('start_date', e.target.value)}
                                error={assignErrors.start_date}
                            />

                            <FloatingInput
                                id="assign_end_date"
                                label="End date"
                                type="date"
                                value={assignData.end_date}
                                onChange={(e) => setAssignData('end_date', e.target.value)}
                                error={assignErrors.end_date}
                            />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="flex flex-col gap-2">
                                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={!!assignData.is_active}
                                        onChange={(e) => setAssignData('is_active', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    Active
                                </label>
                                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={!!assignData.create_login}
                                        onChange={(e) => setAssignData('create_login', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    Create login account (if missing)
                                </label>
                            </div>

                            <div className="flex gap-2">
                                <SecondaryButton
                                    type="button"
                                    onClick={closeAssign}
                                    disabled={assignProcessing}
                                    className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton
                                    disabled={assignProcessing}
                                    className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                                >
                                    {assignProcessing && <Spinner size="sm" className="text-white" />}
                                    <span>Assign</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={editOpen} onClose={closeEdit} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title="Update assignment"
                        subtitle="End or deactivate a leadership assignment."
                        onClose={closeEdit}
                        showRequiredNote={false}
                    />

                    {editing && (
                        <div className="-mt-2 mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            <div className="font-semibold">{editing.member_name}</div>
                            <div className="text-xs text-slate-500">{editing.jumuiya_name} • {editing.role_name}</div>
                        </div>
                    )}

                    <form onSubmit={submitEdit} className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="edit_end_date"
                                label="End date"
                                type="date"
                                value={editData.end_date}
                                onChange={(e) => setEditData('end_date', e.target.value)}
                                error={editErrors.end_date}
                            />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={!!editData.is_active}
                                    onChange={(e) => setEditData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Active
                            </label>

                            <div className="flex gap-2">
                                <SecondaryButton
                                    type="button"
                                    onClick={closeEdit}
                                    disabled={editProcessing}
                                    className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton
                                    disabled={editProcessing}
                                    className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                                >
                                    {editProcessing && <Spinner size="sm" className="text-white" />}
                                    <span>Update</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

function LeadershipRow({ row, striped = false, index, canUpdate, canDelete, onEdit }) {
    const [confirmOpen, setConfirmOpen] = useState(false);

    const destroy = () => {
        router.delete(route('jumuiya-leaderships.destroy', row.uuid), { preserveScroll: true });
    };

    return (
        <>
            <tr className={`${striped ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                <td className="px-4 py-3 text-sm font-semibold text-slate-700">{index}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{row.jumuiya_name ?? '-'}</td>
                <td className="px-4 py-3">
                    <div className="text-sm font-semibold text-slate-900">{row.member_name ?? '-'}</div>
                    <div className="text-xs text-slate-500">{row.member_email ?? ''}</div>
                </td>
                <td className="px-4 py-3 text-sm text-slate-700">{row.role_name ?? '-'}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{row.start_date ?? '-'}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{row.end_date ?? '-'}</td>
                <td className="px-4 py-3 text-sm">
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${row.is_active
                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                            }`}
                    >
                        {row.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                {(canUpdate || canDelete) && (
                    <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-2">
                            {canUpdate && (
                                <button
                                    type="button"
                                    onClick={onEdit}
                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                    title="Update"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3 19.5l1.037-5.25L16.862 3.487z" />
                                    </svg>
                                </button>
                            )}
                            {canDelete && (
                                <button
                                    type="button"
                                    onClick={() => setConfirmOpen(true)}
                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                                    title="Delete"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 14h8l1-14" />
                                    </svg>
                                </button>
                            )}
                        </div>
                    </td>
                )}
            </tr>

            <ConfirmDialog
                open={confirmOpen}
                onCancel={() => setConfirmOpen(false)}
                title="Delete leadership"
                message="Are you sure you want to delete this leadership record? Only ended/inactive leaderships can be deleted."
                confirmText="Delete"
                onConfirm={destroy}
            />
        </>
    );
}
