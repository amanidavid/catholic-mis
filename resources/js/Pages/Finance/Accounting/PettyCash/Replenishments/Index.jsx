import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import ReasonActionModal from '@/Components/ReasonActionModal';
import SearchableLedgerSelect from '@/Components/SearchableLedgerSelect';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { toTitleCase } from '@/lib/formatters';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function PettyCashReplenishmentsIndex({ items, funds, prefill, filters, statuses }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('finance.petty-cash-replenishments.create');
    const canApprove = can('finance.petty-cash-replenishments.approve');
    const canPost = can('finance.petty-cash-replenishments.post');
    const canCancel = can('finance.petty-cash-replenishments.cancel');

    const statusBadgeClass = (value) => {
        switch ((value ?? '').toLowerCase()) {
            case 'draft':
                return 'bg-slate-100 text-slate-700 ring-slate-200';
            case 'submitted':
                return 'bg-blue-50 text-blue-700 ring-blue-200';
            case 'approved':
                return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
            case 'posted':
                return 'bg-violet-50 text-violet-700 ring-violet-200';
            case 'cancelled':
                return 'bg-rose-50 text-rose-700 ring-rose-200';
            default:
                return 'bg-slate-100 text-slate-700 ring-slate-200';
        }
    };

    const [q, setQ] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? '');
    const [open, setOpen] = useState(false);
    const [actionModal, setActionModal] = useState({ open: false, routeName: '', uuid: '', title: '', subtitle: '', actionLabel: '' });
    const [selectedRow, setSelectedRow] = useState(null);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        petty_cash_fund_uuid: '',
        transaction_date: '',
        source_ledger_uuid: '',
        reference_no: '',
        description: '',
        amount: '',
    });
    const { data: actionData, setData: setActionData, post: postAction, processing: actionProcessing, errors: actionErrors, reset: resetAction, clearErrors: clearActionErrors } = useForm({
        reason: '',
    });

    const tableRows = useMemo(() => items?.data ?? [], [items?.data]);
    const selectedFund = useMemo(
        () => (funds ?? []).find((item) => item.uuid === data.petty_cash_fund_uuid) ?? null,
        [funds, data.petty_cash_fund_uuid],
    );
    const selectedFundCurrencyUuid = selectedFund?.currency_uuid ?? '';

    useEffect(() => {
        if (!prefill?.open_create) {
            return;
        }

        setOpen(true);
        if (prefill?.fund_uuid) {
            setData('petty_cash_fund_uuid', prefill.fund_uuid);
        }
    }, [prefill?.open_create, prefill?.fund_uuid]);

    const search = (e) => {
        e.preventDefault();
        router.get(route('finance.petty-cash-replenishments.index'), { q: q || undefined, status: status || undefined }, { preserveState: true, replace: true });
    };

    const clear = () => {
        setQ('');
        setStatus('');
        router.get(route('finance.petty-cash-replenishments.index'), {}, { preserveState: true, replace: true });
    };

    const close = () => {
        setOpen(false);
        reset();
        clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('finance.petty-cash-replenishments.store'), { preserveScroll: true, onSuccess: close });
    };

    const act = (name, uuid) => {
        router.post(route(name, uuid), {}, { preserveScroll: true });
    };
    const openActionModal = ({ routeName, uuid, title, subtitle, actionLabel }) => {
        setActionModal({ open: true, routeName, uuid, title, subtitle, actionLabel });
        setActionData('reason', '');
        clearActionErrors();
    };
    const closeActionModal = () => {
        setActionModal({ open: false, routeName: '', uuid: '', title: '', subtitle: '', actionLabel: '' });
        resetAction();
        clearActionErrors();
    };
    const submitActionModal = (e) => {
        e.preventDefault();
        postAction(route(actionModal.routeName, actionModal.uuid), {
            preserveScroll: true,
            onSuccess: closeActionModal,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Petty Cash Replenishments" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Petty Cash Replenishments</h1>
                        <p className="mt-1 text-sm text-slate-500">Fund top-ups follow draft, submit, approve, post workflow and create accounting entries on posting.</p>
                    </div>
                    {canCreate && (
                        <PrimaryButton type="button" onClick={() => setOpen(true)} className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">
                            New Replenishment
                        </PrimaryButton>
                    )}
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="mb-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <form onSubmit={search} className="grid gap-3 md:grid-cols-6 md:items-end">
                            <FloatingInput id="pcr_q" label="Search no/reference/description" value={q} onChange={(e) => setQ(e.target.value)} className="md:col-span-3" />
                            <FloatingSelect id="pcr_status" label="Status" value={status} onChange={(e) => setStatus(e.target.value)} className="md:col-span-1">
                                <option value="">All statuses</option>
                                {(statuses ?? []).map((s) => <option key={s} value={s}>{toTitleCase(s)}</option>)}
                            </FloatingSelect>
                            <div className="flex items-center gap-2 md:col-span-2 md:justify-end">
                                <button type="submit" className="h-11 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Search</button>
                                <button type="button" onClick={clear} className="h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</button>
                            </div>
                        </form>
                    </div>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Date</th>
                                        <th>Fund</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th className="w-64">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((row) => (
                                        <tr key={row.uuid} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                <div className="font-semibold text-slate-900">{row.replenishment_no}</div>
                                                <div className="text-xs text-slate-500">{row.reference_no ?? row.description ?? '-'}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.transaction_date}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(row.fund_name ?? '')}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{row.amount_formatted}</td>
                                            <td className="px-4 py-3 text-sm"><span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${statusBadgeClass(row.status)}`}>{toTitleCase(row.status ?? '')}</span></td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex flex-wrap gap-2">
                                                    <button type="button" onClick={() => setSelectedRow(row)} className="rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">View</button>
                                                    {row.status === 'draft' && canCreate && <button type="button" onClick={() => act('finance.petty-cash-replenishments.submit', row.uuid)} className="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 ring-1 ring-blue-200 hover:bg-blue-100">Submit</button>}
                                                    {row.status === 'submitted' && canApprove && <button type="button" onClick={() => act('finance.petty-cash-replenishments.approve', row.uuid)} className="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">Approve</button>}
                                                    {row.status === 'submitted' && canApprove && <button type="button" onClick={() => openActionModal({ routeName: 'finance.petty-cash-replenishments.reject', uuid: row.uuid, title: 'Reject replenishment', subtitle: 'Provide a reason for returning this replenishment to draft.', actionLabel: 'Reject Replenishment' })} className="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100">Reject</button>}
                                                    {row.status === 'approved' && canPost && <button type="button" onClick={() => act('finance.petty-cash-replenishments.post', row.uuid)} className="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100">Post</button>}
                                                    {['draft', 'submitted', 'approved'].includes(row.status) && canCancel && <button type="button" onClick={() => openActionModal({ routeName: 'finance.petty-cash-replenishments.cancel', uuid: row.uuid, title: 'Cancel replenishment', subtitle: 'Provide a reason for cancelling this replenishment.', actionLabel: 'Cancel Replenishment' })} className="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200">Cancel</button>}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && <tr><td colSpan={6} className="px-4 py-10 text-center text-sm text-slate-500">No petty cash replenishments found.</td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <Modal show={open} onClose={close} maxWidth="md">
                <div className="p-6">
                    <ModalHeader title="New replenishment" subtitle="Top up petty cash from a source ledger." onClose={close} showRequiredNote />
                    <form onSubmit={submit} className="mt-4 space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingSelect id="pcr_fund" label="Petty cash fund" value={data.petty_cash_fund_uuid} onChange={(e) => setData('petty_cash_fund_uuid', e.target.value)} error={errors.petty_cash_fund_uuid} required>
                                <option value="">Select fund</option>
                                {(funds ?? []).map((f) => <option key={f.uuid} value={f.uuid}>{toTitleCase(f.name ?? '')}</option>)}
                            </FloatingSelect>
                            <FloatingInput id="pcr_date" label="Transaction date" type="date" value={data.transaction_date} onChange={(e) => setData('transaction_date', e.target.value)} error={errors.transaction_date} required />
                            <SearchableLedgerSelect
                                id="pcr_source"
                                label="Source ledger"
                                value={data.source_ledger_uuid}
                                onChange={(value) => setData('source_ledger_uuid', value)}
                                purpose="source"
                                currencyUuid={selectedFundCurrencyUuid}
                                disabled={!selectedFundCurrencyUuid}
                                error={errors.source_ledger_uuid}
                            />
                            <FloatingInput id="pcr_amount" label="Amount" type="number" value={data.amount} onChange={(e) => setData('amount', e.target.value)} error={errors.amount} required />
                            <FloatingInput id="pcr_ref" label="Reference no" value={data.reference_no} onChange={(e) => setData('reference_no', e.target.value)} error={errors.reference_no} />
                            <FloatingInput id="pcr_desc" label="Description" value={data.description} onChange={(e) => setData('description', e.target.value)} error={errors.description} />
                        </div>
                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                            <PrimaryButton className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700" disabled={processing}>
                                {processing && <Spinner size="sm" className="mr-2 text-white" />}
                                <span>Create Draft</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={!!selectedRow} onClose={() => setSelectedRow(null)} maxWidth="xl">
                <div className="p-6">
                    <ModalHeader
                        title="Replenishment details"
                        subtitle="Review the full petty cash replenishment information."
                        onClose={() => setSelectedRow(null)}
                    />
                    {selectedRow && (
                        <div className="mt-4 max-h-[70vh] space-y-5 overflow-y-auto pr-1">
                            <section className="rounded-2xl border border-slate-200 p-4">
                                <div className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm">Basic Info</div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Replenishment No</div>
                                        <div className="mt-1 text-sm font-semibold text-slate-900">{selectedRow.replenishment_no}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</div>
                                        <div className="mt-1 text-sm text-slate-900">{toTitleCase(selectedRow.status ?? '')}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Transaction Date</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.transaction_date ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Fund</div>
                                        <div className="mt-1 text-sm text-slate-900">{toTitleCase(selectedRow.fund_name ?? '') || '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4 md:col-span-2">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Source Ledger</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.source_ledger_account_code ? `${selectedRow.source_ledger_account_code} - ${toTitleCase(selectedRow.source_ledger_name ?? '')}` : toTitleCase(selectedRow.source_ledger_name ?? '') || '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Reference No</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.reference_no ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Description</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.description ?? '-'}</div>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-2xl border border-slate-200 p-4">
                                <div className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm">Workflow Info</div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Rejected At</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.rejected_at ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Rejected Reason</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.rejection_reason ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Cancelled At</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.cancelled_at ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Cancelled Reason</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.cancellation_reason ?? '-'}</div>
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-2xl border border-slate-200 p-4">
                                <div className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm">Accounting Info</div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</div>
                                        <div className="mt-1 text-sm font-semibold text-slate-900">{selectedRow.amount_formatted}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Journal</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.journal_no ?? '-'}</div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    )}
                </div>
            </Modal>

            <ReasonActionModal
                show={actionModal.open}
                onClose={closeActionModal}
                title={actionModal.title}
                subtitle={actionModal.subtitle}
                value={actionData.reason}
                onChange={(value) => setActionData('reason', value)}
                onSubmit={submitActionModal}
                error={actionErrors.reason}
                processing={actionProcessing}
                actionLabel={actionModal.actionLabel}
            />
        </AuthenticatedLayout>
    );
}
