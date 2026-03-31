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

export default function PettyCashFundsIndex({ funds, ledgers, currencies, users, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const canCreate = Array.isArray(permissions) && permissions.includes('finance.petty-cash-funds.create');
    const canCreateReplenishment = Array.isArray(permissions) && permissions.includes('finance.petty-cash-replenishments.create');
    const [open, setOpen] = useState(false);
    const [selectedRow, setSelectedRow] = useState(null);
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        ledger_uuid: '',
        currency_uuid: '',
        custodian_user_uuid: '',
        imprest_amount: '',
        min_reorder_amount: '',
        is_active: true,
    });

    const rows = useMemo(() => funds?.data ?? [], [funds?.data]);

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('finance.petty-cash-funds.store'), { preserveScroll: true, onSuccess: close });
    };
    const applyFilters = (e) => {
        e.preventDefault();
        router.get(route('finance.petty-cash-funds.index'), {
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true, replace: true });
    };
    const clearFilters = () => {
        setDateFrom('');
        setDateTo('');
        router.get(route('finance.petty-cash-funds.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Petty Cash Funds" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Petty Cash Funds</h1>
                        <p className="mt-1 text-sm text-slate-500">Fund balances shown here come from the petty cash ledger in General Ledger, not from voucher totals alone.</p>
                    </div>
                    {canCreate && (
                        <PrimaryButton type="button" onClick={() => setOpen(true)} className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">
                            New Fund
                        </PrimaryButton>
                    )}
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="mb-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <form onSubmit={applyFilters} className="grid gap-3 md:grid-cols-4 md:items-end">
                            <FloatingInput id="pcf_date_from" label="Created from" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                            <FloatingInput id="pcf_date_to" label="Created to" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                            <div className="flex items-center gap-2 md:col-span-2 md:justify-end">
                                <button type="submit" className="h-11 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Search</button>
                                <button type="button" onClick={clearFilters} className="h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</button>
                            </div>
                        </form>
                    </div>

                    <div className="overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Fund</th>
                                        <th>Custodian</th>
                                        <th>Imprest</th>
                                        <th>GL Balance</th>
                                        <th>Status</th>
                                        <th className="w-52">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.map((row) => (
                                        <tr key={row.uuid} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                <div className="font-semibold text-slate-900">{toTitleCase(row.name ?? '')}</div>
                                                <div className="text-xs text-slate-500">{row.code} · {toTitleCase(row.ledger_name ?? '')}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.custodian_name ?? '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.imprest_amount_formatted}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{row.gl_balance_formatted} {row.gl_balance_side}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${row.is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'}`}>
                                                    {row.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex flex-wrap gap-2">
                                                    <button type="button" onClick={() => setSelectedRow(row)} className="rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">View</button>
                                                    {row.needs_replenishment && canCreateReplenishment && (
                                                        <button
                                                            type="button"
                                                            className="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100"
                                                            onClick={() => router.get(route('finance.petty-cash-replenishments.index'), { fund_uuid: row.uuid, open_create: 1 })}
                                                        >
                                                            Replenish
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {rows.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-10 text-center text-sm text-slate-500">No petty cash funds found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <Modal show={open} onClose={close} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader title="New petty cash fund" subtitle="Each fund is linked to one petty cash ledger. The official balance is derived from General Ledger postings. Fund code is generated automatically by the system." onClose={close} showRequiredNote />
                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingInput id="pcf_name" label="Fund name" value={data.name} onChange={(e) => setData('name', e.target.value)} error={errors.name} required />
                            <FloatingSelect id="pcf_ledger" label="Petty cash ledger" value={data.ledger_uuid} onChange={(e) => setData('ledger_uuid', e.target.value)} error={errors.ledger_uuid} required>
                                <option value="">Select ledger</option>
                                {(ledgers ?? []).map((item) => <option key={item.uuid} value={item.uuid}>{item.account_code ? `${item.account_code} - ${toTitleCase(item.name ?? '')}` : toTitleCase(item.name ?? '')}</option>)}
                            </FloatingSelect>
                            <FloatingSelect id="pcf_currency" label="Currency" value={data.currency_uuid} onChange={(e) => setData('currency_uuid', e.target.value)} error={errors.currency_uuid} required>
                                <option value="">Select currency</option>
                                {(currencies ?? []).map((item) => <option key={item.uuid} value={item.uuid}>{item.code} - {toTitleCase(item.name ?? '')}</option>)}
                            </FloatingSelect>
                            <FloatingSelect id="pcf_custodian" label="Custodian" value={data.custodian_user_uuid} onChange={(e) => setData('custodian_user_uuid', e.target.value)}>
                                <option value="">Select custodian</option>
                                {(users ?? []).map((item) => <option key={item.uuid} value={item.uuid}>{item.name}</option>)}
                            </FloatingSelect>
                            <div className="grid gap-4 md:grid-cols-2">
                                <FloatingInput id="pcf_imprest" label="Imprest amount" type="number" value={data.imprest_amount} onChange={(e) => setData('imprest_amount', e.target.value)} error={errors.imprest_amount} required />
                                <FloatingInput id="pcf_reorder" label="Min reorder amount" type="number" value={data.min_reorder_amount} onChange={(e) => setData('min_reorder_amount', e.target.value)} error={errors.min_reorder_amount} />
                            </div>
                        </div>
                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                            Custodian: person responsible for the physical cash fund. Imprest: target fund float. Min reorder: threshold where replenishment should be initiated; it must be less than or equal to imprest.
                        </div>
                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                            <PrimaryButton className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700" disabled={processing}>
                                {processing && <Spinner size="sm" className="mr-2 text-white" />}
                                <span>Create Fund</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={!!selectedRow} onClose={() => setSelectedRow(null)} maxWidth="xl">
                <div className="p-6">
                    <ModalHeader
                        title="Fund details"
                        subtitle="Review the full petty cash fund information including dates."
                        onClose={() => setSelectedRow(null)}
                    />
                    {selectedRow && (
                        <div className="mt-4 max-h-[70vh] space-y-5 overflow-y-auto pr-1">
                            <section className="rounded-2xl border border-slate-200 p-4">
                                <div className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm">Basic Info</div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Fund</div>
                                        <div className="mt-1 text-sm font-semibold text-slate-900">{toTitleCase(selectedRow.name ?? '')}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Code</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.code ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4 md:col-span-2">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Ledger</div>
                                        <div className="mt-1 text-sm text-slate-900">{toTitleCase(selectedRow.ledger_name ?? '') || '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Currency</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.currency_code ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Custodian</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.custodian_name ?? '-'}</div>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-2xl border border-slate-200 p-4">
                                <div className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm">Workflow Info</div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Created At</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.created_at ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Updated At</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.updated_at ?? '-'}</div>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-2xl border border-slate-200 p-4">
                                <div className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm">Accounting Info</div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Imprest Amount</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.imprest_amount_formatted}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Min Reorder Amount</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.min_reorder_amount_formatted ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">GL Balance</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.gl_balance_formatted} {selectedRow.gl_balance_side}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Reorder Status</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.needs_replenishment ? 'Needs replenishment' : 'OK'}</div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    )}
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
