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

export default function KitumeMembers({
    association = null,
    memberships = [],
    outstations = [],
    filters = {},
    can = {},
}) {
    const membershipList = memberships?.data ?? memberships ?? [];
    const selectedOutstationUuid = filters?.outstation_uuid ?? '';
    const selectedMembersQuery = filters?.members_q ?? '';

    const [outstationFilter, setOutstationFilter] = useState(selectedOutstationUuid);
    const [membersSearch, setMembersSearch] = useState(selectedMembersQuery);

    useEffect(() => {
        setOutstationFilter(selectedOutstationUuid);
        setMembersSearch(selectedMembersQuery);
    }, [selectedOutstationUuid, selectedMembersQuery]);

    useEffect(() => {
        router.get(
            route('parish-associations.members.index', association.uuid),
            {
                outstation_uuid: outstationFilter || undefined,
                members_q: membersSearch || undefined,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, [outstationFilter]);

    const runSearch = (nextQuery = membersSearch) => {
        router.get(
            route('parish-associations.members.index', association.uuid),
            {
                outstation_uuid: outstationFilter || undefined,
                members_q: nextQuery || undefined,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    };

    const membershipForm = useForm({
        member_uuid: '',
        joined_at: '',
        end_date: '',
        notes: '',
        is_active: true,
    });
    const [membershipModalOpen, setMembershipModalOpen] = useState(false);
    const [editingMembership, setEditingMembership] = useState(null);
    const [membershipDeleteTarget, setMembershipDeleteTarget] = useState(null);

    const openCreateMembership = () => {
        membershipForm.reset();
        membershipForm.clearErrors();
        membershipForm.setData({
            member_uuid: '',
            joined_at: '',
            end_date: '',
            notes: '',
            is_active: true,
        });
        setEditingMembership(null);
        setMembershipModalOpen(true);
    };

    const openEditMembership = (membership) => {
        membershipForm.clearErrors();
        membershipForm.setData({
            member_uuid: membership?.member_uuid ?? '',
            joined_at: membership?.joined_at ?? '',
            end_date: membership?.end_date ?? '',
            notes: membership?.notes ?? '',
            is_active: !!membership?.is_active,
        });
        setEditingMembership(membership);
        setMembershipModalOpen(true);
    };

    const closeMembershipModal = () => {
        setMembershipModalOpen(false);
        setEditingMembership(null);
        membershipForm.clearErrors();
    };

    const submitMembership = (e) => {
        e.preventDefault();
        if (!association?.uuid) return;

        const options = {
            preserveScroll: true,
            onSuccess: () => closeMembershipModal(),
        };

        if (editingMembership?.uuid) {
            membershipForm.patch(route('parish-associations.members.update', editingMembership.uuid), options);
            return;
        }

        membershipForm.post(route('parish-associations.members.store', association.uuid), options);
    };

    return (
        <AuthenticatedLayout header="Kitume Members">
            <Head title={`Kitume Members - ${association?.name ?? ''}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">{association?.name}</h2>
                            <p className="mt-1 text-sm text-slate-500">Members page for this kitume group.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={() => router.visit(route('parish-associations.index'))}
                                className="h-10 rounded-lg text-sm font-semibold normal-case"
                            >
                                Back to Groups
                            </SecondaryButton>
                            {can?.manageMembers && (
                                <PrimaryButton
                                    type="button"
                                    onClick={openCreateMembership}
                                    className="h-10 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700"
                                >
                                    Add Member
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
                            id="members_search"
                            label="Search members"
                            value={membersSearch}
                            onChange={(e) => setMembersSearch(e.target.value)}
                        />
                        <FloatingSelect
                            id="members_outstation_filter"
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
                                setMembersSearch('');
                                setOutstationFilter('');
                                router.get(
                                    route('parish-associations.members.index', association.uuid),
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
                        title="Member List"
                        subtitle="Only members for the selected kitume group are shown here."
                    />
                    <MembershipTable
                        rows={membershipList}
                        canManage={!!can?.manageMembers}
                        onEdit={openEditMembership}
                        onDelete={setMembershipDeleteTarget}
                    />
                    <div className="px-5 pb-5">
                        <TablePagination meta={memberships?.meta} links={memberships?.meta?.links ?? memberships?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={membershipModalOpen} onClose={closeMembershipModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editingMembership ? 'Update Membership' : 'Add Member'}
                        subtitle={association ? `Selected group: ${association.name}` : 'Select a group first.'}
                        onClose={closeMembershipModal}
                        showRequiredNote={!editingMembership}
                    />
                    <form onSubmit={submitMembership} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            {!editingMembership && (
                                <div className="md:col-span-2">
                                    <SearchableMemberSelect
                                        id="association_member_uuid"
                                        label="Parish member"
                                        value={membershipForm.data.member_uuid}
                                        onChange={(uuid) => membershipForm.setData('member_uuid', uuid)}
                                        outstationUuid={outstationFilter}
                                        error={membershipForm.errors.member_uuid}
                                    />
                                </div>
                            )}
                            <FloatingInput
                                id="association_joined_at"
                                label="Joined date"
                                type="date"
                                value={membershipForm.data.joined_at}
                                onChange={(e) => membershipForm.setData('joined_at', e.target.value)}
                                error={membershipForm.errors.joined_at}
                            />
                            <FloatingInput
                                id="association_end_date"
                                label="End date"
                                type="date"
                                value={membershipForm.data.end_date}
                                onChange={(e) => membershipForm.setData('end_date', e.target.value)}
                                error={membershipForm.errors.end_date}
                            />
                            <div className="md:col-span-2">
                                <label className="mb-1 block text-sm font-semibold text-slate-700">Notes</label>
                                <textarea
                                    value={membershipForm.data.notes}
                                    onChange={(e) => membershipForm.setData('notes', e.target.value)}
                                    rows={3}
                                    className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                />
                                {membershipForm.errors.notes && <div className="mt-1 text-xs font-semibold text-rose-600">{membershipForm.errors.notes}</div>}
                            </div>
                        </div>

                        <div className="flex items-center justify-between gap-3">
                            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={!!membershipForm.data.is_active}
                                    onChange={(e) => membershipForm.setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Active
                            </label>

                            <div className="flex gap-2">
                                <SecondaryButton type="button" onClick={closeMembershipModal} disabled={membershipForm.processing} className="h-11 rounded-lg text-sm font-semibold normal-case">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton disabled={membershipForm.processing} className="h-11 gap-2 rounded-lg bg-indigo-600 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                                    {membershipForm.processing && <Spinner size="sm" className="text-white" />}
                                    <span>{editingMembership ? 'Update' : 'Save'}</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </Modal>

            <ConfirmDialog
                open={!!membershipDeleteTarget}
                onCancel={() => setMembershipDeleteTarget(null)}
                title="Delete membership"
                message="This removes the member from the selected group."
                confirmText="Delete"
                onConfirm={() => {
                    if (!membershipDeleteTarget?.uuid) return;
                    router.delete(route('parish-associations.members.destroy', membershipDeleteTarget.uuid), {
                        preserveScroll: true,
                        onFinish: () => setMembershipDeleteTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}

function MembershipTable({ rows, canManage, onEdit, onDelete }) {
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th className="px-4 py-3">Member</th>
                        <th className="px-4 py-3">Outstation / Jumuiya</th>
                        <th className="px-4 py-3">Joined</th>
                        <th className="px-4 py-3">Status</th>
                        {canManage && <th className="px-4 py-3 text-right">Actions</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={canManage ? 5 : 4} className="px-4 py-8 text-center text-sm text-slate-500">
                                No members found for this group and current filter.
                            </td>
                        </tr>
                    ) : rows.map((membership) => (
                        <tr key={membership.uuid}>
                            <td className="px-4 py-4">
                                <div className="font-semibold text-slate-900">{membership.member_name}</div>
                                <div className="mt-1 text-xs text-slate-500">{membership.phone || membership.email || 'No phone or email'}</div>
                            </td>
                            <td className="px-4 py-4 text-slate-600">
                                <div>{membership.outstation_name || '-'}</div>
                                <div className="mt-1 text-xs text-slate-500">{membership.jumuiya_name || '-'}</div>
                            </td>
                            <td className="px-4 py-4 text-slate-700">{membership.joined_at || '-'}</td>
                            <td className="px-4 py-4">
                                <StatusBadge active={membership.is_active} />
                            </td>
                            {canManage && (
                                <td className="px-4 py-4">
                                    <div className="flex justify-end gap-2">
                                        <ActionButton label="Edit" onClick={() => onEdit(membership)} />
                                        <DangerButton label="Remove" onClick={() => onDelete(membership)} />
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
