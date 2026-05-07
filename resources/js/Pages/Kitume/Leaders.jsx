import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import Spinner from '@/Components/Spinner';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function KitumeLeaders({
    association = null,
    leaderships = [],
    outstations = [],
    leaderRoles = [],
    filters = {},
    can = {},
}) {
    const leadershipList = leaderships?.data ?? leaderships ?? [];
    const roleList = leaderRoles ?? [];
    const selectedOutstationUuid = filters?.outstation_uuid ?? '';
    const selectedLeadershipQuery = filters?.leadership_q ?? '';

    const [outstationFilter, setOutstationFilter] = useState(selectedOutstationUuid);
    const [leadershipSearch, setLeadershipSearch] = useState(selectedLeadershipQuery);

    useEffect(() => {
        setOutstationFilter(selectedOutstationUuid);
        setLeadershipSearch(selectedLeadershipQuery);
    }, [selectedOutstationUuid, selectedLeadershipQuery]);

    useEffect(() => {
        router.get(
            route('parish-associations.leaders.index', association.uuid),
            {
                outstation_uuid: outstationFilter || undefined,
                leadership_q: leadershipSearch || undefined,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, [outstationFilter]);

    const runSearch = (nextQuery = leadershipSearch) => {
        router.get(
            route('parish-associations.leaders.index', association.uuid),
            {
                outstation_uuid: outstationFilter || undefined,
                leadership_q: nextQuery || undefined,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    };

    const leadershipForm = useForm({
        member_uuid: '',
        role_uuid: '',
        start_date: '',
        end_date: '',
        is_active: true,
    });
    const [leadershipModalOpen, setLeadershipModalOpen] = useState(false);
    const [editingLeadership, setEditingLeadership] = useState(null);
    const [leadershipDeleteTarget, setLeadershipDeleteTarget] = useState(null);

    const openCreateLeadership = () => {
        leadershipForm.reset();
        leadershipForm.clearErrors();
        leadershipForm.setData({
            member_uuid: '',
            role_uuid: '',
            start_date: '',
            end_date: '',
            is_active: true,
        });
        setEditingLeadership(null);
        setLeadershipModalOpen(true);
    };

    const openEditLeadership = (leadership) => {
        leadershipForm.clearErrors();
        leadershipForm.setData({
            member_uuid: leadership?.member_uuid ?? '',
            role_uuid: leadership?.role_uuid ?? '',
            start_date: leadership?.start_date ?? '',
            end_date: leadership?.end_date ?? '',
            is_active: !!leadership?.is_active,
        });
        setEditingLeadership(leadership);
        setLeadershipModalOpen(true);
    };

    const closeLeadershipModal = () => {
        setLeadershipModalOpen(false);
        setEditingLeadership(null);
        leadershipForm.clearErrors();
    };

    const submitLeadership = (e) => {
        e.preventDefault();
        if (!association?.uuid) return;

        const options = {
            preserveScroll: true,
            onSuccess: () => closeLeadershipModal(),
        };

        if (editingLeadership?.uuid) {
            leadershipForm.patch(route('parish-associations.leadership.update', editingLeadership.uuid), options);
            return;
        }

        leadershipForm.post(route('parish-associations.leadership.store', association.uuid), options);
    };

    const openRoleManager = () => {
        if (!association?.uuid) return;

        router.visit(route('parish-associations.leader-roles.index', association.uuid));
    };

    return (
        <AuthenticatedLayout header="Kitume Leaders">
            <Head title={`Kitume Leaders - ${association?.name ?? ''}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">{association?.name}</h2>
                            <p className="mt-1 text-sm text-slate-500">Assign and manage leaders for this kitume group.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={() => router.visit(route('parish-associations.index'))}
                                className="h-10 rounded-lg text-sm font-semibold normal-case"
                            >
                                Back to Groups
                            </SecondaryButton>
                            {can?.manageLeaderRoles && (
                                <SecondaryButton
                                    type="button"
                                    onClick={openRoleManager}
                                    className="h-10 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold normal-case text-indigo-700 hover:bg-indigo-100"
                                >
                                    Manage Leader Positions
                                </SecondaryButton>
                            )}
                            {can?.manageLeadership && (
                                <PrimaryButton
                                    type="button"
                                    onClick={openCreateLeadership}
                                    className="h-10 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700"
                                >
                                    Add Leader
                                </PrimaryButton>
                            )}
                        </div>
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            runSearch();
                        }}
                        className="grid gap-3 md:grid-cols-[1fr_1fr_auto_auto] md:items-end"
                    >
                        <FloatingInput
                            id="leaders_search"
                            label="Search leaders or position"
                            value={leadershipSearch}
                            onChange={(e) => setLeadershipSearch(e.target.value)}
                        />
                        <FloatingSelect
                            id="leaders_outstation_filter"
                            label="Outstation"
                            value={outstationFilter}
                            onChange={(e) => setOutstationFilter(e.target.value)}
                        >
                            <option value="">All outstations</option>
                            {(outstations ?? []).map((outstation) => (
                                <option key={outstation.uuid} value={outstation.uuid}>{outstation.name}</option>
                            ))}
                        </FloatingSelect>
                        <PrimaryButton type="submit" className="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                            Search
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => {
                                setLeadershipSearch('');
                                setOutstationFilter('');
                                router.get(
                                    route('parish-associations.leaders.index', association.uuid),
                                    {},
                                    { preserveState: true, replace: true, preserveScroll: true },
                                );
                            }}
                            className="h-11 rounded-xl text-sm font-semibold normal-case"
                        >
                            Clear
                        </SecondaryButton>
                    </form>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <SectionHeader
                        title="Leader List"
                        subtitle="Only leader assignments for the selected kitume group are shown here."
                    />
                    <LeadershipTable
                        rows={leadershipList}
                        canManage={!!can?.manageLeadership}
                        onEdit={openEditLeadership}
                        onDelete={setLeadershipDeleteTarget}
                    />
                    <div className="px-5 pb-5">
                        <TablePagination meta={leaderships?.meta} links={leaderships?.meta?.links ?? leaderships?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={leadershipModalOpen} onClose={closeLeadershipModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editingLeadership ? 'Update Leader Assignment' : 'Assign Leader'}
                        subtitle={association ? `Selected group: ${association.name}` : 'Select a group first.'}
                        onClose={closeLeadershipModal}
                        showRequiredNote
                    />
                    <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="text-sm font-semibold text-slate-900">Missing a leader position?</p>
                                <p className="mt-1 text-xs text-slate-500">Create or edit positions here, then return to finish the leader assignment.</p>
                            </div>
                            {can?.manageLeaderRoles && (
                                <SecondaryButton
                                    type="button"
                                    onClick={openRoleManager}
                                    className="h-9 rounded-lg border border-indigo-200 bg-white px-3 text-xs font-semibold normal-case text-indigo-700 hover:bg-indigo-50"
                                >
                                    Go to Positions Page
                                </SecondaryButton>
                            )}
                        </div>
                    </div>
                    <form onSubmit={submitLeadership} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            {!editingLeadership && (
                                <div className="md:col-span-2">
                                    <SearchableMemberSelect
                                        id="association_leader_member_uuid"
                                        label="Parish member"
                                        value={leadershipForm.data.member_uuid}
                                        onChange={(uuid) => leadershipForm.setData('member_uuid', uuid)}
                                        outstationUuid={outstationFilter}
                                        error={leadershipForm.errors.member_uuid}
                                    />
                                </div>
                            )}
                            <FloatingSelect
                                id="leader_role_uuid"
                                label="Leader position"
                                required
                                value={leadershipForm.data.role_uuid}
                                onChange={(e) => leadershipForm.setData('role_uuid', e.target.value)}
                                error={leadershipForm.errors.role_uuid}
                            >
                                <option value="">Select position</option>
                                {roleList.filter((role) => role.is_active).map((role) => (
                                    <option key={role.uuid} value={role.uuid}>{role.name}</option>
                                ))}
                            </FloatingSelect>
                            <FloatingInput
                                id="leader_start_date"
                                label="Start date"
                                type="date"
                                required
                                value={leadershipForm.data.start_date}
                                onChange={(e) => leadershipForm.setData('start_date', e.target.value)}
                                error={leadershipForm.errors.start_date}
                            />
                            <FloatingInput
                                id="leader_end_date"
                                label="End date"
                                type="date"
                                value={leadershipForm.data.end_date}
                                onChange={(e) => leadershipForm.setData('end_date', e.target.value)}
                                error={leadershipForm.errors.end_date}
                            />
                        </div>

                        <div className="flex items-center justify-between gap-3">
                            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={!!leadershipForm.data.is_active}
                                    onChange={(e) => leadershipForm.setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Active
                            </label>

                            <div className="flex gap-2">
                                <SecondaryButton type="button" onClick={closeLeadershipModal} disabled={leadershipForm.processing} className="h-11 rounded-lg text-sm font-semibold normal-case">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton disabled={leadershipForm.processing} className="h-11 gap-2 rounded-lg bg-indigo-600 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                                    {leadershipForm.processing && <Spinner size="sm" className="text-white" />}
                                    <span>{editingLeadership ? 'Update' : 'Save'}</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </Modal>

            <ConfirmDialog
                open={!!leadershipDeleteTarget}
                onCancel={() => setLeadershipDeleteTarget(null)}
                title="Delete leader assignment"
                message="Only ended or inactive leadership records can be deleted."
                confirmText="Delete"
                onConfirm={() => {
                    if (!leadershipDeleteTarget?.uuid) return;
                    router.delete(route('parish-associations.leadership.destroy', leadershipDeleteTarget.uuid), {
                        preserveScroll: true,
                        onFinish: () => setLeadershipDeleteTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}

function LeadershipTable({ rows, canManage, onEdit, onDelete }) {
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th className="px-4 py-3">Leader</th>
                        <th className="px-4 py-3">Position</th>
                        <th className="px-4 py-3">Outstation / Jumuiya</th>
                        <th className="px-4 py-3">Start</th>
                        <th className="px-4 py-3">Status</th>
                        {canManage && <th className="px-4 py-3 text-right">Actions</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={canManage ? 6 : 5} className="px-4 py-8 text-center text-sm text-slate-500">
                                No leader assignments found for this group and current filter.
                            </td>
                        </tr>
                    ) : rows.map((leadership) => (
                        <tr key={leadership.uuid}>
                            <td className="px-4 py-4">
                                <div className="font-semibold text-slate-900">{leadership.member_name}</div>
                                <div className="mt-1 text-xs text-slate-500">{leadership.phone || leadership.email || 'No phone or email'}</div>
                            </td>
                            <td className="px-4 py-4 font-semibold text-slate-800">{leadership.role_name || '-'}</td>
                            <td className="px-4 py-4 text-slate-600">
                                <div>{leadership.outstation_name || '-'}</div>
                                <div className="mt-1 text-xs text-slate-500">{leadership.jumuiya_name || '-'}</div>
                            </td>
                            <td className="px-4 py-4 text-slate-700">{leadership.start_date || '-'}</td>
                            <td className="px-4 py-4">
                                <StatusBadge active={leadership.is_active} />
                            </td>
                            {canManage && (
                                <td className="px-4 py-4">
                                    <div className="flex justify-end gap-2">
                                        <ActionButton label="Edit" onClick={() => onEdit(leadership)} />
                                        <DangerButton label="Remove" onClick={() => onDelete(leadership)} />
                                    </div>
                                </td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function SectionHeader({ title, subtitle }) {
    return (
        <div className="border-b border-slate-200 px-5 py-4">
            <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
            <p className="mt-1 text-sm text-slate-500">{subtitle}</p>
        </div>
    );
}

function StatusBadge({ active }) {
    return (
        <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>
            {active ? 'Active' : 'Inactive'}
        </span>
    );
}

function ActionButton({ label, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
        >
            {label}
        </button>
    );
}

function DangerButton({ label, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50"
        >
            {label}
        </button>
    );
}

function TablePagination({ meta, links }) {
    if (!meta || !Array.isArray(links) || links.length === 0) return null;

    const isPrev = (label) => typeof label === 'string' && /previous/i.test(label);
    const isNext = (label) => typeof label === 'string' && /next/i.test(label);

    return (
        <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm text-slate-600">
                {meta?.from && meta?.to ? `Showing ${meta.from}-${meta.to}` : `Page ${meta?.current_page ?? 1}`}
            </div>
            <div className="flex flex-wrap gap-2">
                {links.map((link, index) => {
                    const prev = isPrev(link.label);
                    const next = isNext(link.label);
                    const label = prev ? 'Prev' : next ? 'Next' : link.label?.replace(/&laquo;|&raquo;/g, '') ?? '';
                    if (label === '...' || label === '') return null;

                    return (
                        <button
                            key={`${label}-${index}`}
                            type="button"
                            disabled={!link.url || link.active}
                            onClick={() => link.url && router.visit(link.url, { preserveScroll: true, preserveState: true, replace: true })}
                            className={`rounded-lg px-3 py-2 text-sm font-semibold ${link.active
                                ? 'bg-indigo-600 text-white'
                                : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50'
                                }`}
                        >
                            {label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
