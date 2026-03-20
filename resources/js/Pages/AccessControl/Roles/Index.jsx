import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function AccessControlRolesIndex({ roles, permissions, filters }) {
    const { auth } = usePage().props;
    const permissionsList = useMemo(() => permissions ?? [], [permissions]);

    const roleRows = roles?.data ?? [];

    const [q, setQ] = useState(filters?.q ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('access-control.roles.index'), { q: q || undefined }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        router.get(route('access-control.roles.index'), {}, { preserveState: true, replace: true });
    };

    const [roleModalOpen, setRoleModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const closeRoleModal = () => {
        setRoleModalOpen(false);
        setEditing(null);
        clearRoleErrors();
    };

    const {
        data: roleData,
        setData: setRoleData,
        post,
        patch,
        processing: roleProcessing,
        errors: roleErrors,
        clearErrors: clearRoleErrors,
        reset: resetRoleForm,
    } = useForm({
        name: '',
    });

    useEffect(() => {
        if (!roleModalOpen) return;

        clearRoleErrors();
        if (editing) {
            setRoleData({
                name: editing?.name ?? '',
            });
            return;
        }

        resetRoleForm();
        setRoleData({ name: '' });
    }, [roleModalOpen, editing?.id]);

    const openAdd = () => {
        setEditing(null);
        setRoleModalOpen(true);
    };

    const openEdit = (r) => {
        setEditing(r);
        setRoleModalOpen(true);
    };

    const submitRole = (e) => {
        e.preventDefault();

        if (editing) {
            patch(route('access-control.roles.update', editing.id), {
                preserveScroll: true,
                onSuccess: closeRoleModal,
            });
            return;
        }

        post(route('access-control.roles.store'), {
            preserveScroll: true,
            onSuccess: closeRoleModal,
        });
    };

    const [permModalOpen, setPermModalOpen] = useState(false);
    const [permRole, setPermRole] = useState(null);

    const closePermModal = () => {
        setPermModalOpen(false);
        setPermRole(null);
        clearPermErrors();
        resetPermForm();
    };

    const {
        data: permData,
        setData: setPermData,
        post: postPerm,
        transform: transformPerm,
        processing: permProcessing,
        errors: permErrors,
        clearErrors: clearPermErrors,
        reset: resetPermForm,
    } = useForm({
        permissions: [],
    });

    useEffect(() => {
        transformPerm((payload) => ({
            ...payload,
            permissions: Array.isArray(payload?.permissions)
                ? payload.permissions
                    .map((p) => (typeof p === 'string' ? p : (p?.name ?? null)))
                    .filter((p) => typeof p === 'string' && p.length > 0)
                : [],
        }));
    }, []);

    const originalPerms = useMemo(() => (
        Array.isArray(permRole?.permissions)
            ? permRole.permissions
                .map((p) => (typeof p === 'string' ? p : (p?.name ?? null)))
                .filter((p) => typeof p === 'string' && p.length > 0)
            : []
    ), [permRole?.id]);
    const selectedPerms = useMemo(() => (Array.isArray(permData.permissions) ? permData.permissions : []), [permData.permissions]);
    const pendingAdded = useMemo(() => selectedPerms.filter((p) => !originalPerms.includes(p)), [selectedPerms, originalPerms]);
    const pendingRemoved = useMemo(() => originalPerms.filter((p) => !selectedPerms.includes(p)), [selectedPerms, originalPerms]);

    const openPermissions = (r) => {
        setPermRole(r);

        const next = Array.isArray(r?.permissions)
            ? r.permissions
                .map((p) => (typeof p === 'string' ? p : (p?.name ?? null)))
                .filter((p) => typeof p === 'string' && p.length > 0)
            : [];

        setPermData({ permissions: next });
        setPermModalOpen(true);
    };

    const togglePermission = (permName) => {
        const current = Array.isArray(permData.permissions) ? permData.permissions : [];
        if (current.includes(permName)) {
            setPermData('permissions', current.filter((p) => p !== permName));
            return;
        }

        setPermData('permissions', [...current, permName]);
    };

    const submitPermissions = (e) => {
        e.preventDefault();
        if (!permRole) return;

        postPerm(route('access-control.roles.permissions.sync', permRole.id), {
            preserveScroll: true,
            onSuccess: closePermModal,
        });
    };

    const groupedPermissions = useMemo(() => {
        const groups = {};

        (permissionsList ?? []).forEach((p) => {
            const moduleName = p.module || 'Other';
            if (!groups[moduleName]) {
                groups[moduleName] = [];
            }
            groups[moduleName].push(p);
        });

        return Object.entries(groups)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([moduleName, items]) => ({
                moduleName,
                items: (items ?? []).slice().sort((x, y) => (x.display_name || x.name).localeCompare(y.display_name || y.name)),
            }));
    }, [permissionsList]);

    return (
        <AuthenticatedLayout header="Access Control">
            <Head title="Access Control - Roles" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">System Roles</h1>
                        <p className="mt-1 text-sm text-slate-500">Create roles and assign permissions.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('access-control.permissions.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            View permissions
                        </Link>
                        <PrimaryButton
                            type="button"
                            onClick={openAdd}
                            className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                        >
                            <span className="text-lg font-bold leading-none">+</span>
                            <span>Role</span>
                        </PrimaryButton>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <FloatingInput
                            id="roles_q"
                            label="Search role"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            className="sm:max-w-sm"
                        />
                        <div className="flex gap-2">
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
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th className="w-16">#</th>
                                        <th>Role</th>
                                        <th>Permissions</th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {roleRows.map((r, idx) => (
                                        <RoleRow
                                            key={r.id}
                                            role={r}
                                            striped={idx % 2 === 1}
                                            index={(roles?.meta?.from ?? 1) + idx}
                                            onEdit={() => openEdit(r)}
                                            onPermissions={() => openPermissions(r)}
                                        />
                                    ))}
                                    {roleRows.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-10 text-center text-sm text-slate-500">No roles found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={roles?.meta} />
                        <Pagination links={roles?.meta?.links ?? roles?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={roleModalOpen} onClose={closeRoleModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editing ? 'Edit role' : 'Add role'}
                        subtitle={editing ? 'Update role name.' : 'Create a new system role.'}
                        onClose={closeRoleModal}
                        showRequiredNote
                    />

                    <form onSubmit={submitRole} className="mt-4 space-y-4">
                        <FloatingInput
                            id="role_name"
                            label="Role name"
                            required
                            value={roleData.name}
                            onChange={(e) => setRoleData('name', e.target.value)}
                            error={roleErrors.name}
                        />

                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={closeRoleModal}
                                disabled={roleProcessing}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton
                                disabled={roleProcessing}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                {roleProcessing && <Spinner size="sm" className="text-white" />}
                                <span>{editing ? 'Update' : 'Save'}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={permModalOpen} onClose={closePermModal} maxWidth="4xl">
                <div className="p-6">
                    <ModalHeader
                        title="Role permissions"
                        subtitle={permRole ? `Manage permissions for ${permRole.name}.` : 'Manage permissions.'}
                        onClose={closePermModal}
                    />

                    <form onSubmit={submitPermissions} className="mt-4 space-y-4">
                        {permErrors.permissions && (
                            <div className="text-sm font-semibold text-rose-600">{permErrors.permissions}</div>
                        )}

                        <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div className="text-sm font-semibold text-slate-900">Selected</div>
                                <div className="text-xs text-slate-600">
                                    Add: <span className="font-semibold text-emerald-700">{pendingAdded.length}</span>
                                    {' '}| Remove: <span className="font-semibold text-rose-700">{pendingRemoved.length}</span>
                                </div>
                            </div>
                        </div>

                        <div className="max-h-[65vh] overflow-y-auto pr-1">
                            <div className="grid gap-4 md:grid-cols-2">
                                {groupedPermissions.map((g) => (
                                    <div key={g.moduleName} className="rounded-xl border border-slate-200 bg-white p-4">
                                        <div className="text-sm font-semibold text-slate-900">{g.moduleName}</div>
                                        <div className="mt-3 space-y-2">
                                            {g.items.map((p) => {
                                                const checked = Array.isArray(permData.permissions) && permData.permissions.includes(p.name);
                                                return (
                                                    <label key={p.name} className="flex items-start gap-2 text-sm text-slate-700">
                                                        <input
                                                            type="checkbox"
                                                            checked={checked}
                                                            onChange={() => togglePermission(p.name)}
                                                            className="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                        />
                                                        <span className="flex-1">
                                                            <span className="font-medium">{p.display_name ?? p.name}</span>
                                                            <span className="ml-2 text-xs text-slate-500">({p.name})</span>
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton
                                type="button"
                                onClick={closePermModal}
                                disabled={permProcessing}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton
                                disabled={permProcessing}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                {permProcessing && <Spinner size="sm" className="text-white" />}
                                <span>Save permissions</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

function RoleRow({ role, striped = false, index, onEdit, onPermissions }) {
    const [confirmOpen, setConfirmOpen] = useState(false);

    const destroy = () => {
        router.delete(route('access-control.roles.destroy', role.id), { preserveScroll: true });
    };

    return (
        <>
            <tr className={`${striped ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                <td className="px-4 py-3 text-sm font-semibold text-slate-700">{index}</td>
                <td className="px-4 py-3">
                    <div className="text-sm font-semibold text-slate-900">{role.name}</div>
                </td>
                <td className="px-4 py-3 text-sm text-slate-700">{role.permissions_count ?? 0}</td>
                <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            onClick={onPermissions}
                            className="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Permissions
                        </button>
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
                    </div>
                </td>
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

function PaginationSummary({ meta }) {
    if (!meta) return null;

    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    return (
        <div className="text-sm text-slate-500">
            Showing <span className="font-semibold text-slate-700">{from}</span> to{' '}
            <span className="font-semibold text-slate-700">{to}</span> of{' '}
            <span className="font-semibold text-slate-700">{total}</span>
        </div>
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

                const content = prev ? (
                    <span className="inline-flex items-center gap-2">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Previous</span>
                    </span>
                ) : next ? (
                    <span className="inline-flex items-center gap-2">
                        <span>Next</span>
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                ) : (
                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                );

                if (!link.url) {
                    return (
                        <span
                            key={idx}
                            className={className}
                        >
                            {content}
                        </span>
                    );
                }

                return (
                    <Link
                        key={idx}
                        href={link.url}
                        preserveScroll
                        className={className}
                    >
                        {content}
                    </Link>
                );
            })}
        </div>
    );
}
