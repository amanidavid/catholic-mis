import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import SearchableMemberSelect from '@/Components/SearchableMemberSelect';
import { useState } from 'react';

export default function ContributionObligationsShow({ item, transactions }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('contributions.transactions.create');
    const canUpdate = can('contributions.obligations.update');

    const [paymentOpen, setPaymentOpen] = useState(false);
    const [waiverOpen, setWaiverOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);

    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        contribution_payment_request_uuid: item.uuid,
        transaction_type: 'payment',
        amount: item.balance,
        payment_method: 'cash',
        reference_no: '',
        member_uuid: '',
        notes: '',
    });

    const closePayment = () => {
        setPaymentOpen(false);
        reset();
        clearErrors();
    };

    const submitPayment = (e) => {
        e.preventDefault();
        post(route('finance.contribution.transactions.store'), { preserveScroll: true, onSuccess: closePayment });
    };

    const handleWaiver = () => {
        router.post(route('finance.contribution.transactions.waive', item.uuid), {}, { preserveScroll: true, onSuccess: () => setWaiverOpen(false) });
    };

    const handleCancel = () => {
        router.post(route('finance.contribution.transactions.cancel', item.uuid), {}, { preserveScroll: true, onSuccess: () => setCancelOpen(false) });
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'paid': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            case 'partial': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            case 'waived': return 'bg-purple-50 text-purple-700 ring-1 ring-purple-200';
            case 'cancelled': return 'bg-slate-50 text-slate-700 ring-1 ring-slate-200';
            default: return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
        }
    };

    const getTransactionTypeColor = (type) => {
        switch (type) {
            case 'payment': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
            case 'waiver': return 'bg-purple-50 text-purple-700 ring-1 ring-purple-200';
            case 'refund': return 'bg-rose-50 text-rose-700 ring-1 ring-rose-200';
            case 'adjustment': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
            default: return 'bg-slate-50 text-slate-700 ring-1 ring-slate-200';
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Contribution Payment Request Details" />
            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('finance.contribution.payment-requests.index')} className="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200">
                        ← Back
                    </Link>
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Contribution Payment Request Details</h1>
                        <p className="mt-1 text-sm text-slate-500">View payment request details and transaction history.</p>
                    </div>
                    <div className="flex items-center gap-2">
                        <SecondaryButton type="button" onClick={() => router.visit(route('finance.contribution.payment-requests.index'))} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Back</SecondaryButton>
                        {canCreate && item.status !== 'paid' && item.status !== 'waived' && item.status !== 'cancelled' && (
                            <PrimaryButton type="button" onClick={() => setPaymentOpen(true)} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700"><span className="text-lg font-bold leading-none">+</span><span>Record Payment</span></PrimaryButton>
                        )}
                        {canUpdate && item.status === 'pending' && (
                            <SecondaryButton type="button" onClick={() => setWaiverOpen(true)} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Waive</SecondaryButton>
                        )}
                        {canUpdate && item.status !== 'cancelled' && (
                            <SecondaryButton type="button" onClick={() => setCancelOpen(true)} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h2 className="text-lg font-semibold text-slate-900">Transaction History</h2>
                    <div className="mt-4 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th className="w-16">#</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {(() => {
                                        const rows = Array.isArray(transactions?.data) ? transactions.data : (Array.isArray(transactions) ? transactions : []);
                                        const start = transactions?.meta?.from ?? 1;
                                        return rows.map((txn, idx) => (
                                            <tr key={txn.uuid} className="transition hover:bg-blue-50/40">
                                                <td className="px-4 py-3 text-sm text-slate-600">{start + idx}</td>
                                                <td className="px-4 py-3 text-sm">
                                                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${getTransactionTypeColor(txn.transaction_type)}`}>{txn.transaction_type}</span>
                                                </td>
                                                <td className="px-4 py-3 text-sm font-semibold text-slate-900">{Number(txn?.amount ?? 0).toLocaleString()}</td>
                                                <td className="px-4 py-3 text-sm text-slate-700">{txn.payment_method ?? '-'}</td>
                                                <td className="px-4 py-3 text-sm text-slate-700">{txn.paid_at ? new Date(txn.paid_at).toLocaleDateString() : '-'}</td>
                                            </tr>
                                        ));
                                    })()}
                                    {((Array.isArray(transactions?.data) && transactions.data.length === 0) || (Array.isArray(transactions) && transactions.length === 0)) && (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-10 text-center text-sm text-slate-500">No transactions recorded.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={transactions?.meta} />
                        <Pagination links={transactions?.meta?.links ?? transactions?.links ?? []} />
                    </div>
                </section>
            </div>

            <PaymentModal open={paymentOpen} close={closePayment} item={item} data={data} setData={setData} submit={submitPayment} processing={processing} errors={errors} />
            <WaiverModal open={waiverOpen} close={() => setWaiverOpen(false)} item={item} onWaive={handleWaiver} />
            <CancelModal open={cancelOpen} close={() => setCancelOpen(false)} item={item} onCancel={handleCancel} />
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
                    className={`min-w-[2.25rem] rounded-lg px-3 py-2 text-sm font-semibold transition ${link.active ? 'bg-blue-600 text-white' : link.url ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' : 'bg-slate-100 text-slate-400'}`}
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
            Showing <span className="font-semibold text-slate-900">{meta.from ?? 0}</span> to <span className="font-semibold text-slate-900">{meta.to ?? 0}</span> of <span className="font-semibold text-slate-900">{meta.total ?? 0}</span>
        </div>
    );
}

function PaymentModal({ open, close, item, data, setData, submit, processing, errors }) {
    return (
        <Modal show={open} onClose={close} maxWidth="sm">
            <div className="p-6">
                <ModalHeader title="Record Payment" subtitle="Record a payment for this request." onClose={close} showRequiredNote />
                <form onSubmit={submit} className="mt-4 space-y-4">
                    {item?.payer_member_uuid ? (
                        <div>
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Payer Member</div>
                            <div className="mt-1 text-sm text-slate-700">{item?.payer_member_name}</div>
                        </div>
                    ) : (
                        <SearchableMemberSelect
                            id="txn_member"
                            label="Payer Member"
                            value={data.member_uuid}
                            onChange={(value) => setData('member_uuid', value)}
                            error={errors.member_uuid}
                        />
                    )}
                    <FloatingSelect id="txn_type" label="Transaction Type" required value={data.transaction_type} onChange={(e) => setData('transaction_type', e.target.value)} error={errors.transaction_type}>
                        <option value="payment">Payment</option>
                        <option value="adjustment">Adjustment</option>
                    </FloatingSelect>
                    <FloatingInput id="txn_amount" label="Amount" required type="number" step="0.01" min="0" value={data.amount} onChange={(e) => setData('amount', e.target.value)} error={errors.amount} />
                    <FloatingSelect id="txn_method" label="Payment Method" value={data.payment_method} onChange={(e) => setData('payment_method', e.target.value)} error={errors.payment_method}>
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="mobile">Mobile</option>
                        <option value="other">Other</option>
                    </FloatingSelect>
                    <FloatingInput id="txn_reference" label="Reference Number" value={data.reference_no} onChange={(e) => setData('reference_no', e.target.value)} error={errors.reference_no} />
                    <FloatingInput id="txn_notes" label="Notes" value={data.notes} onChange={(e) => setData('notes', e.target.value)} error={errors.notes} />
                    <div className="flex items-center justify-end gap-2">
                        <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                        <PrimaryButton disabled={processing} className="h-11 gap-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">{processing && <Spinner size="sm" className="text-white" />}<span>Save</span></PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

function WaiverModal({ open, close, item, onWaive }) {
    return (
        <Modal show={open} onClose={close} maxWidth="md">
            <div className="p-6">
                <ModalHeader title="Waive Payment Request" subtitle="This will waive the remaining balance of this payment request." onClose={close} />
                <div className="mt-4 rounded-xl border border-purple-200 bg-purple-50 px-4 py-3 text-sm text-purple-800">
                    Are you sure you want to waive <span className="font-semibold">{Number(item?.balance ?? 0).toLocaleString()} {item?.currency_code ?? ''}</span> for <span className="font-semibold">{item?.rule_snapshot_name ?? ''}</span>?
                </div>
                <div className="mt-5 flex items-center justify-end gap-2">
                    <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                    <PrimaryButton type="button" onClick={onWaive} className="h-11 rounded-lg bg-purple-600 text-sm font-semibold text-white hover:bg-purple-700">Waive</PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}

function CancelModal({ open, close, item, onCancel }) {
    return (
        <Modal show={open} onClose={close} maxWidth="md">
            <div className="p-6">
                <ModalHeader title="Cancel Payment Request" subtitle="This will cancel this payment request." onClose={close} />
                <div className="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    Are you sure you want to cancel <span className="font-semibold">{item.rule_snapshot_name}</span>?
                </div>
                <div className="mt-5 flex items-center justify-end gap-2">
                    <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                    <PrimaryButton type="button" onClick={onCancel} className="h-11 rounded-lg bg-rose-600 text-sm font-semibold text-white hover:bg-rose-700">Cancel Request</PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
