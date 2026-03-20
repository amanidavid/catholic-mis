import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function LeadershipRolesIndex({ roles, filters }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canView = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leadership-roles.view'), [permissions]);
    const canCreate = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leadership-roles.create'), [permissions]);
    const canUpdate = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leadership-roles.update'), [permissions]);
    const canDelete = useMemo(() => Array.isArray(permissions) && permissions.includes('jumuiya-leadership-roles.delete'), [permissions]);

    const [activeOnly, setActiveOnly] = useState(!!filters?.active_only);

    const applyFilter = () => {
        router.get(route('jumuiya-leadership-roles.index'), { active_only: activeOnly || undefined }, { preserveState: true, replace: true });
    };

    useEffect(() => {
        applyFilter();
    }, [activeOnly]);

    const dataList = roles?.data ?? roles ?? [];

    const [modalOpen, setModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const closeModal = () => {
        setModalOpen(false);
        setEditing(null);
        clearErrors();
    };

    const { data, setData, post, patch, processing, errors, clearErrors, reset } = useForm({
        name: '',
        system_role_name: '',
        is_active: true,
    });

    useEffect(() => {
        if (!modalOpen) return;

        clearErrors();
        if (editing) {
            setData({
                name: editing?.name ?? '',
                system_role_name: editing?.system_role_name ?? '',
                is_active: editing?.is_active ?? true,
            });
            return;
        }

        reset();
        setData({ name: '', system_role_name: '', is_active: true });
    }, [modalOpen, editing?.uuid]);

    const openAdd = () => {
        setEditing(null);
        setModalOpen(true);
    };

    const openEdit = (role) => {
        setEditing(role);
        setModalOpen(true);
    };

    const submit = (e) => {
        e.preventDefault();

        if (editing) {
            patch(route('jumuiya-leadership-roles.update', editing.uuid), {
                preserveScroll: true,
                onSuccess: closeModal,
            });
            return;
        }

        post(route('jumuiya-leadership-roles.store'), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    };

    return (
        <AuthenticatedLayout header="Leadership Roles">
            <Head title="Leadership Roles" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Leadership Roles</h1>
                        <p className="mt-1 text-sm text-slate-500">Manage leadership roles used in Christian Community leadership assignments.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={openAdd}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Role</span>
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    {!canView ? (
                        <div className="text-sm font-semibold text-rose-600">You do not have permission to view leadership roles.</div>
                    ) : (
                        <>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={activeOnly}
                                        onChange={(e) => setActiveOnly(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    Active only
                                </label>
                                <div className="text-sm text-slate-500">Total: {Array.isArray(dataList) ? dataList.length : 0}</div>
                            </div>

                            <div className="mt-6 overflow-x-auto">
                                <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                                    <table className="mis-table divide-y divide-slate-200">
                                        <thead>
                                            <tr>
                                                <th className="w-16">#</th>
                                                <th>Role</th>
                                                <th>System role</th>
                                                <th>Status</th>
                                                {(canUpdate || canDelete) && (
                                                    <th className="text-right">Actions</th>
                                                )}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {(dataList ?? []).map((r, idx) => (
                                                <RoleRow
                                                    key={r.uuid}
                                                    role={r}
                                                    striped={idx % 2 === 1}
                                                    index={idx + 1}
                                                    canUpdate={canUpdate}
                                                    canDelete={canDelete}
                                                    onEdit={() => openEdit(r)}
                                                />
                                            ))}

                                            {(dataList ?? []).length === 0 && (
                                                <tr>
                                                    <td
                                                        colSpan={(canUpdate || canDelete) ? 5 : 4}
                                                        className="px-4 py-10 text-center text-sm text-slate-500"
                                                    >
                                                        No roles found.
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

            <Modal show={modalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editing ? 'Edit role' : 'Add role'}
                        subtitle={editing ? 'Update role details.' : 'Create a new leadership role.'}
                        onClose={closeModal}
                        showRequiredNote
                    />

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="leadership_role_name"
                                label="Role name"
                                required
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                className="md:col-span-2"
                            />
                            <FloatingInput
                                id="leadership_role_system"
                                label="System role name"
                                value={data.system_role_name}
                                onChange={(e) => setData('system_role_name', e.target.value)}
                                error={errors.system_role_name}
                                className="md:col-span-2"
                            />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={!!data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                Active
                            </label>

                            <div className="flex gap-2">
                                <SecondaryButton
                                    type="button"
                                    onClick={closeModal}
                                    disabled={processing}
                                    className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                                >
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton
                                    disabled={processing}
                                    className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                                >
                                    {processing && <Spinner size="sm" className="text-white" />}
                                    <span>{editing ? 'Update' : 'Save'}</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

function RoleRow({ role, striped = false, index, canUpdate, canDelete, onEdit }) {
    const [confirmOpen, setConfirmOpen] = useState(false);

    const destroy = () => {
        router.delete(route('jumuiya-leadership-roles.destroy', role.uuid), { preserveScroll: true });
    };

    return (
        <>
            <tr className={`${striped ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                <td className="px-4 py-3 text-sm font-semibold text-slate-700">{index}</td>
                <td className="px-4 py-3 text-sm font-medium text-slate-900">{role.name}</td>
                <td className="px-4 py-3 text-sm text-slate-700">{role.system_role_name ?? '-'}</td>
                <td className="px-4 py-3 text-sm">
                    <span
                        className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${role.is_active
                            ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'
                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'
                            }`}
                    >
                        {role.is_active ? 'Active' : 'Inactive'}
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
                                    title="Edit"
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
                onClose={() => setConfirmOpen(false)}
                title="Delete role"
                message={`Are you sure you want to delete "${role.name}"?`}
                confirmText="Delete"
                onConfirm={destroy}
            />
        </>
    );
}
