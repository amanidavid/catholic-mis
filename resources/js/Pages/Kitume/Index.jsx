import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function KitumeIndex({ associations = [], outstations = [], filters = {}, can = {} }) {
    const associationList = associations?.data ?? associations ?? [];
    const selectedOutstationUuid = filters?.outstation_uuid ?? '';
    const selectedGroupsQuery = filters?.groups_q ?? '';
    const selectedGroupsStatus = filters?.groups_status ?? 'all';
    const selectedGroupsSort = filters?.groups_sort ?? 'name';

    const [outstationFilter, setOutstationFilter] = useState(selectedOutstationUuid);
    const [groupSearch, setGroupSearch] = useState(selectedGroupsQuery);
    const [groupStatus, setGroupStatus] = useState(selectedGroupsStatus);
    const [groupSort, setGroupSort] = useState(selectedGroupsSort);

    useEffect(() => {
        setOutstationFilter(selectedOutstationUuid);
        setGroupSearch(selectedGroupsQuery);
        setGroupStatus(selectedGroupsStatus);
        setGroupSort(selectedGroupsSort);
    }, [selectedOutstationUuid, selectedGroupsQuery, selectedGroupsStatus, selectedGroupsSort]);

    useEffect(() => {
        router.get(
            route('parish-associations.index'),
            {
                outstation_uuid: outstationFilter || undefined,
                groups_q: groupSearch || undefined,
                groups_status: groupStatus || undefined,
                groups_sort: groupSort || undefined,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, [outstationFilter]);

    const runGroupSearch = () => {
        router.get(
            route('parish-associations.index'),
            {
                outstation_uuid: outstationFilter || undefined,
                groups_q: groupSearch || undefined,
                groups_status: groupStatus || undefined,
                groups_sort: groupSort || undefined,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    };

    const associationForm = useForm({
        name: '',
        code: '',
        description: '',
        sort_order: 0,
        is_active: true,
    });
    const [associationModalOpen, setAssociationModalOpen] = useState(false);
    const [editingAssociation, setEditingAssociation] = useState(null);
    const [associationDeleteTarget, setAssociationDeleteTarget] = useState(null);

    const openCreateAssociation = () => {
        associationForm.reset();
        associationForm.clearErrors();
        associationForm.setData({
            name: '',
            code: '',
            description: '',
            sort_order: (associationList?.length ?? 0) + 1,
            is_active: true,
        });
        setEditingAssociation(null);
        setAssociationModalOpen(true);
    };

    const openEditAssociation = (association) => {
        associationForm.clearErrors();
        associationForm.setData({
            name: association?.name ?? '',
            code: association?.code ?? '',
            description: association?.description ?? '',
            sort_order: association?.sort_order ?? 0,
            is_active: !!association?.is_active,
        });
        setEditingAssociation(association);
        setAssociationModalOpen(true);
    };

    const closeAssociationModal = () => {
        setAssociationModalOpen(false);
        setEditingAssociation(null);
        associationForm.clearErrors();
    };

    const submitAssociation = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => closeAssociationModal(),
        };

        if (editingAssociation?.uuid) {
            associationForm.patch(route('parish-associations.update', editingAssociation.uuid), options);
            return;
        }

        associationForm.post(route('parish-associations.store'), options);
    };

    return (
        <AuthenticatedLayout header="Kitume Groups">
            <Head title="Kitume Groups" />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            runGroupSearch();
                        }}
                        className="grid gap-3 xl:grid-cols-[1.4fr_1fr_1fr_1fr_auto_auto]"
                    >
                        <FloatingInput
                            id="group_search"
                            label="Search group"
                            value={groupSearch}
                            onChange={(e) => setGroupSearch(e.target.value)}
                        />
                        <FloatingSelect
                            id="outstation_filter"
                            label="Outstation"
                            value={outstationFilter}
                            onChange={(e) => setOutstationFilter(e.target.value)}
                        >
                            <option value="">All outstations</option>
                            {(outstations ?? []).map((outstation) => (
                                <option key={outstation.uuid} value={outstation.uuid}>{outstation.name}</option>
                            ))}
                        </FloatingSelect>
                        <FloatingSelect
                            id="group_status"
                            label="Status"
                            value={groupStatus}
                            onChange={(e) => setGroupStatus(e.target.value)}
                        >
                            <option value="all">All groups</option>
                            <option value="active">Active groups</option>
                            <option value="inactive">Inactive groups</option>
                            <option value="has_leaders">Has leaders</option>
                            <option value="no_leaders">No leaders</option>
                        </FloatingSelect>
                        <FloatingSelect
                            id="group_sort"
                            label="Sort by"
                            value={groupSort}
                            onChange={(e) => setGroupSort(e.target.value)}
                        >
                            <option value="name">Name</option>
                            <option value="members">Members</option>
                            <option value="leaders">Leaders</option>
                            <option value="order">Display order</option>
                        </FloatingSelect>
                        <PrimaryButton type="submit" className="h-11 rounded-xl bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                            Search
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => {
                                setGroupSearch('');
                                setGroupStatus('all');
                                setGroupSort('name');
                                setOutstationFilter('');
                                router.get(
                                    route('parish-associations.index'),
                                    {
                                        outstation_uuid: undefined,
                                        groups_q: undefined,
                                        groups_status: 'all',
                                        groups_sort: 'name',
                                    },
                                    { preserveState: true, replace: true, preserveScroll: true },
                                );
                            }}
                            className="h-11 rounded-xl text-sm font-semibold normal-case"
                        >
                            Reset
                        </SecondaryButton>
                    </form>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">Groups</h2>
                            <p className="mt-1 text-sm text-slate-500">Table list with direct actions for members and leaders.</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                {associations?.meta?.total ?? associationList.length} results
                            </span>
                            {can?.createAssociation && (
                                <PrimaryButton
                                    type="button"
                                    onClick={openCreateAssociation}
                                    className="h-9 rounded-lg bg-indigo-600 px-3 text-xs font-semibold normal-case text-white hover:bg-indigo-700"
                                >
                                    New Group
                                </PrimaryButton>
                            )}
                        </div>
                    </div>

                    <div className="hidden lg:block">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th className="px-5 py-3">Group</th>
                                        <th className="px-4 py-3 text-right">Members</th>
                                        <th className="px-4 py-3 text-right">Leaders</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-5 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {associationList.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-5 py-8 text-center text-sm text-slate-500">
                                                No groups found. Change filters or create a new group.
                                            </td>
                                        </tr>
                                    ) : associationList.map((association) => (
                                        <tr key={association.uuid} className="bg-white">
                                            <td className="px-5 py-4">
                                                <div className="font-semibold text-slate-900">{association.name}</div>
                                                <div className="mt-1 text-xs text-slate-500">
                                                    {association.code || 'No code'} · Order {association.sort_order ?? 0}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4 text-right font-semibold text-slate-800">{association.active_members_count ?? 0}</td>
                                            <td className="px-4 py-4 text-right font-semibold text-slate-800">{association.active_leaders_count ?? 0}</td>
                                            <td className="px-4 py-4">
                                                <StatusBadge active={association.is_active} />
                                            </td>
                                            <td className="px-5 py-4 text-right">
                                                <div className="inline-flex flex-wrap justify-end gap-2">
                                                    {can?.updateAssociation && (
                                                        <ActionButton label="Edit" onClick={() => openEditAssociation(association)} />
                                                    )}
                                                    {can?.deleteAssociation && (
                                                        <DangerButton label="Delete" onClick={() => setAssociationDeleteTarget(association)} />
                                                    )}
                                                    <button
                                                        type="button"
                                                        onClick={() => router.visit(route('parish-associations.members.index', association.uuid))}
                                                        className="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                                    >
                                                        Members
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => router.visit(route('parish-associations.leaders.index', association.uuid))}
                                                        className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                    >
                                                        Leaders
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="space-y-3 p-4 lg:hidden">
                        {associationList.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                                No groups found. Change filters or create a new group.
                            </div>
                        ) : associationList.map((association) => (
                            <div key={association.uuid} className="rounded-2xl border border-slate-200 bg-white p-4">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <div className="font-semibold text-slate-900">{association.name}</div>
                                        <div className="mt-1 text-xs text-slate-500">
                                            {association.code || 'No code'} · Members {association.active_members_count ?? 0} · Leaders {association.active_leaders_count ?? 0}
                                        </div>
                                    </div>
                                    <StatusBadge active={association.is_active} />
                                </div>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {can?.updateAssociation && (
                                        <ActionButton label="Edit" onClick={() => openEditAssociation(association)} />
                                    )}
                                    {can?.deleteAssociation && (
                                        <DangerButton label="Delete" onClick={() => setAssociationDeleteTarget(association)} />
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => router.visit(route('parish-associations.members.index', association.uuid))}
                                        className="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white"
                                    >
                                        Members
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => router.visit(route('parish-associations.leaders.index', association.uuid))}
                                        className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700"
                                    >
                                        Leaders
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="border-t border-slate-200 px-5 py-4">
                        <TablePagination meta={associations?.meta} links={associations?.meta?.links ?? associations?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={associationModalOpen} onClose={closeAssociationModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editingAssociation ? 'Edit Group' : 'New Group'}
                        subtitle="Create or update one parish-level kitume group."
                        onClose={closeAssociationModal}
                        showRequiredNote
                    />
                    <form onSubmit={submitAssociation} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="association_name"
                                label="Group name"
                                required
                                value={associationForm.data.name}
                                onChange={(e) => associationForm.setData('name', e.target.value)}
                                error={associationForm.errors.name}
                            />
                            <FloatingInput
                                id="association_code"
                                label="Short code"
                                value={associationForm.data.code}
                                onChange={(e) => associationForm.setData('code', e.target.value)}
                                error={associationForm.errors.code}
                            />
                            <FloatingInput
                                id="association_sort_order"
                                label="Display order"
                                type="number"
                                value={associationForm.data.sort_order}
                                onChange={(e) => associationForm.setData('sort_order', Number(e.target.value || 0))}
                                error={associationForm.errors.sort_order}
                            />
                            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={!!associationForm.data.is_active}
                                    onChange={(e) => associationForm.setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Active
                            </label>
                            <div className="md:col-span-2">
                                <label className="mb-1 block text-sm font-semibold text-slate-700">Description</label>
                                <textarea
                                    value={associationForm.data.description}
                                    onChange={(e) => associationForm.setData('description', e.target.value)}
                                    rows={4}
                                    className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                />
                                {associationForm.errors.description && <div className="mt-1 text-xs font-semibold text-rose-600">{associationForm.errors.description}</div>}
                            </div>
                        </div>

                        <div className="flex justify-end gap-2">
                            <SecondaryButton type="button" onClick={closeAssociationModal} disabled={associationForm.processing} className="h-11 rounded-lg text-sm font-semibold normal-case">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={associationForm.processing} className="h-11 gap-2 rounded-lg bg-indigo-600 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                                {associationForm.processing && <Spinner size="sm" className="text-white" />}
                                <span>{editingAssociation ? 'Update' : 'Save'}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <ConfirmDialog
                open={!!associationDeleteTarget}
                onCancel={() => setAssociationDeleteTarget(null)}
                title="Delete group"
                message="This group will be deleted only if it has no members and no leadership records."
                confirmText="Delete"
                onConfirm={() => {
                    if (!associationDeleteTarget?.uuid) return;
                    router.delete(route('parish-associations.destroy', associationDeleteTarget.uuid), {
                        preserveScroll: true,
                        onFinish: () => setAssociationDeleteTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
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

