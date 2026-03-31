import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingFileInput from '@/Components/FloatingFileInput';
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
import { useMemo, useState } from 'react';

export default function PettyCashVouchersIndex({ items, funds, filters, statuses }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canCreate = can('finance.petty-cash-vouchers.create');
    const canUpdate = can('finance.petty-cash-vouchers.update');
    const canApprove = can('finance.petty-cash-vouchers.approve');
    const canPost = can('finance.petty-cash-vouchers.post');
    const canCancel = can('finance.petty-cash-vouchers.cancel');
    const canCreateReplenishment = can('finance.petty-cash-replenishments.create');

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
    const [editingVoucherUuid, setEditingVoucherUuid] = useState(null);
    const [actionModal, setActionModal] = useState({ open: false, routeName: '', uuid: '', title: '', subtitle: '', actionLabel: '' });
    const [selectedRow, setSelectedRow] = useState(null);
    const [previewOpen, setPreviewOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState('');
    const [previewTitle, setPreviewTitle] = useState('Attachment preview');

    const { data, setData, post, patch, processing, errors, reset, clearErrors } = useForm({
        petty_cash_fund_uuid: '',
        transaction_date: '',
        payee_name: '',
        reference_no: '',
        description: '',
        attachments: [],
        lines: [{ expense_ledger_uuid: '', description: '', amount: '' }],
    });
    const { data: actionData, setData: setActionData, post: postAction, processing: actionProcessing, errors: actionErrors, reset: resetAction, clearErrors: clearActionErrors } = useForm({
        reason: '',
    });

    const tableRows = useMemo(() => items?.data ?? [], [items?.data]);

    const apply = (e) => {
        e.preventDefault();
        router.get(route('finance.petty-cash-vouchers.index'), { q: q || undefined, status: status || undefined }, { preserveState: true, replace: true });
    };

    const clear = () => {
        setQ('');
        setStatus('');
        router.get(route('finance.petty-cash-vouchers.index'), {}, { preserveState: true, replace: true });
    };

    const close = () => {
        setOpen(false);
        setEditingVoucherUuid(null);
        reset();
        clearErrors();
    };

    const submit = (e) => {
        e.preventDefault();
        const action = editingVoucherUuid
            ? patch(route('finance.petty-cash-vouchers.update', editingVoucherUuid), { preserveScroll: true, onSuccess: close, forceFormData: true })
            : post(route('finance.petty-cash-vouchers.store'), { preserveScroll: true, onSuccess: close, forceFormData: true });

        return action;
    };

    const addLine = () => setData('lines', [...(data.lines ?? []), { expense_ledger_uuid: '', description: '', amount: '' }]);
    const updateLine = (index, field, value) => {
        const next = [...(data.lines ?? [])];
        next[index] = { ...next[index], [field]: value };
        setData('lines', next);
    };

    const selectedFund = useMemo(
        () => (funds ?? []).find((item) => item.uuid === data.petty_cash_fund_uuid) ?? null,
        [funds, data.petty_cash_fund_uuid],
    );
    const selectedFundCurrencyUuid = selectedFund?.currency_uuid ?? '';
    const lowFunds = useMemo(() => (funds ?? []).filter((item) => item.needs_replenishment), [funds]);
    const draftAttachments = useMemo(
        () => tableRows.find((row) => row.uuid === editingVoucherUuid)?.attachments ?? [],
        [tableRows, editingVoucherUuid],
    );

    const act = (routeName, uuid) => router.post(route(routeName, uuid), {}, { preserveScroll: true });
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
    const openPreview = (url, title) => {
        setPreviewUrl(url);
        setPreviewTitle(title || 'Attachment preview');
        setPreviewOpen(true);
    };
    const closePreview = () => {
        setPreviewOpen(false);
        window.setTimeout(() => setPreviewUrl(''), 150);
    };
    const handleAttachmentsChange = (e) => {
        setData('attachments', Array.from(e.target.files ?? []));
    };
    const selectedAttachmentPreviews = useMemo(
        () => (data.attachments ?? []).map((file, index) => ({
            key: `${file.name}-${index}`,
            name: file.name,
            type: file.type,
            size: file.size,
            url: URL.createObjectURL(file),
        })),
        [data.attachments],
    );
    const startEdit = (row) => {
        setEditingVoucherUuid(row.uuid);
        setData({
            petty_cash_fund_uuid: row.fund_uuid ?? '',
            transaction_date: row.transaction_date ?? '',
            payee_name: row.payee_name ?? '',
            reference_no: row.reference_no ?? '',
            description: row.description ?? '',
            attachments: [],
            lines: (row.lines?.length
                ? row.lines.map((line) => ({
                    expense_ledger_uuid: line.expense_ledger_uuid ?? '',
                    description: line.description ?? '',
                    amount: line.amount ?? '',
                }))
                : [{ expense_ledger_uuid: '', description: '', amount: '' }]),
        });
        clearErrors();
        setOpen(true);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Petty Cash Vouchers" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Petty Cash Vouchers</h1>
                        <p className="mt-1 text-sm text-slate-500">Requesters create and submit vouchers. Approvers approve. Posting writes the accounting impact to Journal and General Ledger.</p>
                    </div>
                    {canCreate && <PrimaryButton type="button" onClick={() => { setEditingVoucherUuid(null); setOpen(true); }} className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700">New Voucher</PrimaryButton>}
                </div>

                {lowFunds.length > 0 && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-100">
                        <div className="font-semibold">Replenishment alert: {lowFunds.length} fund(s) are at or below minimum reorder threshold.</div>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {lowFunds.map((fund) => (
                                <button
                                    key={fund.uuid}
                                    type="button"
                                    className="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-300 hover:bg-amber-100"
                                    onClick={() => router.get(route('finance.petty-cash-replenishments.index'), { fund_uuid: fund.uuid, open_create: 1 })}
                                    disabled={!canCreateReplenishment}
                                >
                                    {canCreateReplenishment ? `Create Replenishment - ${toTitleCase(fund.name ?? '')}` : `${toTitleCase(fund.name ?? '')} needs replenishment`}
                                </button>
                            ))}
                        </div>
                    </div>
                )}

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="mb-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <form onSubmit={apply} className="grid gap-3 md:grid-cols-6 md:items-end">
                            <FloatingInput id="pcv_q" label="Search voucher / payee / description" value={q} onChange={(e) => setQ(e.target.value)} className="md:col-span-3" />
                            <FloatingSelect id="pcv_status" label="Status" value={status} onChange={(e) => setStatus(e.target.value)} className="md:col-span-1">
                                <option value="">All statuses</option>
                                {(statuses ?? []).map((item) => <option key={item} value={item}>{toTitleCase(item)}</option>)}
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
                                        <th>Voucher</th>
                                        <th>Date</th>
                                        <th>Fund</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th className="w-56">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((row) => (
                                        <tr key={row.uuid} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                <div className="font-semibold text-slate-900">{row.voucher_no}</div>
                                                <div className="text-xs text-slate-500">{row.reference_no ?? row.description ?? '-'}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.transaction_date}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(row.fund_name ?? '')}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{row.amount_formatted}</td>
                                            <td className="px-4 py-3 text-sm"><span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${statusBadgeClass(row.status)}`}>{toTitleCase(row.status ?? '')}</span></td>
                                            <td className="px-4 py-3 text-sm">
                                                <div className="flex flex-wrap gap-2">
                                                    <button type="button" onClick={() => setSelectedRow(row)} className="rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">View</button>
                                                    {row.status === 'draft' && canUpdate && <button type="button" onClick={() => startEdit(row)} className="rounded-lg bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100">Edit</button>}
                                                    {row.status === 'draft' && canCreate && <button type="button" onClick={() => act('finance.petty-cash-vouchers.submit', row.uuid)} className="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 ring-1 ring-blue-200 hover:bg-blue-100">Submit</button>}
                                                    {row.status === 'submitted' && canApprove && <button type="button" onClick={() => act('finance.petty-cash-vouchers.approve', row.uuid)} className="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100">Approve</button>}
                                                    {row.status === 'submitted' && canApprove && <button type="button" onClick={() => openActionModal({ routeName: 'finance.petty-cash-vouchers.reject', uuid: row.uuid, title: 'Reject petty cash voucher', subtitle: 'Provide a reason for returning this voucher to draft.', actionLabel: 'Reject Voucher' })} className="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100">Reject</button>}
                                                    {row.status === 'approved' && canPost && <button type="button" onClick={() => act('finance.petty-cash-vouchers.post', row.uuid)} className="rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100">Post</button>}
                                                    {['draft', 'submitted', 'approved'].includes(row.status) && canCancel && <button type="button" onClick={() => openActionModal({ routeName: 'finance.petty-cash-vouchers.cancel', uuid: row.uuid, title: 'Cancel petty cash voucher', subtitle: 'Provide a reason for cancelling this voucher.', actionLabel: 'Cancel Voucher' })} className="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-200">Cancel</button>}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && <tr><td colSpan={6} className="px-4 py-10 text-center text-sm text-slate-500">No petty cash vouchers found.</td></tr>}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <Modal show={open} onClose={close} maxWidth="2xl">
                <div className="p-6">
                    <ModalHeader title={editingVoucherUuid ? 'Edit petty cash voucher' : 'New petty cash voucher'} subtitle="Create the expense request first. After submission, a user with approve permission can approve it, and a user with post permission can post it to accounting." onClose={close} showRequiredNote />
                    <form onSubmit={submit} className="mt-4 max-h-[75vh] space-y-4 overflow-y-auto pr-1">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FloatingSelect id="pcv_fund" label="Petty cash fund" value={data.petty_cash_fund_uuid} onChange={(e) => setData('petty_cash_fund_uuid', e.target.value)} error={errors.petty_cash_fund_uuid} required>
                                <option value="">Select fund</option>
                                {(funds ?? []).map((item) => <option key={item.uuid} value={item.uuid}>{toTitleCase(item.name ?? '')} ({item.gl_balance_formatted} {item.gl_balance_side})</option>)}
                            </FloatingSelect>
                            <FloatingInput id="pcv_date" label="Transaction date" type="date" value={data.transaction_date} onChange={(e) => setData('transaction_date', e.target.value)} error={errors.transaction_date} required />
                            <FloatingInput id="pcv_payee" label="Payee" value={data.payee_name} onChange={(e) => setData('payee_name', e.target.value)} error={errors.payee_name} />
                            <FloatingInput id="pcv_ref" label="Reference no" value={data.reference_no} onChange={(e) => setData('reference_no', e.target.value)} error={errors.reference_no} />
                            <FloatingInput id="pcv_desc" label="Description" value={data.description} onChange={(e) => setData('description', e.target.value)} error={errors.description} className="md:col-span-2" />
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <div>
                                    <h3 className="text-sm font-semibold text-slate-900">Expense Lines</h3>
                                    <p className="mt-1 text-xs text-slate-500">Each line debits an expense ledger. Posting the voucher will credit the selected petty cash fund ledger in GL.</p>
                                </div>
                                <button type="button" onClick={addLine} className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Add Line</button>
                            </div>
                            <div className="space-y-3">
                                {(data.lines ?? []).map((line, index) => (
                                    <div key={index} className="grid gap-3 md:grid-cols-3">
                                        <SearchableLedgerSelect
                                            id={`pcv_line_ledger_${index}`}
                                            label="Expense ledger"
                                            value={line.expense_ledger_uuid}
                                            onChange={(value) => updateLine(index, 'expense_ledger_uuid', value)}
                                            purpose="expense"
                                            currencyUuid={selectedFundCurrencyUuid}
                                            disabled={!selectedFundCurrencyUuid}
                                            error={errors[`lines.${index}.expense_ledger_uuid`]}
                                        />
                                        <FloatingInput id={`pcv_line_desc_${index}`} label="Line description" value={line.description} onChange={(e) => updateLine(index, 'description', e.target.value)} />
                                        <FloatingInput id={`pcv_line_amount_${index}`} label="Amount" type="number" value={line.amount} onChange={(e) => updateLine(index, 'amount', e.target.value)} required />
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <div>
                                <h3 className="text-sm font-semibold text-slate-900">Attachments</h3>
                                <p className="mt-1 text-xs text-slate-500">Upload supporting documents. You can attach multiple files. Allowed: PDF, JPG, JPEG, PNG up to 5 MB each.</p>
                            </div>

                            <div className="mt-4">
                                <FloatingFileInput
                                    id="pcv_attachments"
                                    label="Voucher attachments"
                                    onChange={handleAttachmentsChange}
                                    error={errors.attachments || errors['attachments.0']}
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    multiple
                                />
                            </div>

                            {selectedAttachmentPreviews.length > 0 && (
                                <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Selected files</div>
                                    <div className="mt-3 space-y-2">
                                        {selectedAttachmentPreviews.map((attachment) => (
                                            <div key={attachment.key} className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div className="text-sm font-semibold text-slate-900">{attachment.name}</div>
                                                    <div className="mt-1 text-xs text-slate-500">{attachment.type || 'Unknown type'} · {(attachment.size / 1024 / 1024).toFixed(2)} MB</div>
                                                </div>
                                                <SecondaryButton type="button" className="h-10" onClick={() => openPreview(attachment.url, attachment.name)}>
                                                    Preview
                                                </SecondaryButton>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {editingVoucherUuid && draftAttachments.length > 0 && (
                                <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Existing attachments</div>
                                    <div className="mt-3 space-y-2">
                                        {draftAttachments.map((attachment) => (
                                            <div key={attachment.uuid} className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div className="text-sm font-semibold text-slate-900">{attachment.original_name}</div>
                                                    <div className="mt-1 text-xs text-slate-500">{attachment.created_at ?? '-'}</div>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    <SecondaryButton type="button" className="h-10" onClick={() => openPreview(`${route('finance.petty-cash-vouchers.attachments.download', { uuid: editingVoucherUuid, attachmentUuid: attachment.uuid })}?disposition=inline`, attachment.original_name)}>
                                                        Preview
                                                    </SecondaryButton>
                                                    <SecondaryButton type="button" className="h-10" onClick={() => window.location.assign(route('finance.petty-cash-vouchers.attachments.download', { uuid: editingVoucherUuid, attachmentUuid: attachment.uuid }))}>
                                                        Download
                                                    </SecondaryButton>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton type="button" onClick={close} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">Cancel</SecondaryButton>
                            <PrimaryButton className="h-11 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700" disabled={processing}>
                                {processing && <Spinner size="sm" className="mr-2 text-white" />}
                                <span>{editingVoucherUuid ? 'Update Draft' : 'Create Draft'}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <Modal show={!!selectedRow} onClose={() => setSelectedRow(null)} maxWidth="xl">
                <div className="p-6">
                    <ModalHeader
                        title="Voucher details"
                        subtitle="Review the full petty cash voucher information."
                        onClose={() => setSelectedRow(null)}
                    />
                    {selectedRow && (
                        <div className="mt-4 max-h-[70vh] space-y-5 overflow-y-auto pr-1">
                            <section className="rounded-2xl border border-slate-200 p-4">
                                <div className="rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm">Basic Info</div>
                                <div className="mt-4 grid gap-4 md:grid-cols-2">
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Voucher No</div>
                                        <div className="mt-1 text-sm font-semibold text-slate-900">{selectedRow.voucher_no}</div>
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
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Payee</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.payee_name ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4">
                                        <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Reference No</div>
                                        <div className="mt-1 text-sm text-slate-900">{selectedRow.reference_no ?? '-'}</div>
                                    </div>
                                    <div className="rounded-xl border border-slate-200 p-4 md:col-span-2">
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

                                <div className="mt-4 rounded-xl border border-slate-200 p-4">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Expense Lines</div>
                                    <div className="mt-3 overflow-x-auto">
                                        <table className="min-w-full divide-y divide-slate-200 text-sm">
                                            <thead>
                                                <tr className="text-left text-slate-500">
                                                    <th className="pb-2 pr-4">Ledger</th>
                                                    <th className="pb-2 pr-4">Description</th>
                                                    <th className="pb-2">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {(selectedRow.lines ?? []).map((line, index) => (
                                                    <tr key={line.uuid ?? index}>
                                                        <td className="py-2 pr-4 text-slate-900">{line.expense_ledger_account_code ? `${line.expense_ledger_account_code} - ${toTitleCase(line.expense_ledger_name ?? '')}` : toTitleCase(line.expense_ledger_name ?? '') || '-'}</td>
                                                        <td className="py-2 pr-4 text-slate-700">{line.description ?? '-'}</td>
                                                        <td className="py-2 text-slate-900">{line.amount_formatted ?? '-'}</td>
                                                    </tr>
                                                ))}
                                                {(selectedRow.lines ?? []).length === 0 && (
                                                    <tr>
                                                        <td colSpan={3} className="py-3 text-slate-500">No expense lines found.</td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div className="mt-4 rounded-xl border border-slate-200 p-4">
                                    <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Attachments</div>
                                    <div className="mt-3 space-y-2">
                                        {(selectedRow.attachments ?? []).map((attachment) => (
                                            <div key={attachment.uuid} className="flex flex-col gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div className="text-sm font-semibold text-slate-900">{attachment.original_name}</div>
                                                    <div className="mt-1 text-xs text-slate-500">{attachment.created_at ?? '-'}</div>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    <SecondaryButton type="button" className="h-10" onClick={() => openPreview(`${route('finance.petty-cash-vouchers.attachments.download', { uuid: selectedRow.uuid, attachmentUuid: attachment.uuid })}?disposition=inline`, attachment.original_name)}>
                                                        Preview
                                                    </SecondaryButton>
                                                    <SecondaryButton type="button" className="h-10" onClick={() => window.location.assign(route('finance.petty-cash-vouchers.attachments.download', { uuid: selectedRow.uuid, attachmentUuid: attachment.uuid }))}>
                                                        Download
                                                    </SecondaryButton>
                                                </div>
                                            </div>
                                        ))}
                                        {(selectedRow.attachments ?? []).length === 0 && (
                                            <div className="text-sm text-slate-500">No attachments uploaded.</div>
                                        )}
                                    </div>
                                </div>
                            </section>
                        </div>
                    )}
                </div>
            </Modal>

            <Modal show={previewOpen} onClose={closePreview} maxWidth="4xl">
                <div className="p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-lg font-semibold text-slate-900">{previewTitle}</h2>
                        <SecondaryButton type="button" className="h-11" onClick={closePreview}>
                            Close
                        </SecondaryButton>
                    </div>

                    <div className="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                        {previewUrl ? <iframe title={previewTitle} src={previewUrl} className="h-[75vh] w-full" /> : null}
                    </div>
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
