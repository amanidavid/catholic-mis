import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function AccessControlUsersIndex({ users, roles, permissions, filters }) {
    const rows = users?.data ?? [];

    const [q, setQ] = useState(filters?.q ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('access-control.users.index'), { q: q || undefined }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        router.get(route('access-control.users.index'), {}, { preserveState: true, replace: true });
    };

    const rolesList = useMemo(() => (Array.isArray(roles) ? roles : []), [roles]);
    const permissionsList = useMemo(() => (Array.isArray(permissions) ? permissions : []), [permissions]);

    const [roleModalOpen, setRoleModalOpen] = useState(false);
    const [permModalOpen, setPermModalOpen] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

    const closeRoleModal = () => {
        setRoleModalOpen(false);
        setSelectedUser(null);
        clearRoleErrors();
        resetRoleForm();
    };

    const closePermModal = () => {
        setPermModalOpen(false);
        setSelectedUser(null);
        clearPermErrors();
        resetPermForm();
    };

    const {
        data: roleData,
        setData: setRoleData,
        post: postRoles,
        processing: roleProcessing,
        errors: roleErrors,
        clearErrors: clearRoleErrors,
        reset: resetRoleForm,
    } = useForm({ roles: [] });

    const {
        data: permData,
        setData: setPermData,
        post: postPerms,
        processing: permProcessing,
        errors: permErrors,
        clearErrors: clearPermErrors,
        reset: resetPermForm,
    } = useForm({ permissions: [] });

    const openRoles = (u) => {
        setSelectedUser(u);
        setRoleModalOpen(true);
    };

    const openDirectPermissions = (u) => {
        setSelectedUser(u);
        setPermModalOpen(true);
    };

    const originalRoles = useMemo(() => (Array.isArray(selectedUser?.role_names) ? selectedUser.role_names : []), [selectedUser?.id]);
    const selectedRoles = useMemo(() => (Array.isArray(roleData.roles) ? roleData.roles : []), [roleData.roles]);
    const rolesAdded = useMemo(() => selectedRoles.filter((r) => !originalRoles.includes(r)), [selectedRoles, originalRoles]);
    const rolesRemoved = useMemo(() => originalRoles.filter((r) => !selectedRoles.includes(r)), [selectedRoles, originalRoles]);

    const originalPerms = useMemo(() => (Array.isArray(selectedUser?.direct_permission_names) ? selectedUser.direct_permission_names : []), [selectedUser?.id]);
    const selectedPerms = useMemo(() => (Array.isArray(permData.permissions) ? permData.permissions : []), [permData.permissions]);
    const permsAdded = useMemo(() => selectedPerms.filter((p) => !originalPerms.includes(p)), [selectedPerms, originalPerms]);
    const permsRemoved = useMemo(() => originalPerms.filter((p) => !selectedPerms.includes(p)), [selectedPerms, originalPerms]);

    const toggleRole = (roleName) => {
        const current = Array.isArray(roleData.roles) ? roleData.roles : [];
        if (current.includes(roleName)) {
            setRoleData('roles', current.filter((r) => r !== roleName));
            return;
        }
        setRoleData('roles', [...current, roleName]);
    };

    const togglePermission = (permName) => {
        const current = Array.isArray(permData.permissions) ? permData.permissions : [];
        if (current.includes(permName)) {
            setPermData('permissions', current.filter((p) => p !== permName));
            return;
        }
        setPermData('permissions', [...current, permName]);
    };

    const submitRoles = (e) => {
        e.preventDefault();
        if (!selectedUser) return;

        postRoles(route('access-control.users.roles.sync', selectedUser.uuid), {
            preserveScroll: true,
            onSuccess: closeRoleModal,
        });
    };

    const submitDirectPermissions = (e) => {
        e.preventDefault();
        if (!selectedUser) return;

        postPerms(route('access-control.users.permissions.sync', selectedUser.uuid), {
            preserveScroll: true,
            onSuccess: closePermModal,
        });
    };

    const groupedPermissions = useMemo(() => {
        const groups = {};
        (permissionsList ?? []).forEach((p) => {
            const moduleName = p.module || 'Other';
            if (!groups[moduleName]) groups[moduleName] = [];
            groups[moduleName].push(p);
        });

        return Object.entries(groups)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([moduleName, items]) => ({
                moduleName,
                items: (items ?? []).slice().sort((x, y) => (x.display_name || x.name).localeCompare(y.display_name || y.name)),
            }));
    }, [permissionsList]);

    useEffect(() => {
        if (!roleModalOpen) return;
        clearRoleErrors();
        resetRoleForm();
        setRoleData('roles', Array.isArray(selectedUser?.role_names) ? selectedUser.role_names : []);
    }, [roleModalOpen]);

    useEffect(() => {
        if (!permModalOpen) return;
        clearPermErrors();
        resetPermForm();
        setPermData('permissions', Array.isArray(selectedUser?.direct_permission_names) ? selectedUser.direct_permission_names : []);
    }, [permModalOpen]);

    return (
        <AuthenticatedLayout header="Access Control">
            <Head title="Access Control - Users" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">Users</h1>
                    <p className="mt-1 text-sm text-slate-500">Assign roles to users and grant/revoke direct permissions.</p>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <FloatingInput
                            id="users_q"
                            label="Search user"
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
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">#</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">User</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Roles</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Direct permissions</th>
                                        <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {rows.map((u, idx) => (
                                        <tr key={u.id} className={`${idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-700">{(users?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3">
                                                <div className="text-sm font-semibold text-slate-900">{u.name}</div>
                                                <div className="text-xs text-slate-500">{u.email}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{Array.isArray(u.role_names) && u.role_names.length ? u.role_names.join(', ') : '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                {Array.isArray(u.direct_permission_names) && u.direct_permission_names.length
                                                    ? `${u.direct_permission_names.length} (effective: ${u.effective_permissions_count ?? u.direct_permission_names.length})`
                                                    : `- (effective: ${u.effective_permissions_count ?? 0})`}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        type="button"
                                                        onClick={() => openRoles(u)}
                                                        className="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                                    >
                                                        Roles
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => openDirectPermissions(u)}
                                                        className="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                                    >
                                                        Direct permissions
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {rows.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-10 text-center text-sm text-slate-500">No users found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="text-sm text-slate-500">
                            Showing <span className="font-semibold text-slate-700">{users?.meta?.from ?? 0}</span> to{' '}
                            <span className="font-semibold text-slate-700">{users?.meta?.to ?? 0}</span> of{' '}
                            <span className="font-semibold text-slate-700">{users?.meta?.total ?? 0}</span>
                        </div>
                    </div>
                </section>
            </div>

            <Modal show={roleModalOpen} onClose={closeRoleModal} maxWidth="3xl">
                <div className="p-6">
                    <ModalHeader
                        title="User roles"
                        subtitle={selectedUser ? `Manage roles for ${selectedUser.email}.` : 'Manage roles.'}
                        onClose={closeRoleModal}
                    />

                    <form onSubmit={submitRoles} className="mt-4 space-y-4">
                        {roleErrors.roles && (
                            <div className="text-sm font-semibold text-rose-600">{roleErrors.roles}</div>
                        )}

                        <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div className="text-sm font-semibold text-slate-900">Selected</div>
                                <div className="text-xs text-slate-600">
                                    Add: <span className="font-semibold text-emerald-700">{rolesAdded.length}</span>
                                    {' '}| Remove: <span className="font-semibold text-rose-700">{rolesRemoved.length}</span>
                                </div>
                            </div>
                        </div>

                        <div className="max-h-[55vh] overflow-y-auto pr-1">
                            <div className="grid gap-3 sm:grid-cols-2">
                                {rolesList.map((r) => {
                                    const checked = selectedRoles.includes(r);
                                    return (
                                        <label key={r} className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 text-sm text-slate-700">
                                            <input
                                                type="checkbox"
                                                checked={checked}
                                                onChange={() => toggleRole(r)}
                                                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span className="font-medium">{r}</span>
                                        </label>
                                    );
                                })}
                                {rolesList.length === 0 && (
                                    <div className="text-sm text-slate-500">No roles available.</div>
                                )}
                            </div>
                        </div>

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
                                disabled={roleProcessing || (rolesAdded.length === 0 && rolesRemoved.length === 0)}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                {roleProcessing && <Spinner size="sm" className="text-white" />}
                                <span>Save roles</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={permModalOpen} onClose={closePermModal} maxWidth="5xl">
                <div className="p-6">
                    <ModalHeader
                        title="User direct permissions"
                        subtitle={selectedUser ? `Manage direct permissions for ${selectedUser.email}.` : 'Manage direct permissions.'}
                        onClose={closePermModal}
                    />

                    <form onSubmit={submitDirectPermissions} className="mt-4 space-y-4">
                        {permErrors.permissions && (
                            <div className="text-sm font-semibold text-rose-600">{permErrors.permissions}</div>
                        )}

                        <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div className="text-sm font-semibold text-slate-900">Selected</div>
                                <div className="text-xs text-slate-600">
                                    Add: <span className="font-semibold text-emerald-700">{permsAdded.length}</span>
                                    {' '}| Remove: <span className="font-semibold text-rose-700">{permsRemoved.length}</span>
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
                                                const checked = selectedPerms.includes(p.name);
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
                                disabled={permProcessing || (permsAdded.length === 0 && permsRemoved.length === 0)}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                {permProcessing && <Spinner size="sm" className="text-white" />}
                                <span>Save direct permissions</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
