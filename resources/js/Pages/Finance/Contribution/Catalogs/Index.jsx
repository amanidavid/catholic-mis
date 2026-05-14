import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import Checkbox from '@/Components/Checkbox';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function ContributionCatalogsIndex({ items, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('contributions.catalogs.create');
    const canUpdate = can('contributions.catalogs.update');
    const canDelete = can('contributions.catalogs.delete');

    const [q, setQ] = useState(filters?.q ?? '');
    const [isActive, setIsActive] = useState(filters?.is_active ?? '');
    const [open, setOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [selected, setSelected] = useState(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        code: '',
        description: '',
        is_active: true,
    });

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('finance.contribution.catalogs.index'), { q: q || undefined, is_active: isActive || undefined }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        setIsActive('');
        router.get(route('finance.contribution.catalogs.index'), {}, { preserveState: true, replace: true });
    };

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    const openEdit = (item) => {
        setSelected(item);
        setData({
            name: item.name ?? '',
            code: item.code ?? '',
            description: item.description ?? '',
            is_active: !!item.is_active,
        });
        setEditOpen(true);
    };

    const closeEdit = () => {
        setEditOpen(false);
        setSelected(null);
        reset();
        clearErrors();
    };

    const closeDelete = () => {
        setDeleteOpen(false);
        setSelected(null);
    };

    const submit = (e, onSuccess) => {
        e.preventDefault();
        post(route('finance.contribution.catalogs.store'), { preserveScroll: true, onSuccess });
    };

    const update = (e, onSuccess) => {
        e.preventDefault();
        router.patch(route('finance.contribution.catalogs.update', selected?.uuid), data, { preserveScroll: true, onSuccess });
    };

    const handleDelete = () => {
        if (selected?.uuid) {
            router.delete(route('finance.contribution.catalogs.destroy', selected.uuid), { preserveScroll: true, onSuccess: () => { closeDelete(); } });
        }
    };

    const tableRows = items?.data ?? [];

    return (
        <AuthenticatedLayout>
            <Head title="Contribution Catalogs" />
            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Contribution Catalogs</h1>
                        <p className="mt-1 text-sm text-slate-500">Manage contribution types available in the parish.</p>
                    </div>
                    {canCreate && <PrimaryButton type="button" onClick={() => setOpen(true)} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700"><span className="text-lg font-bold leading-none">+</span><span>Catalog</span></PrimaryButton>}
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div className="w-full sm:max-w-md">
                            <FloatingInput id="catalogs_q" label="Search (name or code)" value={q} onChange={(e) => setQ(e.target.value)} />
                        </div>
                        <div className="flex items-center gap-2">
                            <select value={isActive} onChange={(e) => setIsActive(e.target.value)} className="h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
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
                                        <th>Code</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        {(canUpdate || canDelete) && <th className="w-32">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((item, idx) => (
                                        <tr key={item.uuid} className="transition hover:bg-blue-50/40">
                                            <td className="px-4 py-3 text-sm text-slate-600">{(items?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{item.name}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{item.code}</td>
                                            <td className="px-4 py-3 text-sm text-slate-600">{item.description ?? '-'}</td>
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
                                    {tableRows.length === 0 && <tr><td colSpan={(canUpdate || canDelete) ? 6 : 5} className="px-4 py-10 text-center text-sm text-slate-500">No contribution catalogs found.</td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={items?.meta} />
                        <Pagination links={items?.meta?.links ?? items?.links ?? []} />
                    </div>
                </section>
            </div>

            <CatalogModal open={open} close={close} data={data} setData={setData} submit={submit} processing={processing} errors={errors} title="Add contribution catalog" submitLabel="Save catalog" />
            <CatalogModal open={editOpen} close={closeEdit} data={data} setData={setData} submit={update} processing={processing} errors={errors} title="Update contribution catalog" submitLabel="Update catalog" editing />
            <DeleteModal open={deleteOpen} close={closeDelete} item={selected} onDelete={handleDelete} />
        </AuthenticatedLayout>
    );
}

function CatalogModal({ open, close, data, setData, submit, processing, errors, title, submitLabel, editing = false }) {
    return (
        <Modal show={open} onClose={close} maxWidth="sm">
            <div className="p-6">
                <ModalHeader title={title} subtitle={editing ? "Update contribution catalog details." : "Add a new contribution catalog."} onClose={close} showRequiredNote />
                <form onSubmit={submit} className="mt-4 space-y-4">
                    <FloatingInput id="catalog_name" label="Name" required value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} />
                    <FloatingInput id="catalog_code" label="Code" required value={data.code} onChange={(e) => setData('code', e.target.value)} error={errors.code} />
                    <FloatingInput id="catalog_description" label="Description" value={data.description} onChange={(e) => setData('description', e.target.value)} error={errors.description} />
                    <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-700">
                        <Checkbox checked={!!data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                        <span>Active</span>
                    </label>
                    <div className="flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                        <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">{processing && <Spinner size="sm" className="text-white" />}<span>{submitLabel}</span></PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function DeleteModal({ open, close, item, onDelete }) {
    return (
        <Modal show={open} onClose={close} maxWidth="md">
            <div className="p-6">
                <ModalHeader title="Delete contribution catalog" subtitle="This will permanently delete the contribution catalog." onClose={close} />
                <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    Are you sure you want to delete <span className="font-semibold">{item?.name ?? ''}</span>?
                </div>
                <div className="mt-5 flex items-center justify-end gap-2">
                    <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                    <PrimaryButton type="button" onClick={onDelete} className="h-11 rounded-lg bg-rose-600 text-sm font-semibold text-white hover:bg-rose-700">Delete</PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) return null;
    return <nav className="flex flex-wrap items-center justify-end gap-1">{links.map((link, idx) => <button key={idx} type="button" disabled={!link.url} onClick={() => link.url && router.visit(link.url, { preserveState: true, replace: true })} className={`min-w-[2.25rem] rounded-lg px-3 py-2 text-sm font-semibold transition ${link.active ? 'bg-blue-600 text-white' : link.url ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' : 'bg-slate-100 text-slate-400'}`} dangerouslySetInnerHTML={{ __html: link.label }} />)}</nav>;
}

function PaginationSummary({ meta }) {
    if (!meta || typeof meta !== 'object') return null;
    return <div className="text-sm text-slate-600">Showing <span className="font-semibold text-slate-900">{meta.from ?? 0}</span> to <span className="font-semibold text-slate-900">{meta.to ?? 0}</span> of <span className="font-semibold text-slate-900">{meta.total ?? 0}</span></div>;
}
