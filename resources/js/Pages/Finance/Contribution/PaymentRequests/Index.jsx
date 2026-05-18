import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ContributionPaymentRequestsIndex({ items, catalogs, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canView = can('contributions.obligations.view');
    const canCreate = can('contributions.obligations.create');
    const canTxnCreate = can('contributions.transactions.create');

    const [q, setQ] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? '');
    const [catalogUuid, setCatalogUuid] = useState(filters?.catalog_uuid ?? '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(route('finance.contribution.payment-requests.index'), {
            q: q || undefined,
            status: status || undefined,
            catalog_uuid: catalogUuid || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        setStatus('');
        setCatalogUuid('');
        setDateFrom('');
        setDateTo('');
        router.get(route('finance.contribution.payment-requests.index'), {}, { preserveState: true, replace: true });
    };

    const tableRows = items?.data ?? [];

    const getStatusColor = (status) => {
        switch (status) {
            case 'paid': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            case 'partial': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            case 'waived': return 'bg-purple-50 text-purple-700 ring-1 ring-purple-200';
            case 'cancelled': return 'bg-slate-50 text-slate-700 ring-1 ring-slate-200';
            default: return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
        }
    };

    const [open, setOpen] = useState(false);
    const [paymentOpen, setPaymentOpen] = useState(false);
    const [currentItem, setCurrentItem] = useState(null);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        contribution_catalog_uuid: '',
        payer_member_uuid: '',
        notes: '',
    });

    const close = () => { setOpen(false); reset(); clearErrors(); };

    const submit = (e) => {
        e.preventDefault();
        post(route('finance.contribution.payment-requests.store'), { preserveScroll: true, onSuccess: close });
    };

    // Payment modal form
    const { data: payData, setData: setPayData, post: postPay, processing: processingPay, errors: errorsPay, reset: resetPay, clearErrors: clearErrorsPay } = useForm({
        contribution_payment_request_uuid: '',
        transaction_type: 'payment',
        amount: '',
        payment_method: 'cash',
        reference_no: '',
        member_uuid: '',
        notes: '',
    });

    const openPayment = (item) => {
        setCurrentItem(item);
        setPayData('contribution_payment_request_uuid', item.uuid);
        setPayData('transaction_type', 'payment');
        setPayData('amount', item.balance ?? '');
        setPayData('payment_method', 'cash');
        setPayData('reference_no', '');
        setPayData('member_uuid', item?.payer_member_uuid || '');
        setPayData('notes', '');
        clearErrorsPay();
        setPaymentOpen(true);
    };

    const closePayment = () => {
        setPaymentOpen(false);
        setCurrentItem(null);
        resetPay();
        clearErrorsPay();
    };

    const submitPayment = (e) => {
        e.preventDefault();
        postPay(route('finance.contribution.transactions.store'), { preserveScroll: true, onSuccess: closePayment });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Contribution Payment Requests" />
            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Contribution Payment Requests</h1>
                        <p className="mt-1 text-sm text-slate-500">View and manage contribution payment requests.</p>
                    </div>
                    {canCreate && (
                        <PrimaryButton type="button" onClick={() => setOpen(true)} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">
                            <span className="text-lg font-bold leading-none">+</span>
                            <span>Request</span>
                        </PrimaryButton>
                    )}
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="space-y-3">
                        <div className="w-full md:max-w-xl">
                            <FloatingInput id="obligations_q" label="Search (name, code, member)" value={q} onChange={(e) => setQ(e.target.value)} />
                        </div>
                        <div className="grid gap-3 md:grid-cols-6 items-end">
                            <div className="md:col-span-2">
                                <FloatingSelect id="filter_status" label="Status" value={status} onChange={(e) => setStatus(e.target.value)}>
                                    <option value="">All status</option>
                                    <option value="pending">Pending</option>
                                    <option value="partial">Partial</option>
                                    <option value="paid">Paid</option>
                                    <option value="waived">Waived</option>
                                    <option value="cancelled">Cancelled</option>
                                </FloatingSelect>
                            </div>
                            <div className="md:col-span-2">
                                <FloatingSelect id="filter_catalog" label="Catalog" value={catalogUuid} onChange={(e) => setCatalogUuid(e.target.value)}>
                                    <option value="">All catalogs</option>
                                    {catalogs.map((c) => <option key={c.uuid} value={c.uuid}>{c.name}</option>)}
                                </FloatingSelect>
                            </div>
                            <div className="md:col-span-1">
                                <FloatingInput id="filter_from" label="From date" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
                            </div>
                            <div className="md:col-span-1">
                                <FloatingInput id="filter_to" label="To date" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
                            </div>
                            <div className="md:col-span-6 flex gap-2 md:justify-end">
                                <PrimaryButton type="submit" className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">Search</PrimaryButton>
                                <SecondaryButton type="button" onClick={clearSearch} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Clear</SecondaryButton>
                            </div>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th className="w-16">#</th>
                                        <th>Contribution</th>
                                        <th>Payer</th>
                                        <th>Paid</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th className="w-32">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((item, idx) => (
                                        <tr key={item.uuid} className="transition hover:bg-blue-50/40">
                                            <td className="px-4 py-3 text-sm text-slate-600">{(items?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="font-semibold text-slate-900">{item.rule_snapshot_name}</div>
                                                <div className="text-xs text-slate-500">{item.rule_snapshot_code}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{item.payer_member_name ?? '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{Number(item?.amount_paid ?? 0).toLocaleString()}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{Number(item?.balance ?? 0).toLocaleString()}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusColor(item.status)}`}>{item.status}</span>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex items-center gap-2">
                                                    {canView && (
                                                        <button
                                                            type="button"
                                                            onClick={() => router.visit(route('finance.contribution.payment-requests.show', item.uuid))}
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50"
                                                            title="View"
                                                        >
                                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                    )}
                                                    {canTxnCreate && item.status !== 'paid' && item.status !== 'waived' && item.status !== 'cancelled' && (
                                                        <button
                                                            type="button"
                                                            onClick={() => openPayment(item)}
                                                            className="inline-flex h-9 items-center justify-center rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white hover:bg-blue-700"
                                                        >
                                                            Record
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && <tr><td colSpan={7} className="px-4 py-10 text-center text-sm text-slate-500">No contribution obligations found.</td></tr>}
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
            {/* Inline Record Payment Modal */}
            {canTxnCreate && (
                <Modal show={paymentOpen} onClose={closePayment} maxWidth="sm">
                    <div className="p-6">
                        <ModalHeader title="Record Payment" subtitle="Record a payment for this request." onClose={closePayment} showRequiredNote />
                        <form onSubmit={submitPayment} className="mt-4 space-y-4">
                            {currentItem?.payer_member_uuid ? (
                                <div>
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Payer Member</div>
                                    <div className="mt-1 text-sm text-slate-700">{currentItem?.payer_member_name}</div>
                                </div>
                            ) : (
                                <SearchableMemberSelect
                                    id="idx_txn_member"
                                    label="Payer Member (optional)"
                                    value={payData.member_uuid}
                                    onChange={(value) => setPayData('member_uuid', value)}
                                    error={errorsPay.member_uuid}
                                />
                            )}
                            <FloatingSelect id="idx_txn_type" label="Transaction Type" required value={payData.transaction_type} onChange={(e) => setPayData('transaction_type', e.target.value)} error={errorsPay.transaction_type}>
                                <option value="payment">Payment</option>
                                <option value="adjustment">Adjustment</option>
                            </FloatingSelect>
                            <FloatingInput id="idx_txn_amount" label="Amount" required type="number" step="0.01" min="0" value={payData.amount} onChange={(e) => setPayData('amount', e.target.value)} error={errorsPay.amount} />
                            <FloatingSelect id="idx_txn_method" label="Payment Method" value={payData.payment_method} onChange={(e) => setPayData('payment_method', e.target.value)} error={errorsPay.payment_method}>
                                <option value="cash">Cash</option>
                                <option value="bank">Bank</option>
                                <option value="mobile">Mobile</option>
                                <option value="other">Other</option>
                            </FloatingSelect>
                            <FloatingInput id="idx_txn_reference" label="Reference Number" value={payData.reference_no} onChange={(e) => setPayData('reference_no', e.target.value)} error={errorsPay.reference_no} />
                            <FloatingInput id="idx_txn_notes" label="Notes" value={payData.notes} onChange={(e) => setPayData('notes', e.target.value)} error={errorsPay.notes} />
                            <div className="flex items-center justify-end gap-2">
                                <SecondaryButton type="button" onClick={closePayment} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                                <PrimaryButton disabled={processingPay} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">{processingPay && <Spinner size="sm" className="text-white" />}<span>Save</span></PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            )}
            {canCreate && (
                <Modal show={open} onClose={close} maxWidth="md">
                    <div className="p-4 sm:p-6">
                        <ModalHeader title="Create payment request" subtitle="Create a manual contribution payment request." onClose={close} showRequiredNote />
                        <form onSubmit={submit} className="mt-4 space-y-4">
                            <div className="grid gap-3 sm:gap-4 md:grid-cols-1">
                                <FloatingSelect id="pr_catalog" label="Contribution Catalog" required value={data.contribution_catalog_uuid} onChange={(e) => setData('contribution_catalog_uuid', e.target.value)} error={errors.contribution_catalog_uuid}>
                                    <option value="">Select catalog</option>
                                    {catalogs.map((c) => <option key={c.uuid} value={c.uuid}>{c.name} ({c.code})</option>)}
                                </FloatingSelect>
                            </div>
                            <div className="grid gap-3 sm:gap-4 md:grid-cols-1">
                                <SearchableMemberSelect
                                    id="pr_payer"
                                    label="Payer Member *"
                                    value={data.payer_member_uuid}
                                    onChange={(value) => setData('payer_member_uuid', value)}
                                    error={errors.payer_member_uuid}
                                />
                            </div>
                            <FloatingInput id="pr_notes" label="Notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} error={errors.notes} />
                            <div className="mt-2 flex items-center justify-end gap-2">
                                <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                                <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">{processing && <Spinner size="sm" className="text-white" />}<span>Save Request</span></PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            )}
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
