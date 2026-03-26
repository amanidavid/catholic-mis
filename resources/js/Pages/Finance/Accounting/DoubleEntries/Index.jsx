import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { toTitleCase } from '@/lib/formatters';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function DoubleEntriesIndex({ items, ledgers, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('finance.double-entries.create');
    const canDelete = can('finance.double-entries.delete');

    const [q, setQ] = useState(filters?.q ?? '');
    const perPage = filters?.per_page ?? 15;

    const apply = (e) => {
        e.preventDefault();
        router.get(route('finance.double-entries.index'), { q: q || undefined, per_page: perPage }, { preserveState: true, replace: true });
    };

    const clear = () => {
        setQ('');
        router.get(route('finance.double-entries.index'), { per_page: perPage }, { preserveState: true, replace: true });
    };

    const tableRows = useMemo(() => items?.data ?? [], [items?.data]);

    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors, delete: destroy } = useForm({
        description: '',
        ledger_uuid: '',
        debit_ledger_uuid: '',
        credit_ledger_uuid: '',
    });

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('finance.double-entries.store'), { preserveScroll: true, onSuccess: close });
    };

    const remove = (row) => {
        if (!row?.uuid) return;
        destroy(route('finance.double-entries.destroy', row.uuid), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Double Entries" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Double Entries</h1>
                        <p className="mt-1 text-sm text-slate-500">Configure debit/credit ledger mappings for automated journal creation.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {canCreate && (
                            <PrimaryButton
                                type="button"
                                onClick={() => setOpen(true)}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                <span className="text-xl font-bold leading-none">+</span>
                                <span>Double Entry</span>
                            </PrimaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={apply} className="grid gap-3 lg:grid-cols-12 lg:items-end">
                        <FloatingInput id="de_q" label="Search (description)" value={q} onChange={(e) => setQ(e.target.value)} className="lg:col-span-9" />
                        <div className="flex items-center gap-2 lg:col-span-3 lg:justify-end">
                            <button type="submit" className="h-11 rounded-lg px-4 text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">Search</button>
                            <button type="button" onClick={clear} className="h-11 rounded-lg px-4 text-sm font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">Clear</button>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th className="w-16">#</th>
                                        <th>Description</th>
                                        <th>Lookup ledger</th>
                                        <th>Debit ledger</th>
                                        <th>Credit ledger</th>
                                        {canDelete && <th className="w-20">Action</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((r, idx) => (
                                        <tr key={r.uuid} className="hover:bg-indigo-50/40 transition">
                                            <td className="px-4 py-3 text-sm text-slate-600">{(items?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{toTitleCase(r.description ?? '')}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                {r.ledger_account_code ? `${r.ledger_account_code} - ${toTitleCase(r.ledger_name ?? '')}` : toTitleCase(r.ledger_name ?? '')}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                {r.debit_ledger_account_code ? `${r.debit_ledger_account_code} - ${toTitleCase(r.debit_ledger_name ?? '')}` : toTitleCase(r.debit_ledger_name ?? '')}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                {r.credit_ledger_account_code ? `${r.credit_ledger_account_code} - ${toTitleCase(r.credit_ledger_name ?? '')}` : toTitleCase(r.credit_ledger_name ?? '')}
                                            </td>
                                            {canDelete && (
                                                <td className="px-4 py-3 text-sm">
                                                    <button
                                                        type="button"
                                                        onClick={() => remove(r)}
                                                        className="inline-flex h-9 w-9 items-center justify-center rounded-lg ring-1 ring-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
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
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && (
                                        <tr>
                                            <td colSpan={canDelete ? 6 : 5} className="px-4 py-10 text-center text-sm text-slate-500">No mappings found.</td>
                                        </tr>
                                    )}
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

            <Modal show={open} onClose={close} maxWidth="md">
                <div className="p-6">
                    <ModalHeader title="New double entry mapping" subtitle="Pick lookup ledger (optional), and set debit & credit ledgers." onClose={close} showRequiredNote />

                    <form onSubmit={submit} className="mt-4 space-y-4">
                        {Object.keys(errors ?? {}).length > 0 && (
                            <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Fix the highlighted errors below.</div>
                        )}

                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput
                                id="de_desc"
                                label="Description (optional)"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                error={errors.description}
                            />
                            <FloatingSelect
                                id="de_lookup_ledger"
                                label="Lookup ledger (optional)"
                                value={data.ledger_uuid}
                                onChange={(e) => setData('ledger_uuid', e.target.value)}
                                error={errors.ledger_uuid}
                            >
                                <option value="">None</option>
                                {(ledgers ?? []).map((l) => (
                                    <option key={l.uuid} value={l.uuid}>
                                        {l.account_code ? `${l.account_code} - ${toTitleCase(l.name)}` : toTitleCase(l.name)}
                                    </option>
                                ))}
                            </FloatingSelect>
                            <FloatingSelect
                                id="de_debit"
                                label="Debit ledger"
                                value={data.debit_ledger_uuid}
                                onChange={(e) => setData('debit_ledger_uuid', e.target.value)}
                                error={errors.debit_ledger_uuid}
                                required
                            >
                                <option value="">Select debit ledger</option>
                                {(ledgers ?? []).map((l) => (
                                    <option key={l.uuid} value={l.uuid}>
                                        {l.account_code ? `${l.account_code} - ${toTitleCase(l.name)}` : toTitleCase(l.name)}
                                    </option>
                                ))}
                            </FloatingSelect>
                            <FloatingSelect
                                id="de_credit"
                                label="Credit ledger"
                                value={data.credit_ledger_uuid}
                                onChange={(e) => setData('credit_ledger_uuid', e.target.value)}
                                error={errors.credit_ledger_uuid}
                                required
                            >
                                <option value="">Select credit ledger</option>
                                {(ledgers ?? []).map((l) => (
                                    <option key={l.uuid} value={l.uuid}>
                                        {l.account_code ? `${l.account_code} - ${toTitleCase(l.name)}` : toTitleCase(l.name)}
                                    </option>
                                ))}
                            </FloatingSelect>
                        </div>

                        <div className="flex flex-wrap items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700">
                                {processing && <Spinner size="sm" className="text-white" />}
                                <span>Save</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) return null;

    return (
        <nav className="flex flex-wrap items-center justify-end gap-1">
            {links.map((link, idx) => (
                <button
                    key={idx}
                    type="button"
                    disabled={!link.url}
                    onClick={() => link.url && router.visit(link.url, { preserveState: true, replace: true })}
                    className={`min-w-[2.25rem] rounded-lg px-3 py-2 text-sm font-semibold transition ${link.active
                        ? 'bg-blue-600 text-white'
                        : link.url
                            ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'
                            : 'bg-slate-100 text-slate-400'
                        }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </nav>
    );
}

function PaginationSummary({ meta }) {
    if (!meta || typeof meta !== 'object') return null;

    return (
        <div className="text-sm text-slate-600">
            Showing <span className="font-semibold text-slate-900">{meta.from ?? 0}</span> to{' '}
            <span className="font-semibold text-slate-900">{meta.to ?? 0}</span> of{' '}
            <span className="font-semibold text-slate-900">{meta.total ?? 0}</span>
        </div>
    );
}
