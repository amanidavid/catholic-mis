import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function ParishStaffPositionsIndex({ positions }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canView = useMemo(() => Array.isArray(permissions) && permissions.includes('parish-staff-positions.view'), [permissions]);
    const canCreate = useMemo(() => Array.isArray(permissions) && permissions.includes('parish-staff-positions.create'), [permissions]);
    const canUpdate = useMemo(() => Array.isArray(permissions) && permissions.includes('parish-staff-positions.update'), [permissions]);
    const canDelete = useMemo(() => Array.isArray(permissions) && permissions.includes('parish-staff-positions.delete'), [permissions]);

    const dataList = Array.isArray(positions) ? positions : (positions?.data ?? []);

    const [modalOpen, setModalOpen] = useState(false);
    const [editing, setEditing] = useState(null);

    const closeModal = () => {
        setModalOpen(false);
        setEditing(null);
        clearErrors();
    };

    const openAdd = () => {
        setEditing(null);
        setModalOpen(true);
    };

    const openEdit = (p) => {
        setEditing(p);
        setModalOpen(true);
    };

    const { data, setData, post, patch, delete: destroy, processing, errors, clearErrors, reset } = useForm({
        name: '',
        is_active: true,
    });

    useEffect(() => {
        if (!modalOpen) return;

        clearErrors();

        if (editing) {
            setData({
                name: editing?.name ?? '',
                is_active: editing?.is_active ?? true,
            });
            return;
        }

        reset();
        setData({ name: '', is_active: true });
    }, [modalOpen, editing?.uuid]);

    const submit = (e) => {
        e.preventDefault();

        if (editing) {
            patch(route('parish-staff-positions.update', editing.uuid), {
                preserveScroll: true,
                onSuccess: closeModal,
            });
            return;
        }

        post(route('parish-staff-positions.store'), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
    };

    const [confirm, setConfirm] = useState({ open: false, position: null });

    const requestDelete = (p) => {
        setConfirm({ open: true, position: p });
    };

    const confirmDelete = () => {
        const p = confirm.position;
        if (!p?.uuid) {
            setConfirm({ open: false, position: null });
            return;
        }

        destroy(route('parish-staff-positions.destroy', p.uuid), {
            preserveScroll: true,
            onSuccess: () => setConfirm({ open: false, position: null }),
            onFinish: () => setConfirm({ open: false, position: null }),
        });
    };

    return (
        <AuthenticatedLayout header="Staff Positions">
            <Head title="Staff Positions" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Staff Positions</h1>
                        <p className="mt-1 text-sm text-slate-500">Positions are titles only and do not grant system access.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={openAdd}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                <span className="text-lg font-bold leading-none">+</span>
                                <span>Position</span>
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    {!canView ? (
                        <div className="text-sm font-semibold text-rose-600">You do not have permission to view staff positions.</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                                <table className="mis-table divide-y divide-slate-200">
                                    <thead>
                                        <tr>
                                            <th className="w-16">#</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            {(canUpdate || canDelete) && (
                                                <th className="text-right">Actions</th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {(dataList ?? []).map((p, idx) => (
                                            <tr key={p.uuid} className={idx % 2 === 1 ? 'bg-slate-50/40' : ''}>
                                                <td className="px-4 py-3 text-sm text-slate-600">{idx + 1}</td>
                                                <td className="px-4 py-3 text-sm font-semibold text-slate-900">{p.name}</td>
                                                <td className="px-4 py-3 text-sm">
                                                    <span className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ${p.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'}`}>
                                                        {p.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                {(canUpdate || canDelete) && (
                                                    <td className="px-4 py-3 text-right text-sm">
                                                        <div className="flex justify-end gap-2">
                                                            {canUpdate && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => openEdit(p)}
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
                                                                    onClick={() => requestDelete(p)}
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
                                        ))}

                                        {(dataList ?? []).length === 0 && (
                                            <tr>
                                                <td colSpan={(canUpdate || canDelete) ? 4 : 3} className="px-4 py-10 text-center text-sm text-slate-500">
                                                    No positions found.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </section>
            </div>

            <Modal show={modalOpen} onClose={closeModal} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader
                        title={editing ? 'Edit position' : 'Add position'}
                        subtitle={editing ? 'Update position details.' : 'Create a new staff position (no system access granted).'}
                        onClose={closeModal}
                        showRequiredNote
                    />

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="staff_position_name"
                                label="Position name"
                                required
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                className="md:col-span-2"
                            />

                            {editing && (
                                <FloatingSelect
                                    id="staff_position_is_active"
                                    label="Active"
                                    value={data.is_active ? '1' : '0'}
                                    onChange={(e) => setData('is_active', e.target.value === '1')}
                                    error={errors.is_active}
                                    className="md:col-span-2"
                                >
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </FloatingSelect>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center justify-end gap-2">
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
                    </form>
                </div>
            </Modal>

            <ConfirmDialog
                open={confirm.open}
                title="Delete position"
                message="This will permanently remove the position. If it has assignment history, deletion will be blocked."
                confirmText="Delete"
                onConfirm={confirmDelete}
                onClose={() => setConfirm({ open: false, position: null })}
            />
        </AuthenticatedLayout>
    );
}
