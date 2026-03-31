import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import Checkbox from '@/Components/Checkbox';
import { toTitleCase } from '@/lib/formatters';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function BanksIndex({ banks, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('finance.banks.create');
    const canUpdate = can('finance.banks.update');
    const canDelete = can('finance.banks.delete');

    const [q, setQ] = useState(filters?.q ?? '');
    const perPage = filters?.per_page ?? 15;
    const [open, setOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [deactivateOpen, setDeactivateOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [selected, setSelected] = useState(null);
    const [rows, setRows] = useState([{ name: '', is_active: true }]);

    const { setData, post, processing, errors, reset, clearErrors } = useForm({ items: rows });

    useEffect(() => {
        setData('items', rows);
    }, [rows]);

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('finance.banks.index'), { q: q || undefined, per_page: perPage }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        router.get(route('finance.banks.index'), { per_page: perPage }, { preserveState: true, replace: true });
    };

    const addRow = () => setRows((prev) => [...prev, { name: '', is_active: true }]);
    const removeRow = (idx) => setRows((prev) => {
        const next = prev.filter((_, i) => i !== idx);
        return next.length ? next : [{ name: '', is_active: true }];
    });
    const updateRow = (idx, key, value) => setRows((prev) => prev.map((r, i) => (i === idx ? { ...r, [key]: value } : r)));

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
        setRows([{ name: '', is_active: true }]);
    };

    const openEdit = (item) => {
        setSelected(item);
        setRows([{ uuid: item.uuid, name: toTitleCase(item.name ?? ''), is_active: !!item.is_active }]);
        setEditOpen(true);
    };

    const closeEdit = () => {
        setEditOpen(false);
        setSelected(null);
        reset();
        clearErrors();
        setRows([{ name: '', is_active: true }]);
    };

    const submit = (e, onSuccess) => {
        e.preventDefault();
        post(route('finance.banks.bulk'), { preserveScroll: true, onSuccess });
    };

    const tableRows = useMemo(() => banks?.data ?? [], [banks?.data]);

    return (
        <AuthenticatedLayout>
            <Head title="Banks" />
            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Banks</h1>
                        <p className="mt-1 text-sm text-slate-500">Manage bank institutions used by bank accounts.</p>
                    </div>
                    {canCreate && <PrimaryButton type="button" onClick={() => setOpen(true)} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700"><span className="text-lg font-bold leading-none">+</span><span>Banks</span></PrimaryButton>}
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div className="w-full sm:max-w-md">
                            <FloatingInput id="banks_q" label="Search (name)" value={q} onChange={(e) => setQ(e.target.value)} />
                        </div>
                        <div className="flex items-center gap-2">
                            <PrimaryButton type="submit" className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">Search</PrimaryButton>
                            <SecondaryButton type="button" onClick={clearSearch} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Clear</SecondaryButton>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th className="w-16">#</th>
                                        <th>Name</th>
                                        <th>Status</th>
                                        {(canUpdate || canDelete) && <th className="w-32">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((item, idx) => (
                                        <tr key={item.uuid} className="transition hover:bg-blue-50/40">
                                            <td className="px-4 py-3 text-sm text-slate-600">{(banks?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{toTitleCase(item.name)}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${item.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'}`}>{item.is_active ? 'Active' : 'Inactive'}</span>
                                            </td>
                                            {(canUpdate || canDelete) && (
                                                <td className="px-4 py-3 text-sm">
                                                    <div className="flex items-center gap-2">
                                                        {canUpdate && (
                                                            <button
                                                                type="button"
                                                                onClick={() => openEdit(item)}
                                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50"
                                                                title="Edit"
                                                            >
                                                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.5 1.5 0 112.121 2.122l-1.687 1.687M16.862 4.487L7.5 13.85l-.5 2.5 2.5-.5 9.362-9.363M16.862 4.487l2.121 2.121" />
                                                                </svg>
                                                            </button>
                                                        )}
                                                        {canDelete && (
                                                            <button
                                                                type="button"
                                                                onClick={() => { setSelected(item); setDeactivateOpen(true); }}
                                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100"
                                                                title="Deactivate"
                                                            >
                                                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v4" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 17h.01" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M10.29 3.86l-7.4 12.83A2 2 0 004.62 20h14.76a2 2 0 001.73-3.31l-7.4-12.83a2 2 0 00-3.42 0z" />
                                                                </svg>
                                                            </button>
                                                        )}
                                                        {canDelete && (
                                                            <button
                                                                type="button"
                                                                onClick={() => { setSelected(item); setDeleteOpen(true); }}
                                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100"
                                                                title="Delete"
                                                            >
                                                                <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 7h12" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M10 11v6" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M14 11v6" />
                                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M8 7l1 14h6l1-14" />
                                                                </svg>
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && <tr><td colSpan={(canUpdate || canDelete) ? 4 : 3} className="px-4 py-10 text-center text-sm text-slate-500">No banks found.</td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={banks?.meta} />
                        <Pagination links={banks?.meta?.links ?? banks?.links ?? []} />
                    </div>
                </section>
            </div>

            <BankModal open={open} close={close} rows={rows} updateRow={updateRow} removeRow={removeRow} addRow={addRow} submit={(e) => submit(e, close)} processing={processing} errors={errors} title="Add banks" submitLabel="Save banks" />
            <BankModal open={editOpen} close={closeEdit} rows={rows} updateRow={updateRow} removeRow={removeRow} addRow={addRow} submit={(e) => submit(e, closeEdit)} processing={processing} errors={errors} title="Update bank" submitLabel="Update bank" editing />

            <ConfirmModal title="Deactivate bank" subtitle="This will set the bank as inactive." open={deactivateOpen} close={() => { setDeactivateOpen(false); setSelected(null); }} action={() => selected?.uuid && router.patch(route('finance.banks.deactivate', selected.uuid), {}, { preserveScroll: true, onSuccess: () => { setDeactivateOpen(false); setSelected(null); } })} actionLabel="Deactivate" actionClass="bg-amber-600 hover:bg-amber-700">Are you sure you want to deactivate <span className="font-semibold">{toTitleCase(selected?.name ?? '')}</span>?</ConfirmModal>
            <ConfirmModal title="Delete bank" subtitle="This will permanently delete the bank if allowed by business rules." open={deleteOpen} close={() => { setDeleteOpen(false); setSelected(null); }} action={() => selected?.uuid && router.delete(route('finance.banks.destroy', selected.uuid), { preserveScroll: true, onSuccess: () => { setDeleteOpen(false); setSelected(null); } })} actionLabel="Delete" actionClass="bg-rose-600 hover:bg-rose-700">Are you sure you want to delete <span className="font-semibold">{toTitleCase(selected?.name ?? '')}</span>?</ConfirmModal>
        </AuthenticatedLayout>
    );
}

function BankModal({ open, close, rows, updateRow, removeRow, addRow, submit, processing, errors, title, submitLabel, editing = false }) {
    return (
        <Modal show={open} onClose={close} maxWidth="2xl">
            <div className="p-6">
                <ModalHeader title={title} subtitle="Add one or many banks at once." onClose={close} showRequiredNote />
                <form onSubmit={submit} className="mt-4 space-y-4">
                    {rows.map((row, idx) => (
                        <div key={idx} className="rounded-xl border border-slate-200 p-4">
                            <div className="grid gap-4 md:grid-cols-2">
                                <FloatingInput id={`bank_name_${idx}`} label="Bank name" required value={row.name} onChange={(e) => updateRow(idx, 'name', e.target.value)} error={errors[`items.${idx}.name`]} />
                                <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-700">
                                    <Checkbox checked={!!row.is_active} onChange={(e) => updateRow(idx, 'is_active', e.target.checked)} />
                                    <span>Active</span>
                                </label>
                            </div>
                            {!editing && <div className="mt-4 flex items-center justify-between"><button type="button" onClick={() => removeRow(idx)} className="text-sm font-semibold text-rose-700 hover:text-rose-800">Remove</button></div>}
                        </div>
                    ))}
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        {!editing && <SecondaryButton type="button" onClick={addRow} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Add another</SecondaryButton>}
                        <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">{processing && <Spinner size="sm" className="text-white" />}<span>{submitLabel}</span></PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function ConfirmModal({ title, subtitle, open, close, action, actionLabel, actionClass, children }) {
    return <Modal show={open} onClose={close} maxWidth="md"><div className="p-6"><ModalHeader title={title} subtitle={subtitle} onClose={close} /><div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{children}</div><div className="mt-5 flex items-center justify-end gap-2"><SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton><PrimaryButton type="button" onClick={action} className={`h-11 rounded-lg text-sm font-semibold text-white ${actionClass}`}>{actionLabel}</PrimaryButton></div></div></Modal>;
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) return null;
    return <nav className="flex flex-wrap items-center justify-end gap-1">{links.map((link, idx) => <button key={idx} type="button" disabled={!link.url} onClick={() => link.url && router.visit(link.url, { preserveState: true, replace: true })} className={`min-w-[2.25rem] rounded-lg px-3 py-2 text-sm font-semibold transition ${link.active ? 'bg-blue-600 text-white' : link.url ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' : 'bg-slate-100 text-slate-400'}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>;
}

function PaginationSummary({ meta }) {
    if (!meta || typeof meta !== 'object') return null;
    return <div className="text-sm text-slate-600">Showing <span className="font-semibold text-slate-900">{meta.from ?? 0}</span> to <span className="font-semibold text-slate-900">{meta.to ?? 0}</span> of <span className="font-semibold text-slate-900">{meta.total ?? 0}</span></div>;
}
