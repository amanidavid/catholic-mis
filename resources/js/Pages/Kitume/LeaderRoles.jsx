import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function KitumeLeaderRoles({
    association = null,
    leaderRoles = [],
    filters = {},
    can = {},
}) {
    const roleList = leaderRoles?.data ?? leaderRoles ?? [];
    const selectedQuery = filters?.roles_q ?? '';

    const [rolesQuery, setRolesQuery] = useState(selectedQuery);

    useEffect(() => {
        setRolesQuery(selectedQuery);
    }, [selectedQuery]);

    const runSearch = (nextQuery = rolesQuery) => {
        if (!association?.uuid) return;

        router.get(
            route('parish-associations.leader-roles.index', association.uuid),
            {
                roles_q: nextQuery || undefined,
            },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    };

    const roleForm = useForm({
        name: '',
        sort_order: 0,
        is_active: true,
    });
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRole, setEditingRole] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);

    const openCreateModal = () => {
        roleForm.clearErrors();
        setEditingRole(null);
        roleForm.setData({
            name: '',
            sort_order: (leaderRoles?.meta?.total ?? roleList.length) + 1,
            is_active: true,
        });
        setModalOpen(true);
    };

    const openEditModal = (role) => {
        roleForm.clearErrors();
        roleForm.setData({
            name: role?.name ?? '',
            sort_order: role?.sort_order ?? 0,
            is_active: !!role?.is_active,
        });
        setEditingRole(role);
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        setEditingRole(null);
        roleForm.clearErrors();
    };

    const submitRole = (e) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        };

        if (editingRole?.uuid) {
            roleForm.patch(route('parish-associations.leader-roles.update', editingRole.uuid), options);
            return;
        }

        roleForm.post(route('parish-associations.leader-roles.store'), options);
    };

    return (
        <AuthenticatedLayout header="Kitume Leader Positions">
            <Head title={`Leader Positions - ${association?.name ?? ''}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold text-slate-900">{association?.name}</h2>
                            <p className="mt-1 text-sm text-slate-500">Single table for leader positions in this kitume group context.</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={() => association?.uuid && router.visit(route('parish-associations.leaders.index', association.uuid))}
                                className="h-10 rounded-lg text-sm font-semibold normal-case"
                            >
                                Back to Leaders
                            </SecondaryButton>
                            {can?.manageLeaderRoles && (
                                <PrimaryButton
                                    type="button"
                                    onClick={openCreateModal}
                                    className="h-10 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700"
                                >
                                    New Position
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
                        className="flex items-end gap-3"
                    >
                        <div className="min-w-0 flex-1">
                            <FloatingInput
                                id="leader_roles_search"
                                label="Search position"
                                value={rolesQuery}
                                onChange={(e) => setRolesQuery(e.target.value)}
                            />
                        </div>
                        <PrimaryButton type="submit" className="h-11 shrink-0 rounded-xl bg-indigo-600 px-5 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                            Search
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => {
                                setRolesQuery('');
                                if (!association?.uuid) return;
                                router.get(
                                    route('parish-associations.leader-roles.index', association.uuid),
                                    {},
                                    { preserveState: true, replace: true, preserveScroll: true },
                                );
                            }}
                            className="h-11 shrink-0 rounded-xl px-5 text-sm font-semibold normal-case"
                        >
                            Clear
                        </SecondaryButton>
                    </form>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <SectionHeader
                        title="Leader Positions"
                        subtitle="Single table view for create, edit, and delete actions."
                    />
                    <LeaderRoleTable
                        rows={roleList}
                        canManage={!!can?.manageLeaderRoles}
                        onEdit={openEditModal}
                        onDelete={setDeleteTarget}
                    />
                    <div className="px-5 pb-5">
                        <TablePagination meta={leaderRoles?.meta} links={leaderRoles?.meta?.links ?? leaderRoles?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={modalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editingRole ? 'Edit Leader Position' : 'New Leader Position'}
                        subtitle="Create or update one leader position at a time."
                        onClose={closeModal}
                        showRequiredNote
                    />
                    <form onSubmit={submitRole} className="mt-4 space-y-4">
                        <FloatingInput
                            id="leader_role_name"
                            label="Position name"
                            required
                            value={roleForm.data.name}
                            onChange={(e) => roleForm.setData('name', e.target.value)}
                            error={roleForm.errors.name}
                        />
                        <FloatingInput
                            id="leader_role_sort_order"
                            label="Display order"
                            type="number"
                            value={roleForm.data.sort_order}
                            onChange={(e) => roleForm.setData('sort_order', e.target.value)}
                            error={roleForm.errors.sort_order}
                        />
                        <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input
                                type="checkbox"
                                checked={!!roleForm.data.is_active}
                                onChange={(e) => roleForm.setData('is_active', e.target.checked)}
                                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            Active
                        </label>

                        <div className="flex flex-wrap justify-end gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={closeModal}
                                disabled={roleForm.processing}
                                className="h-11 rounded-lg text-sm font-semibold normal-case"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={roleForm.processing} className="h-11 gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700">
                                {roleForm.processing && <Spinner size="sm" className="text-white" />}
                                <span>{editingRole ? 'Update Position' : 'Save Position'}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <ConfirmDialog
                open={!!deleteTarget}
                onCancel={() => setDeleteTarget(null)}
                title="Delete leader position"
                message="Delete this position only if it is no longer needed. Positions already used in leader records cannot be deleted."
                confirmText="Delete"
                onConfirm={() => {
                    if (!deleteTarget?.uuid) return;
                    router.delete(route('parish-associations.leader-roles.destroy', deleteTarget.uuid), {
                        preserveScroll: true,
                        onFinish: () => setDeleteTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}

function LeaderRoleTable({ rows, canManage, onEdit, onDelete }) {
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-slate-200 text-sm">
                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th className="px-4 py-3">Position</th>
                        <th className="px-4 py-3">Order</th>
                        <th className="px-4 py-3">Active Assignments</th>
                        <th className="px-4 py-3">Status</th>
                        {canManage && <th className="px-4 py-3 text-right">Actions</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={canManage ? 5 : 4} className="px-4 py-8 text-center text-sm text-slate-500">
                                No leader positions created yet.
                            </td>
                        </tr>
                    ) : rows.map((role) => (
                        <tr key={role.uuid}>
                            <td className="px-4 py-4 font-semibold text-slate-900">{role.name}</td>
                            <td className="px-4 py-4 text-slate-700">{role.sort_order ?? 0}</td>
                            <td className="px-4 py-4 text-slate-700">{role.active_assignments_count ?? 0}</td>
                            <td className="px-4 py-4">
                                <StatusBadge active={role.is_active} />
                            </td>
                            {canManage && (
                                <td className="px-4 py-4">
                                    <div className="flex justify-end gap-2">
                                        <ActionButton label="Edit" onClick={() => onEdit(role)} />
                                        <DangerButton label="Delete" onClick={() => onDelete(role)} />
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
