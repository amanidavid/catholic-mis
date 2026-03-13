import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SearchableInstitutionSelect from '@/Components/SearchableInstitutionSelect';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableFamilySelect from '@/Components/SearchableFamilySelect';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import SecondaryButton from '@/Components/SecondaryButton';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function ParishStaffIndex({ staff, filters, assignmentRoles }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canCreate = useMemo(() => permissions.includes('parish-staff.create'), [permissions]);
    const canUpdate = useMemo(() => permissions.includes('parish-staff.update'), [permissions]);
    const canDelete = useMemo(() => permissions.includes('parish-staff.delete'), [permissions]);
    const canAssignments = useMemo(() => permissions.includes('parish-staff.assignments.manage'), [permissions]);
    const canLogin = useMemo(() => permissions.includes('parish-staff.login.manage'), [permissions]);

    const [q, setQ] = useState(filters?.q ?? '');
    const [searchBy, setSearchBy] = useState(filters?.search_by ?? 'name');
    const [status, setStatus] = useState(filters?.is_active ?? 'all');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(
            route('parish-staff.index'),
            {
                q: q || undefined,
                search_by: searchBy || undefined,
                is_active: status || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clearSearch = () => {
        setQ('');
        setSearchBy('name');
        setStatus('all');
        router.get(route('parish-staff.index'), {}, { preserveState: true, replace: true });
    };

    const [addOpen, setAddOpen] = useState(false);
    const [editing, setEditing] = useState(null);
    const [assigning, setAssigning] = useState(null);
    const [viewing, setViewing] = useState(null);
    const [registering, setRegistering] = useState(null);
    const [transferring, setTransferring] = useState(null);

    useEffect(() => {
        const rows = Array.isArray(staff?.data) ? staff.data : [];

        if (editing?.uuid) {
            const fresh = rows.find((r) => r.uuid === editing.uuid);
            if (fresh) setEditing(fresh);
        }

        if (assigning?.uuid) {
            const fresh = rows.find((r) => r.uuid === assigning.uuid);
            if (fresh) setAssigning(fresh);
        }

        if (viewing?.uuid) {
            const fresh = rows.find((r) => r.uuid === viewing.uuid);
            if (fresh) setViewing(fresh);
        }

        if (registering?.uuid) {
            const fresh = rows.find((r) => r.uuid === registering.uuid);
            if (fresh) setRegistering(fresh);
        }

        if (transferring?.uuid) {
            const fresh = rows.find((r) => r.uuid === transferring.uuid);
            if (fresh) setTransferring(fresh);
        }
    }, [staff?.data]);

    const searchLabel = useMemo(() => {
        if (searchBy === 'phone') return 'Search phone';
        if (searchBy === 'email') return 'Search email';
        if (searchBy === 'national_id') return 'Search national ID';
        if (searchBy === 'assignment_type') return 'Search assignment type';
        return 'Search name';
    }, [searchBy]);

    return (
        <AuthenticatedLayout>
            <Head title="Parish Staff" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Parish staff</h1>
                        <p className="mt-1 text-sm text-slate-500">Manage staff and keep assignment history.</p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={() => setAddOpen(true)}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Staff</span>
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid w-full gap-3 lg:grid-cols-12">
                            <FloatingInput
                                id="staff_q"
                                label={searchLabel}
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                hint="Tip: use Search by for best performance"
                                className="lg:col-span-5"
                            />

                            <FloatingSelect
                                id="staff_search_by"
                                label="Search by"
                                value={searchBy}
                                onChange={(e) => setSearchBy(e.target.value)}
                                className="lg:col-span-3"
                            >
                                <option value="name">Name</option>
                                <option value="phone">Phone</option>
                                <option value="email">Email</option>
                                <option value="national_id">National ID</option>
                                <option value="assignment_type">Assignment type</option>
                            </FloatingSelect>

                            <FloatingSelect
                                id="staff_status"
                                label="Status"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="lg:col-span-4"
                            >
                                <option value="all">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </FloatingSelect>
                        </div>

                        <div className="flex items-center gap-2">
                            <PrimaryButton
                                type="submit"
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                Search
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={clearSearch}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                            >
                                Clear
                            </SecondaryButton>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Name</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Current assignment</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {(staff?.data ?? []).map((s) => (
                                        <StaffRow
                                            key={s.uuid}
                                            staff={s}
                                            canUpdate={canUpdate}
                                            canDelete={canDelete}
                                            canAssignments={canAssignments}
                                            canLogin={canLogin}
                                            onEdit={() => setEditing(s)}
                                            onAssignments={() => setAssigning(s)}
                                            onView={() => setViewing(s)}
                                            onRegisterAsMember={() => setRegistering(s)}
                                            onTransferMember={() => setTransferring(s)}
                                        />
                                    ))}
                                    {(staff?.data ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-10 text-center text-sm text-slate-500">No staff found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={staff?.meta} />
                        <Pagination links={staff?.meta?.links ?? []} />
                    </div>
                </section>
            </div>

            <StaffModal
                title="Add staff"
                open={addOpen}
                onClose={() => setAddOpen(false)}
                submitRoute={route('parish-staff.store')}
                assignmentRoles={assignmentRoles}
            />

            <StaffModal
                title="Edit staff"
                open={!!editing}
                onClose={() => setEditing(null)}
                submitRoute={editing ? route('parish-staff.update', editing.uuid) : null}
                method="patch"
                initial={editing}
                assignmentRoles={assignmentRoles}
            />

            <AssignmentsModal
                open={!!assigning}
                onClose={() => setAssigning(null)}
                staff={assigning}
                canManage={canAssignments}
                assignmentRoles={assignmentRoles}
            />

            <StaffViewModal
                open={!!viewing}
                onClose={() => setViewing(null)}
                staff={viewing}
            />

            <RegisterAsMemberModal
                open={!!registering}
                onClose={() => setRegistering(null)}
                staff={registering}
            />

            <TransferMemberModal
                open={!!transferring}
                onClose={() => setTransferring(null)}
                staff={transferring}
            />
        </AuthenticatedLayout >
    );
}

function StaffRow({ staff, canUpdate, canDelete, canAssignments, canLogin, onEdit, onAssignments, onView, onRegisterAsMember, onTransferMember }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);
    const canManageInstitutions = useMemo(
        () => Array.isArray(permissions) && permissions.includes('institutions.view'),
        [permissions]
    );

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [loginConfirmOpen, setLoginConfirmOpen] = useState(false);

    const canRegisterAsMember = useMemo(() => {
        return staff?.source === 'External' && !staff?.member_uuid;
    }, [staff?.member_uuid, staff?.source]);

    const canTransferMember = useMemo(() => {
        return staff?.source === 'Member' && !!staff?.member_uuid;
    }, [staff?.member_uuid, staff?.source]);

    const destroy = () => {
        router.delete(route('parish-staff.destroy', staff.uuid), { preserveScroll: true });
    };

    const enableLogin = () => {
        router.post(route('parish-staff.login.create', staff.uuid), {}, { preserveScroll: true });
    };

    const disableLogin = () => {
        router.delete(route('parish-staff.login.disable', staff.uuid), { preserveScroll: true });
    };

    return (
        <>
            <tr className="hover:bg-indigo-50/40 transition">
                <td className="px-4 py-3 text-sm font-semibold text-slate-900">{toTitleCase(staff.display_name)}</td>
                <td className="px-4 py-3 text-sm text-slate-700">
                    {staff.current_assignment
                        ? toTitleCase(staff.current_assignment.title || staff.current_assignment.assignment_type)
                        : '-'}
                </td>
                <td className="px-4 py-3 text-sm">
                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${staff.is_active
                        ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                        : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                        }`}
                    >
                        {staff.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            onClick={onView}
                            title="View"
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7z" />
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                            </svg>
                        </button>
                        {canLogin && (
                            staff.has_login ? (
                                <button
                                    type="button"
                                    onClick={() => setLoginConfirmOpen(true)}
                                    title="Disable login"
                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M18 6L6 18M6 6l12 12" />
                                    </svg>
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={enableLogin}
                                    title="Create login"
                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4 9 5.567 9 7.5 10.343 11 12 11z" />
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 20a7 7 0 0114 0" />
                                    </svg>
                                </button>
                            )
                        )}
                        {canAssignments && (
                            <button
                                type="button"
                                onClick={onAssignments}
                                title="Assignments"
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6M9 16h6M9 8h6" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M7 4h10a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z" />
                                </svg>
                            </button>
                        )}
                        {canUpdate && canTransferMember && (
                            <button
                                type="button"
                                onClick={onTransferMember}
                                title="Transfer member"
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-cyan-200 bg-cyan-50 text-cyan-800 hover:bg-cyan-100"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 16l-6-6m0 0l6-6m-6 6h14a4 4 0 014 4v1" />
                                </svg>
                            </button>
                        )}
                        {canUpdate && canRegisterAsMember && (
                            <button
                                type="button"
                                onClick={onRegisterAsMember}
                                title="Create member profile"
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 11a4 4 0 100-8 4 4 0 000 8z" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 8v6" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M22 11h-6" />
                                </svg>
                            </button>
                        )}
                        {canUpdate && (
                            <button
                                type="button"
                                onClick={onEdit}
                                title="Edit"
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M11 4h-5a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2v-5" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                            </button>
                        )}
                        {canDelete && (
                            <button
                                type="button"
                                onClick={() => setConfirmOpen(true)}
                                title="Delete"
                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 6h18" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 6V4h8v2" />
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 6l-1 14H6L5 6" />
                                </svg>
                            </button>
                        )}
                    </div>
                </td>
            </tr>

            <ConfirmDialog
                open={confirmOpen}
                onClose={() => setConfirmOpen(false)}
                title="Delete staff"
                message={`Are you sure you want to delete "${staff.display_name}"?`}
                confirmText="Delete"
                onConfirm={destroy}
            />

            <ConfirmDialog
                open={loginConfirmOpen}
                onClose={() => setLoginConfirmOpen(false)}
                title="Disable login"
                message={`Disable login for "${staff.display_name}"? They will no longer be able to sign in.`}
                confirmText="Disable"
                onConfirm={disableLogin}
            />
        </>
    );
}

function RegisterAsMemberModal({ open, onClose, staff }) {
    const { data, setData, processing, errors, reset, post } = useForm({
        zone_uuid: '',
        jumuiya_uuid: staff?.jumuiya_uuid ?? '',
    });

    const needsJumuiya = useMemo(() => {
        return !staff?.jumuiya_uuid;
    }, [staff?.jumuiya_uuid]);

    useEffect(() => {
        if (!open) return;
        setData({
            zone_uuid: '',
            jumuiya_uuid: staff?.jumuiya_uuid ?? '',
        });
    }, [open, staff?.uuid]);

    const close = () => {
        onClose();
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        if (!staff?.uuid) return;

        post(route('parish-staff.register-as-member', staff.uuid), {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    };

    return (
        <Modal show={open} onClose={close} maxWidth="lg">
            <div className="bg-white">
                <ModalHeader title="Create member profile" onClose={close} />

                <form onSubmit={submit} className="space-y-5 p-6">
                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        This will create a Member record and a placeholder Family, then link this staff to that Member so the person appears under Zone - Christian Community - Family.
                    </div>

                    {needsJumuiya ? (
                        <div>
                            <SearchableZoneSelect
                                id="register_zone_uuid"
                                label="Zone (optional)"
                                value={data.zone_uuid}
                                onChange={(uuid) => {
                                    setData('zone_uuid', uuid);
                                    if (data.jumuiya_uuid) {
                                        setData('jumuiya_uuid', '');
                                    }
                                }}
                                error={errors.zone_uuid}
                                className="mb-4"
                            />
                            <SearchableJumuiyaSelect
                                id="register_jumuiya_uuid"
                                label="Christian Community (Jumuiya)"
                                required
                                value={data.jumuiya_uuid}
                                onChange={(v) => setData('jumuiya_uuid', v)}
                                zoneUuid={data.zone_uuid}
                                error={errors.jumuiya_uuid}
                            />
                        </div>
                    ) : (
                        <FloatingInput
                            id="register_jumuiya_name"
                            label="Christian Community (Jumuiya)"
                            value={staff?.jumuiya_name ?? '-'}
                            disabled
                        />
                    )}

                    <div className="flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            disabled={processing}
                            className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-emerald-600 text-white hover:bg-emerald-700 focus:bg-emerald-700 active:bg-emerald-800"
                        >
                            Register
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function TransferMemberModal({ open, onClose, staff }) {
    const { data, setData, processing, errors, reset, post } = useForm({
        zone_uuid: '',
        jumuiya_uuid: '',
        family_uuid: '',
    });

    useEffect(() => {
        if (!open) return;
        setData({
            zone_uuid: '',
            jumuiya_uuid: '',
            family_uuid: '',
        });
    }, [open, staff?.uuid]);

    const close = () => {
        onClose();
        reset();
    };

    const submit = (e) => {
        e.preventDefault();
        if (!staff?.uuid) return;

        post(route('parish-staff.transfer-member', staff.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                close();
                router.reload({ only: ['staff'], preserveScroll: true });
            },
        });
    };

    return (
        <Modal show={open} onClose={close} maxWidth="lg">
            <div className="bg-white">
                <ModalHeader title="Transfer member" onClose={close} />

                <form onSubmit={submit} className="space-y-5 p-6">
                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                        Select Zone, Christian Community and Family. This will move the member under the selected Family.
                    </div>

                    <SearchableZoneSelect
                        id="transfer_zone_uuid"
                        label="Zone"
                        value={data.zone_uuid}
                        onChange={(uuid) => {
                            setData('zone_uuid', uuid);
                            if (data.jumuiya_uuid) setData('jumuiya_uuid', '');
                            if (data.family_uuid) setData('family_uuid', '');
                        }}
                        error={errors.zone_uuid}
                    />

                    <SearchableJumuiyaSelect
                        id="transfer_jumuiya_uuid"
                        label="Christian Community (Jumuiya)"
                        value={data.jumuiya_uuid}
                        onChange={(uuid) => {
                            setData('jumuiya_uuid', uuid);
                            if (data.family_uuid) setData('family_uuid', '');
                        }}
                        zoneUuid={data.zone_uuid}
                        disabled={!data.zone_uuid || processing}
                        error={errors.jumuiya_uuid}
                    />

                    <SearchableFamilySelect
                        id="transfer_family_uuid"
                        label="Family"
                        value={data.family_uuid}
                        onChange={(uuid) => setData('family_uuid', uuid)}
                        jumuiyaUuid={data.jumuiya_uuid}
                        disabled={!data.jumuiya_uuid || processing}
                        error={errors.family_uuid}
                    />

                    <div className="flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            disabled={processing || !data.jumuiya_uuid || !data.family_uuid}
                            className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-cyan-600 text-white hover:bg-cyan-700 focus:bg-cyan-700 active:bg-cyan-800"
                        >
                            Transfer
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function StaffModal({ title, open, onClose, submitRoute, method = 'post', initial }) {
    const { data, setData, processing, errors, reset, submit } = useForm({
        member_uuid: initial?.member_uuid ?? '',
        jumuiya_uuid: initial?.jumuiya_uuid ?? '',
        gender: initial?.gender ?? '',
        first_name: initial?.first_name ?? '',
        middle_name: initial?.middle_name ?? '',
        last_name: initial?.last_name ?? '',
        phone: initial?.phone ?? '',
        email: initial?.email ?? '',
        national_id: initial?.national_id ?? '',
        notes: initial?.notes ?? '',
        is_active: initial?.is_active ?? true,
    });

    const hasMember = useMemo(() => typeof data.member_uuid === 'string' && data.member_uuid.trim() !== '', [data.member_uuid]);

    const [selectedMember, setSelectedMember] = useState(null);

    const derivedJumuiyaName = useMemo(() => {
        if (!hasMember) return '';
        const fromSelect = selectedMember?.jumuiya_name;
        if (typeof fromSelect === 'string' && fromSelect.trim() !== '') {
            return fromSelect;
        }

        const v = initial?.derived_jumuiya_name;
        return typeof v === 'string' ? v : '';
    }, [hasMember, initial?.derived_jumuiya_name, selectedMember?.jumuiya_name]);

    useEffect(() => {
        if (!open) return;

        setData({
            member_uuid: initial?.member_uuid ?? '',
            jumuiya_uuid: initial?.jumuiya_uuid ?? '',
            gender: initial?.gender ?? '',
            first_name: initial?.first_name ?? '',
            middle_name: initial?.middle_name ?? '',
            last_name: initial?.last_name ?? '',
            phone: initial?.phone ?? '',
            email: initial?.email ?? '',
            national_id: initial?.national_id ?? '',
            notes: initial?.notes ?? '',
            is_active: initial?.is_active ?? true,
        });
    }, [open, initial?.uuid]);

    const [memberTouched, setMemberTouched] = useState(false);

    useEffect(() => {
        if (!open) {
            setMemberTouched(false);
            setSelectedMember(null);
        }
    }, [open]);

    useEffect(() => {
        if (!open) return;
        if (!memberTouched) return;
        if (!hasMember) return;

        if (data.jumuiya_uuid) {
            setData('jumuiya_uuid', '');
        }

        if (data.first_name || data.middle_name || data.last_name) {
            setData((prev) => ({
                ...prev,
                gender: '',
                first_name: '',
                middle_name: '',
                last_name: '',
            }));
        }
    }, [open, memberTouched, hasMember]);

    const close = () => {
        onClose();
        reset();
    };

    const save = (e) => {
        e.preventDefault();
        if (!submitRoute) return;

        submit(method, submitRoute, {
            preserveScroll: true,
            onSuccess: () => {
                close();
                if (typeof onSaved === 'function') {
                    onSaved();
                }
            },
        });
    };

    return (
        <Modal show={open} onClose={close} maxWidth="2xl">
            <div className="p-6">
                <ModalHeader
                    title={title}
                    subtitle="Link to an existing member, or enter external staff details."
                    onClose={close}
                    showRequiredNote
                />

                <form onSubmit={save} className="mt-4 space-y-4">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="md:col-span-3">
                            <SearchableMemberSelect
                                id="staff_member_uuid"
                                label="Member (optional)"
                                value={data.member_uuid}
                                onChange={(uuid) => {
                                    setMemberTouched(true);
                                    setData('member_uuid', uuid);
                                }}
                                onSelect={(opt) => {
                                    setSelectedMember(opt);
                                }}
                                disabled={processing}
                                error={errors.member_uuid}
                            />
                            <div className="mt-1 text-xs text-slate-500">
                                If selected, name fields will be locked to avoid duplicates.
                            </div>
                        </div>

                        {hasMember ? (
                            <div className="md:col-span-3">
                                <FloatingInput
                                    id="derived_jumuiya"
                                    label="Christian Community (from Member)"
                                    value={derivedJumuiyaName || '-'}
                                    disabled
                                />
                                <div className="mt-1 text-xs text-slate-500">
                                    Change Christian Community from the Member/Family records.
                                </div>
                            </div>
                        ) : (
                            <SearchableJumuiyaSelect
                                id="staff_jumuiya_uuid"
                                label="Christian Community (optional)"
                                value={data.jumuiya_uuid}
                                onChange={(uuid) => setData('jumuiya_uuid', uuid)}
                                disabled={processing}
                                error={errors.jumuiya_uuid}
                                className="md:col-span-3"
                            />
                        )}

                        {hasMember ? (
                            <FloatingInput
                                id="derived_gender"
                                label="Gender (from Member)"
                                value={(selectedMember?.gender ?? initial?.gender ?? '').toString() || '-'}
                                disabled
                                className="md:col-span-1"
                            />
                        ) : (
                            <FloatingSelect
                                id="gender"
                                label="Gender"
                                required
                                value={data.gender}
                                onChange={(e) => setData('gender', e.target.value)}
                                error={errors.gender}
                                className="md:col-span-1"
                            >
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </FloatingSelect>
                        )}

                        <FloatingInput id="first_name" label="First name" value={data.first_name} disabled={hasMember} onChange={(e) => setData('first_name', e.target.value)} error={errors.first_name} className="md:col-span-1" />
                        <FloatingInput id="middle_name" label="Middle name" value={data.middle_name} disabled={hasMember} onChange={(e) => setData('middle_name', e.target.value)} error={errors.middle_name} className="md:col-span-1" />
                        <FloatingInput id="last_name" label="Last name" value={data.last_name} disabled={hasMember} onChange={(e) => setData('last_name', e.target.value)} error={errors.last_name} className="md:col-span-1" />

                        <FloatingInput id="phone" label="Phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} error={errors.phone} className="md:col-span-1" />
                        <FloatingInput id="email" label="Email" value={data.email} onChange={(e) => setData('email', e.target.value)} error={errors.email} className="md:col-span-1" />
                        <FloatingInput id="national_id" label="National ID" value={data.national_id} onChange={(e) => setData('national_id', e.target.value)} error={errors.national_id} className="md:col-span-1" />

                        {initial?.uuid && (
                            <FloatingSelect id="is_active" label="Status" value={data.is_active ? '1' : '0'} onChange={(e) => setData('is_active', e.target.value === '1')} error={errors.is_active} className="md:col-span-1">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </FloatingSelect>
                        )}
                        <FloatingInput id="notes" label="Notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} error={errors.notes} className="md:col-span-2" />
                    </div>

                    <div className="-mt-2 text-xs text-slate-500">
                        {hasMember
                            ? 'Member-linked staff: update personal details from the Member profile.'
                            : 'External staff: required fields must be provided. Linking a Christian Community does not create a Member record.'}
                    </div>

                    {Object.keys(errors ?? {}).length > 0 && (
                        <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            Please fix the highlighted fields.
                        </div>
                    )}

                    <div className="flex justify-end gap-2 pt-2">
                        <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                        <PrimaryButton disabled={processing} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function AssignmentsModal({ open, onClose, staff, canManage, assignmentRoles }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);
    const canManageInstitutions = useMemo(
        () => Array.isArray(permissions) && permissions.includes('institutions.view'),
        [permissions]
    );

    const [adding, setAdding] = useState(false);
    const [editing, setEditing] = useState(null);

    const hasAssignment = useMemo(() => {
        return Array.isArray(staff?.assignments) && staff.assignments.length > 0;
    }, [staff?.assignments]);

    const normalizeAssignment = (a) => {
        const normalizeDate = (v) => {
            if (typeof v !== 'string') return '';
            return v.length >= 10 ? v.slice(0, 10) : v;
        };

        return {
            ...a,
            role_uuid: a?.role?.uuid ?? a?.role_uuid ?? '',
            institution_uuid: a?.institution?.uuid ?? a?.institution_uuid ?? '',
            start_date: normalizeDate(a?.start_date ?? ''),
            end_date: normalizeDate(a?.end_date ?? ''),
        };
    };

    const close = () => {
        setAdding(false);
        setEditing(null);
        onClose();
    };

    if (!staff) {
        return <Modal show={open} onClose={close} />;
    }

    return (
        <Modal show={open} onClose={close} maxWidth="2xl">
            <div className="flex max-h-[85vh] flex-col">
                <div className="border-b border-slate-200 bg-white px-5 py-4">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0">
                            <h2 className="truncate text-lg font-semibold text-slate-900">Assignments</h2>
                            <p className="mt-1 text-sm text-slate-500">{staff.display_name}</p>
                        </div>
                        <div className="flex items-center gap-2">
                            {canManage && (
                                <button
                                    type="button"
                                    onClick={() => setAdding(true)}
                                    disabled={hasAssignment}
                                    className={`inline-flex h-10 items-center rounded-lg px-3 text-sm font-semibold text-white ${hasAssignment
                                        ? 'cursor-not-allowed bg-indigo-300'
                                        : 'bg-indigo-600 hover:bg-indigo-700'
                                        }`}
                                >
                                    +
                                </button>
                            )}
                            <button
                                type="button"
                                onClick={close}
                                className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>

                <div className="flex-1 overflow-auto px-5 py-5">
                    {(staff.assignments ?? []).length === 0 ? (
                        <div className="text-sm text-slate-600">No assignment history.</div>
                    ) : (
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Type</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Institution</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Title</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Start</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">End</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Active</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {(staff.assignments ?? []).map((a) => (
                                        <tr key={a.uuid} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{a.assignment_type}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{a.institution?.name ? toTitleCase(a.institution.name) : '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{a.title ?? '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{a.start_date ?? '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{a.end_date ?? '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{a.is_active ? 'Yes' : 'No'}</td>
                                            <td className="px-4 py-3 text-right">
                                                {canManage && (
                                                    <button
                                                        type="button"
                                                        onClick={() => setEditing(normalizeAssignment(a))}
                                                        className="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                                    >
                                                        Edit
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            <AssignmentModal
                open={adding}
                onClose={() => setAdding(false)}
                staff={staff}
                title="+ Assignment"
                submitRoute={route('parish-staff.assignments.store', staff.uuid)}
                assignmentRoles={assignmentRoles}
                onSaved={() => router.reload({ only: ['staff'], preserveScroll: true })}
            />

            <AssignmentModal
                open={!!editing}
                onClose={() => setEditing(null)}
                staff={staff}
                title="Edit assignment"
                method="patch"
                submitRoute={editing ? route('parish-staff.assignments.update', [staff.uuid, editing.uuid]) : null}
                initial={editing}
                assignmentRoles={assignmentRoles}
                onSaved={() => router.reload({ only: ['staff'], preserveScroll: true })}
            />
        </Modal>
    );
}

function AssignmentModal({ open, onClose, staff, title, submitRoute, method = 'post', initial, assignmentRoles, onSaved }) {
    const { data, setData, processing, errors, reset, submit } = useForm({
        role_uuid: initial?.role_uuid ?? '',
        institution_uuid: initial?.institution_uuid ?? '',
        title: initial?.title ?? '',
        start_date: initial?.start_date ?? '',
        end_date: initial?.end_date ?? '',
        is_active: initial?.is_active ?? true,
        notes: initial?.notes ?? '',
    });

    useEffect(() => {
        if (!open) return;

        setData({
            role_uuid: initial?.role_uuid ?? '',
            institution_uuid: initial?.institution_uuid ?? '',
            title: initial?.title ?? '',
            start_date: initial?.start_date ?? '',
            end_date: initial?.end_date ?? '',
            is_active: initial?.is_active ?? true,
            notes: initial?.notes ?? '',
        });
    }, [open, initial?.uuid]);

    const close = () => {
        onClose();
        reset();
    };

    const save = (e) => {
        e.preventDefault();
        if (!submitRoute) return;

        submit(method, submitRoute, {
            preserveScroll: true,
            onSuccess: () => close(),
        });
    };

    return (
        <Modal show={open} onClose={close} maxWidth="2xl">
            <div className="p-6">
                <ModalHeader
                    title={title}
                    subtitle="Choose a predefined position (this does not grant system access)."
                    onClose={close}
                    showRequiredNote
                />

                <form onSubmit={save} className="mt-4 space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <FloatingSelect
                            id="assignment_role_uuid"
                            label="Assignment position"
                            required
                            value={data.role_uuid}
                            onChange={(e) => setData('role_uuid', e.target.value)}
                            error={errors.role_uuid}
                            className="md:col-span-2"
                        >
                            <option value="">Select position</option>
                            {(assignmentRoles ?? []).map((r) => (
                                <option key={r.uuid} value={r.uuid}>{r.name}</option>
                            ))}
                        </FloatingSelect>

                        <SearchableInstitutionSelect
                            id="assignment_institution_uuid"
                            label="Institution (optional)"
                            value={data.institution_uuid}
                            onChange={(uuid) => setData('institution_uuid', uuid)}
                            error={errors.institution_uuid}
                            className="md:col-span-2"
                        />
                        <FloatingInput id="title" label="Title (optional)" value={data.title} onChange={(e) => setData('title', e.target.value)} error={errors.title} />
                        <FloatingInput id="start_date" label="Start date" type="date" value={data.start_date} onChange={(e) => setData('start_date', e.target.value)} error={errors.start_date} />
                        <FloatingInput id="end_date" label="End date" type="date" value={data.end_date} onChange={(e) => setData('end_date', e.target.value)} error={errors.end_date} />
                        {initial?.uuid && (
                            <FloatingSelect id="is_active_assignment" label="Active" value={data.is_active ? '1' : '0'} onChange={(e) => setData('is_active', e.target.value === '1')} error={errors.is_active}>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </FloatingSelect>
                        )}
                        <FloatingInput id="notes_assignment" label="Notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} error={errors.notes} />
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                        <PrimaryButton disabled={processing} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700">Save</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) {
        return null;
    }

    const isPrev = (label) => typeof label === 'string' && /previous/i.test(label);
    const isNext = (label) => typeof label === 'string' && /next/i.test(label);

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, idx) => {
                const prev = isPrev(link.label);
                const next = isNext(link.label);

                const className = `inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active
                    ? 'bg-indigo-600 text-white'
                    : link.url
                        ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                        : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-400'
                    }`;

                const label = prev ? 'Prev' : next ? 'Next' : link.label?.replace(/&laquo;|&raquo;/g, '') ?? '';

                return (
                    <button
                        key={idx}
                        type="button"
                        disabled={!link.url}
                        onClick={() => link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })}
                        className={className}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}

function PaginationSummary({ meta }) {
    if (!meta) return null;
    return (
        <div className="text-sm text-slate-600">
            Showing <span className="font-semibold">{meta.from ?? 0}</span> to <span className="font-semibold">{meta.to ?? 0}</span> of{' '}
            <span className="font-semibold">{meta.total ?? 0}</span>
        </div>
    );
}

function StaffViewModal({ open, onClose, staff }) {
    if (!staff) {
        return <Modal show={open} onClose={onClose} />;
    }

    const current = staff.current_assignment
        ? `${staff.current_assignment.assignment_type}${staff.current_assignment.title ? ` (${staff.current_assignment.title})` : ''}`
        : '-';

    const currentInstitution = staff.current_assignment?.institution?.name ?? '-';

    return (
        <Modal show={open} onClose={onClose} maxWidth="lg">
            <div className="p-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <h2 className="truncate text-lg font-semibold text-slate-900">Staff details</h2>
                        <p className="mt-1 text-sm text-slate-500">{staff.display_name}</p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Close
                    </button>
                </div>

                <div className="mt-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 text-sm">
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Current assignment</div>
                        <div className="text-slate-900">{current}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Institution</div>
                        <div className="text-slate-900">{currentInstitution ? toTitleCase(currentInstitution) : '-'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Zone</div>
                        <div className="text-slate-900">{staff.zone_name ? toTitleCase(staff.zone_name) : '-'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Christian Community</div>
                        <div className="text-slate-900">{staff.jumuiya_name ? toTitleCase(staff.jumuiya_name) : '-'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Family</div>
                        <div className="text-slate-900">{staff.family_name ? toTitleCase(staff.family_name) : '-'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Phone</div>
                        <div className="text-slate-900">{staff.phone ?? '-'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Email</div>
                        <div className="text-slate-900">{staff.email ?? '-'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">National ID</div>
                        <div className="text-slate-900">{staff.national_id ?? '-'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Status</div>
                        <div className="text-slate-900">{staff.is_active ? 'Active' : 'Inactive'}</div>
                    </div>
                    <div className="flex items-center justify-between gap-3">
                        <div className="font-semibold text-slate-700">Login</div>
                        <div className="text-slate-900">{staff.has_login ? 'Enabled' : 'Disabled'}</div>
                    </div>
                </div>
            </div>
        </Modal>
    );
}
