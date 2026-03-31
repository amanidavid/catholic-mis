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
import { useEffect, useMemo, useState } from 'react';

export default function BankTransactionsIndex({ items, accounts, ledgers, mappings, transaction_types, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('finance.bank-account-transactions.create');
    const canDelete = can('finance.bank-account-transactions.delete');

    const [q, setQ] = useState(filters?.q ?? '');
    const [bankAccountUuid, setBankAccountUuid] = useState(filters?.bank_account_uuid ?? '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');
    const perPage = filters?.per_page ?? 15;
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors, delete: destroy } = useForm({
        bank_account_uuid: '',
        transaction_date: '',
        transaction_type: '',
        direction: 'inflow',
        double_entry_uuid: '',
        debit_ledger_uuid: '',
        credit_ledger_uuid: '',
        amount: '',
        reference_no: '',
        description: '',
    });

    const selectedAccount = useMemo(
        () => (accounts ?? []).find((item) => item.uuid === data.bank_account_uuid) ?? null,
        [accounts, data.bank_account_uuid],
    );

    const selectedMapping = useMemo(() => {
        if (!selectedAccount?.ledger_uuid || !data.transaction_type) {
            return null;
        }

        return (mappings ?? []).find((item) => item.ledger_uuid === selectedAccount.ledger_uuid && item.transaction_type === data.transaction_type) ?? null;
    }, [mappings, selectedAccount, data.transaction_type]);

    const debitLedgerLabel = useMemo(
        () => ledgerLabel((ledgers ?? []).find((item) => item.uuid === data.debit_ledger_uuid)),
        [ledgers, data.debit_ledger_uuid],
    );

    const creditLedgerLabel = useMemo(
        () => ledgerLabel((ledgers ?? []).find((item) => item.uuid === data.credit_ledger_uuid)),
        [ledgers, data.credit_ledger_uuid],
    );

    const lookupLedgerLabel = useMemo(() => {
        if (!selectedAccount) {
            return '';
        }

        return selectedAccount.ledger_account_code
            ? `${selectedAccount.ledger_account_code} - ${toTitleCase(selectedAccount.ledger_name ?? '')}`
            : toTitleCase(selectedAccount.ledger_name ?? '');
    }, [selectedAccount]);

    useEffect(() => {
        if (!selectedAccount || !data.transaction_type) {
            setData((current) => ({
                ...current,
                double_entry_uuid: '',
                debit_ledger_uuid: '',
                credit_ledger_uuid: '',
            }));

            return;
        }

        if (selectedMapping) {
            setData((current) => ({
                ...current,
                double_entry_uuid: selectedMapping.uuid ?? '',
                debit_ledger_uuid: selectedMapping.debit_ledger_uuid ?? '',
                credit_ledger_uuid: selectedMapping.credit_ledger_uuid ?? '',
            }));

            return;
        }

        const bankLedgerUuid = selectedAccount.ledger_uuid ?? '';
        const nextDebit = data.direction === 'inflow' ? bankLedgerUuid : '';
        const nextCredit = data.direction === 'outflow' ? bankLedgerUuid : '';

        setData((current) => ({
            ...current,
            double_entry_uuid: '',
            debit_ledger_uuid: nextDebit,
            credit_ledger_uuid: nextCredit,
        }));
    }, [selectedAccount, selectedMapping, data.transaction_type, data.direction, setData]);

    const isManualOverride = Boolean(
        selectedMapping
            && (data.debit_ledger_uuid !== (selectedMapping.debit_ledger_uuid ?? '')
                || data.credit_ledger_uuid !== (selectedMapping.credit_ledger_uuid ?? '')),
    );

    const apply = (e) => {
        e.preventDefault();
        router.get(route('finance.bank-account-transactions.index'), { q: q || undefined, bank_account_uuid: bankAccountUuid || undefined, date_from: dateFrom || undefined, date_to: dateTo || undefined, per_page: perPage }, { preserveState: true, replace: true });
    };

    const clear = () => {
        setQ('');
        setBankAccountUuid('');
        setDateFrom('');
        setDateTo('');
        router.get(route('finance.bank-account-transactions.index'), { per_page: perPage }, { preserveState: true, replace: true });
    };

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('finance.bank-account-transactions.store'), { preserveScroll: true, onSuccess: close });
    };

    const remove = (row) => row?.uuid && destroy(route('finance.bank-account-transactions.destroy', row.uuid), { preserveScroll: true });
    const tableRows = useMemo(() => items?.data ?? [], [items?.data]);

    return (
        <AuthenticatedLayout>
            <Head title="Bank Transactions" />
            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Bank transactions</h1>
                        <p className="mt-1 text-sm text-slate-500">Track inflows and outflows for bank accounts.</p>
                    </div>
                    {canCreate && <PrimaryButton type="button" onClick={() => setOpen(true)} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700"><span className="text-xl font-bold leading-none">+</span><span>Transaction</span></PrimaryButton>}
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={apply} className="grid gap-3 lg:grid-cols-12 lg:items-end">
                        <FloatingInput id="bat_q" label="Search (reference / description / type)" value={q} onChange={(e) => setQ(e.target.value)} className="lg:col-span-4" />
                        <FloatingSelect id="bat_account" label="Bank account" value={bankAccountUuid} onChange={(e) => setBankAccountUuid(e.target.value)} className="lg:col-span-3">
                            <option value="">All accounts</option>
                            {(accounts ?? []).map((item) => <option key={item.uuid} value={item.uuid}>{toTitleCase(item.bank_name ?? '')} - {toTitleCase(item.account_name ?? '')} ({item.account_number_masked ?? '-'})</option>)}
                        </FloatingSelect>
                        <FloatingInput id="bat_from" label="Date from" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="lg:col-span-2" />
                        <FloatingInput id="bat_to" label="Date to" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="lg:col-span-2" />
                        <div className="flex items-center gap-2 lg:col-span-1 lg:justify-end">
                            <button type="submit" className="h-11 rounded-lg px-4 text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">Search</button>
                            <button type="button" onClick={clear} className="h-11 rounded-lg px-4 text-sm font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">Clear</button>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead><tr><th className="w-16">#</th><th>Date</th><th>Account</th><th>Type</th><th>Direction</th><th>Amount</th><th>Reference</th><th>Journal</th>{canDelete && <th className="w-20">Action</th>}</tr></thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((row, idx) => (
                                        <tr key={row.uuid} className="transition hover:bg-blue-50/40">
                                            <td className="px-4 py-3 text-sm text-slate-600">{(items?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.transaction_date}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(row.bank_name ?? '')} - {toTitleCase(row.bank_account_name ?? '')}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{transaction_types?.[row.transaction_type] ?? toTitleCase(String(row.transaction_type ?? '').replace(/-/g, ' '))}</td>
                                            <td className="px-4 py-3 text-sm"><span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${row.direction === 'inflow' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'}`}>{row.direction === 'inflow' ? 'Inflow' : 'Outflow'}</span></td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{row.amount_formatted}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.reference_no ?? '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                <div className="flex flex-col">
                                                    <span>{row.journal_no ?? '-'}</span>
                                                    {row.is_manual_override && <span className="text-xs text-amber-700">Manual override</span>}
                                                </div>
                                            </td>
                                            {canDelete && (
                                                <td className="px-4 py-3 text-sm">
                                                    <button
                                                        type="button"
                                                        onClick={() => remove(row)}
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
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && <tr><td colSpan={canDelete ? 9 : 8} className="px-4 py-10 text-center text-sm text-slate-500">No bank transactions found.</td></tr>}
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

            <Modal show={open} onClose={close} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader title="New bank transaction" subtitle="Record inflow or outflow, then review or override the debit and credit ledgers before posting." onClose={close} showRequiredNote />
                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingSelect id="bat_modal_account" label="Bank account" value={data.bank_account_uuid} onChange={(e) => setData('bank_account_uuid', e.target.value)} error={errors.bank_account_uuid} required>
                                <option value="">Select bank account</option>
                                {(accounts ?? []).map((item) => <option key={item.uuid} value={item.uuid}>{toTitleCase(item.bank_name ?? '')} - {toTitleCase(item.account_name ?? '')} ({item.account_number_masked ?? '-'})</option>)}
                            </FloatingSelect>
                            <FloatingInput id="bat_modal_date" label="Transaction date" type="date" max={new Date().toISOString().split('T')[0]} value={data.transaction_date} onChange={(e) => setData('transaction_date', e.target.value)} error={errors.transaction_date} required />
                            <FloatingSelect id="bat_modal_type" label="Transaction type" value={data.transaction_type} onChange={(e) => setData('transaction_type', e.target.value)} error={errors.transaction_type} required>
                                <option value="">Select transaction type</option>
                                {Object.entries(transaction_types ?? {}).map(([value, label]) => <option key={value} value={value}>{label}</option>)}
                            </FloatingSelect>
                            <FloatingSelect id="bat_modal_direction" label="Direction" value={data.direction} onChange={(e) => setData('direction', e.target.value)} error={errors.direction} required>
                                <option value="inflow">Inflow</option>
                                <option value="outflow">Outflow</option>
                            </FloatingSelect>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div className="mb-3 flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 className="text-sm font-semibold text-slate-900">Posting Rule</h3>
                                    <p className="mt-1 text-xs text-slate-500">The selected bank account provides the lookup ledger. Once transaction type is chosen, the configured debit and credit are auto-filled and can still be overridden.</p>
                                </div>
                                {selectedMapping && (
                                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${isManualOverride ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200'}`}>
                                        {isManualOverride ? 'Manual override' : 'Mapped rule loaded'}
                                    </span>
                                )}
                            </div>

                            {!selectedMapping && data.bank_account_uuid && data.transaction_type && (
                                <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                    No configured double-entry rule was found for this bank account and transaction type. You can still choose the debit and credit ledgers manually.
                                </div>
                            )}

                            <input type="hidden" value={data.double_entry_uuid} />

                            <div className="grid gap-4 md:grid-cols-3">
                                <FloatingInput
                                    id="bat_modal_lookup_ledger"
                                    label="Lookup ledger"
                                    value={lookupLedgerLabel}
                                    readOnly
                                />
                                <FloatingSelect
                                    id="bat_modal_debit_ledger"
                                    label="Debit ledger"
                                    value={data.debit_ledger_uuid}
                                    onChange={(e) => setData('debit_ledger_uuid', e.target.value)}
                                    error={errors.debit_ledger_uuid}
                                    required
                                >
                                    <option value="">Select debit ledger</option>
                                    {(ledgers ?? []).map((item) => (
                                        <option key={item.uuid} value={item.uuid}>
                                            {ledgerLabel(item)}
                                        </option>
                                    ))}
                                </FloatingSelect>
                                <FloatingSelect
                                    id="bat_modal_credit_ledger"
                                    label="Credit ledger"
                                    value={data.credit_ledger_uuid}
                                    onChange={(e) => setData('credit_ledger_uuid', e.target.value)}
                                    error={errors.credit_ledger_uuid}
                                    required
                                >
                                    <option value="">Select credit ledger</option>
                                    {(ledgers ?? []).map((item) => (
                                        <option key={item.uuid} value={item.uuid}>
                                            {ledgerLabel(item)}
                                        </option>
                                    ))}
                                </FloatingSelect>
                            </div>

                            {(debitLedgerLabel || creditLedgerLabel) && (
                                <div className="mt-4 grid gap-3 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Debit</div>
                                        <div className="mt-1 text-sm font-medium text-slate-900">{debitLedgerLabel || '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Credit</div>
                                        <div className="mt-1 text-sm font-medium text-slate-900">{creditLedgerLabel || '-'}</div>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput id="bat_modal_amount" label="Amount" type="number" value={data.amount} onChange={(e) => setData('amount', e.target.value)} error={errors.amount} required />
                            <FloatingInput id="bat_modal_reference" label="Reference no" value={data.reference_no} onChange={(e) => setData('reference_no', e.target.value)} error={errors.reference_no} />
                            <FloatingInput id="bat_modal_description" label="Description" value={data.description} onChange={(e) => setData('description', e.target.value)} error={errors.description} className="md:col-span-2" />
                        </div>
                        <div className="flex flex-wrap items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                            <PrimaryButton disabled={processing || !data.transaction_type} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">{processing && <Spinner size="sm" className="text-white" />}<span>Save transaction</span></PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>
        </AuthenticatedLayout>
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

function ledgerLabel(item) {
    if (!item) {
        return '';
    }

    return item.account_code ? `${item.account_code} - ${toTitleCase(item.name ?? '')}` : toTitleCase(item.name ?? '');
}
