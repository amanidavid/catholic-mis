import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import Checkbox from '@/Components/Checkbox';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function ContributionRulesIndex({ items, catalogs, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('contributions.rules.create');
    const canUpdate = can('contributions.rules.update');
    const canDelete = can('contributions.rules.delete');

    const [q, setQ] = useState(filters?.q ?? '');

    const [isActive, setIsActive] = useState(filters?.is_active ?? '');
    const [open, setOpen] = useState(false);
    const [editOpen, setEditOpen] = useState(false);
    const [deleteOpen, setDeleteOpen] = useState(false);
    const [selected, setSelected] = useState(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        contribution_catalog_uuid: '',
        amount: '',
        currency_code: 'TZS',
        is_required: true,
        allow_partial_payment: false,
        waiver_allowed: false,
        completion_policy: 'advisory',
        effective_from: '',
        effective_to: '',
        sort_order: 0,
        is_active: true,
    });

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('finance.contribution.rules.index'), { q: q || undefined, is_active: isActive || undefined }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');

        setIsActive('');
        router.get(route('finance.contribution.rules.index'), {}, { preserveState: true, replace: true });
    };

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    const openEdit = (item) => {
        setSelected(item);
        setData({
            contribution_catalog_uuid: item.catalog_uuid ?? '',
            amount: item.amount ?? '',
            currency_code: item.currency_code ?? 'TZS',
            is_required: !!item.is_required,
            allow_partial_payment: !!item.allow_partial_payment,
            waiver_allowed: !!item.waiver_allowed,
            completion_policy: item.completion_policy ?? 'advisory',
            effective_from: item.effective_from ?? '',
            effective_to: item.effective_to ?? '',
            sort_order: item.sort_order ?? 0,
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
        post(route('finance.contribution.rules.store'), { preserveScroll: true, onSuccess });
    };

    const update = (e, onSuccess) => {
        e.preventDefault();
        router.patch(route('finance.contribution.rules.update', selected?.uuid), data, { preserveScroll: true, onSuccess });
    };

    const handleDelete = () => {
        if (selected?.uuid) {
            router.delete(route('finance.contribution.rules.destroy', selected.uuid), { preserveScroll: true, onSuccess: () => { closeDelete(); } });
        }
    };

    const tableRows = items?.data ?? [];

    return (
        <AuthenticatedLayout>
            <Head title="Contribution Rules" />
            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Contribution Rules</h1>
                        <p className="mt-1 text-sm text-slate-500">Configure when and how contributions apply to services.</p>
                    </div>
                    {canCreate && <PrimaryButton type="button" onClick={() => setOpen(true)} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700"><span className="text-lg font-bold leading-none">+</span><span>Rule</span></PrimaryButton>}
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div className="w-full sm:max-w-md">
                            <FloatingInput id="rules_q" label="Search (catalog name or code)" value={q} onChange={(e) => setQ(e.target.value)} />
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
                                        <th>Catalog</th>
                                        <th>Amount</th>
                                        <th>Required</th>
                                        <th>Partial</th>
                                        <th>Status</th>
                                        {(canUpdate || canDelete) && <th className="w-32">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((item, idx) => (
                                        <tr key={item.uuid} className="transition hover:bg-blue-50/40">
                                            <td className="px-4 py-3 text-sm text-slate-600">{(items?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="font-semibold text-slate-900">{item.catalog_name}</div>
                                                <div className="text-xs text-slate-500">{item.catalog_code}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{item.amount} {item.currency_code}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${item.is_required ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' : 'bg-slate-50 text-slate-700 ring-1 ring-slate-200'}`}>{item.is_required ? 'Yes' : 'No'}</span>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${item.allow_partial_payment ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-50 text-slate-700 ring-1 ring-slate-200'}`}>{item.allow_partial_payment ? 'Yes' : 'No'}</span>
                                            </td>
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
                                    {tableRows.length === 0 && <tr><td colSpan={(canUpdate || canDelete) ? 7 : 6} className="px-4 py-10 text-center text-sm text-slate-500">No contribution rules found.</td></tr>}
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

            <RuleModal open={open} close={close} data={data} setData={setData} catalogs={catalogs} submit={submit} processing={processing} errors={errors} title="Add contribution rule" submitLabel="Save rule" />
            <RuleModal open={editOpen} close={closeEdit} data={data} setData={setData} catalogs={catalogs} submit={update} processing={processing} errors={errors} title="Update contribution rule" submitLabel="Update rule" editing />
            <DeleteModal open={deleteOpen} close={closeDelete} item={selected} onDelete={handleDelete} />
        </AuthenticatedLayout>
    );
}

function RuleModal({ open, close, data, setData, catalogs, submit, processing, errors, title, submitLabel, editing = false }) {
    return (
        <Modal show={open} onClose={close} maxWidth="md">
            <div className="p-4 sm:p-6">
                <ModalHeader title={title} subtitle={editing ? "Update contribution rule details." : "Add a new contribution rule."} onClose={close} showRequiredNote />
                <form onSubmit={submit} className="mt-4 space-y-4">
                    <div className="grid gap-3 sm:gap-4 md:grid-cols-2">
                        <FloatingSelect id="rule_catalog" label="Contribution Catalog" required value={data.contribution_catalog_uuid} onChange={(e) => setData('contribution_catalog_uuid', e.target.value)} error={errors.contribution_catalog_uuid}>
                            <option value="">Select catalog</option>
                            {catalogs.map((c) => <option key={c.uuid} value={c.uuid}>{c.name} ({c.code})</option>)}
                        </FloatingSelect>
                        <FloatingInput id="rule_amount" label="Amount" required type="number" step="0.01" min="0" value={data.amount} onChange={(e) => setData('amount', e.target.value)} error={errors.amount} />
                        <FloatingInput id="rule_currency" label="Currency Code" value={data.currency_code} onChange={(e) => setData('currency_code', e.target.value)} error={errors.currency_code} />
                        <FloatingInput id="rule_sort" label="Sort Order" type="number" min="0" value={data.sort_order} onChange={(e) => setData('sort_order', e.target.value)} error={errors.sort_order} />
                        <FloatingInput id="rule_effective_from" label="Effective From" type="date" value={data.effective_from} onChange={(e) => setData('effective_from', e.target.value)} error={errors.effective_from} />
                        <FloatingInput id="rule_effective_to" label="Effective To" type="date" value={data.effective_to} onChange={(e) => setData('effective_to', e.target.value)} error={errors.effective_to} />
                    </div>
                    <div className="grid gap-3 sm:gap-4 md:grid-cols-3">
                        <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-700">
                            <Checkbox checked={!!data.is_required} onChange={(e) => setData('is_required', e.target.checked)} />
                            <span>Required</span>
                        </label>
                        <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-700">
                            <Checkbox checked={!!data.allow_partial_payment} onChange={(e) => setData('allow_partial_payment', e.target.checked)} />
                            <span>Allow Partial Payment</span>
                        </label>
                        <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-700" title="If enabled, staff can mark this payment request as waived (forgiveness) without payment when appropriate.">
                            <Checkbox checked={!!data.waiver_allowed} onChange={(e) => setData('waiver_allowed', e.target.checked)} />
                            <span>Waiver Allowed</span>
                        </label>
                    </div>
                    <label className="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-700">
                        <Checkbox checked={!!data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                        <span>Active</span>
                    </label>
                    <div className="sticky bottom-0 mt-2 flex items-center justify-end gap-2">
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
                <ModalHeader title="Delete contribution rule" subtitle="This will permanently delete the contribution rule." onClose={close} />
                <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    Are you sure you want to delete the rule for <span className="font-semibold">{item?.catalog_name ?? ''}</span>?
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
