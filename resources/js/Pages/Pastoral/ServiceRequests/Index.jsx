import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import ModalHeader from '@/Components/ModalHeader';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function ServiceRequestsIndex({ requests, filters, categories, can, schedule_users }) {
    const rows = requests?.data ?? [];
    const scheduleUsers = Array.isArray(schedule_users) ? schedule_users : [];
    const [q, setQ] = useState(filters?.q ?? '');
    const [status, setStatus] = useState(filters?.status ?? 'all');
    const [categoryUuid, setCategoryUuid] = useState(filters?.category_uuid ?? '');
    const [dateFilter, setDateFilter] = useState(filters?.date_filter ?? 'all');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');
    const [cancelModal, setCancelModal] = useState({ open: false, uuid: '', reason: '' });
    const [scheduleModal, setScheduleModal] = useState({
        open: false,
        uuid: '',
        scheduled_service_date: '',
        assigned_to_user_uuid: '',
        notes: '',
    });

    const runSearch = (event) => {
        event?.preventDefault?.();

        router.get(route('pastoral.service-requests.index'), {
            q: q || undefined,
            status: status === 'all' ? undefined : status,
            category_uuid: categoryUuid || undefined,
            date_filter: dateFilter === 'all' ? undefined : dateFilter,
            date_from: dateFilter === 'custom' ? (dateFrom || undefined) : undefined,
            date_to: dateFilter === 'custom' ? (dateTo || undefined) : undefined,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        setStatus('all');
        setCategoryUuid('');
        setDateFilter('all');
        setDateFrom('');
        setDateTo('');
        router.get(route('pastoral.service-requests.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    const transition = (uuid, action, payload = {}) => {
        const routeMap = {
            submit: 'pastoral.service-requests.submit',
            schedule: 'pastoral.service-requests.schedule',
            progress: 'pastoral.service-requests.progress',
            complete: 'pastoral.service-requests.complete',
            cancel: 'pastoral.service-requests.cancel',
        };

        const routeName = routeMap[action];
        if (!routeName) return;

        router.post(route(routeName, uuid), payload, { preserveScroll: true });
    };

    const openCancelModal = (uuid) => {
        setCancelModal({ open: true, uuid, reason: '' });
    };

    const submitCancel = () => {
        if (!cancelModal.uuid || cancelModal.reason.trim() === '') return;

        transition(cancelModal.uuid, 'cancel', { cancel_reason: cancelModal.reason.trim() });
        setCancelModal({ open: false, uuid: '', reason: '' });
    };

    const openScheduleModal = (row) => {
        setScheduleModal({
            open: true,
            uuid: row.uuid,
            scheduled_service_date: row.scheduled_service_date ?? row.preferred_service_date ?? '',
            assigned_to_user_uuid: row.assigned_to_user_uuid ?? '',
            notes: '',
        });
    };

    const submitSchedule = () => {
        if (!scheduleModal.uuid || !scheduleModal.scheduled_service_date || !scheduleModal.assigned_to_user_uuid) return;

        transition(scheduleModal.uuid, 'schedule', {
            scheduled_service_date: scheduleModal.scheduled_service_date,
            assigned_to_user_uuid: scheduleModal.assigned_to_user_uuid,
            notes: scheduleModal.notes || undefined,
        });

        setScheduleModal({
            open: false,
            uuid: '',
            scheduled_service_date: '',
            assigned_to_user_uuid: '',
            notes: '',
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Service Requests" />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Service Requests</h1>
                            <p className="mt-1 text-sm text-slate-500">Track Jumuiya-origin service requests by status and date.</p>
                        </div>
                        {can?.create && (
                            <Link
                                href={route('pastoral.service-requests.create')}
                                className="inline-flex h-11 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                New Request
                            </Link>
                        )}
                    </div>
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={runSearch} className="grid gap-3 lg:grid-cols-12">
                        <FloatingInput id="requests_q" label="Search family" value={q} onChange={(e) => setQ(e.target.value)} className="lg:col-span-4" />

                        <FloatingSelect id="requests_status" label="Status" value={status} onChange={(e) => setStatus(e.target.value)} className="lg:col-span-2">
                            <option value="all">All</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="in_progress">In progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </FloatingSelect>

                        <FloatingSelect id="requests_category" label="Category" value={categoryUuid} onChange={(e) => setCategoryUuid(e.target.value)} className="lg:col-span-2">
                            <option value="">All</option>
                            {(categories?.data ?? []).map((category) => (
                                <option key={category.uuid} value={category.uuid}>{category.name}</option>
                            ))}
                        </FloatingSelect>

                        <FloatingSelect id="requests_date_filter" label="Date filter" value={dateFilter} onChange={(e) => setDateFilter(e.target.value)} className="lg:col-span-2">
                            <option value="all">All</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="custom">Custom</option>
                        </FloatingSelect>

                        <div className="flex items-end gap-2 lg:col-span-2">
                            <PrimaryButton type="submit" className="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold normal-case text-white hover:bg-indigo-700">Search</PrimaryButton>
                            <SecondaryButton type="button" onClick={clearSearch} className="h-11 rounded-lg px-5 text-sm font-semibold normal-case">Clear</SecondaryButton>
                        </div>

                        {dateFilter === 'custom' && (
                            <>
                                <FloatingInput id="requests_date_from" type="date" label="From" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="lg:col-span-2" />
                                <FloatingInput id="requests_date_to" type="date" label="To" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="lg:col-span-2" />
                            </>
                        )}
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Request Date</th>
                                        <th>Jumuiya</th>
                                        <th>Scheduled</th>
                                        <th>Status</th>
                                        <th>Families / Items</th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-10 text-center text-sm text-slate-500">No service requests found.</td>
                                        </tr>
                                    ) : rows.map((row, idx) => (
                                        <tr key={row.uuid} className={idx % 2 ? 'bg-slate-50/40' : 'bg-white'}>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.request_date || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.jumuiya_name || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">
                                                <div>{row.scheduled_service_date || '-'}</div>
                                                <div className="text-xs text-slate-500">{row.assigned_to_user_name || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm"><StatusBadge status={row.status} /></td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.families_count ?? 0} / {row.items_count ?? 0}</td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="inline-flex flex-wrap justify-end gap-2">
                                                    <Link href={route('pastoral.service-requests.show', row.uuid)} className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">View</Link>
                                                    {can?.submit && row.status === 'draft' && <button type="button" onClick={() => transition(row.uuid, 'submit')} className="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Submit</button>}
                                                    {can?.schedule && ['submitted', 'in_progress'].includes(row.status) && <button type="button" onClick={() => openScheduleModal(row)} className="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Schedule</button>}
                                                    {can?.progress && row.status === 'submitted' && <button type="button" onClick={() => transition(row.uuid, 'progress')} className="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Start</button>}
                                                    {can?.complete && row.status === 'in_progress' && <button type="button" onClick={() => transition(row.uuid, 'complete')} className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">Complete</button>}
                                                    {can?.cancel && !['completed', 'cancelled'].includes(row.status) && <button type="button" onClick={() => openCancelModal(row.uuid)} className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">Cancel</button>}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={requests?.meta} />
                        <Pagination links={requests?.meta?.links ?? requests?.links ?? []} />
                    </div>
                </section>

                <Modal show={cancelModal.open} onClose={() => setCancelModal({ open: false, uuid: '', reason: '' })} maxWidth="lg">
                    <div className="p-6">
                        <ModalHeader
                            title="Cancel Service Request"
                            subtitle="Provide a reason for cancellation."
                            onClose={() => setCancelModal({ open: false, uuid: '', reason: '' })}
                            showRequiredNote
                        />
                        <div className="mt-4 space-y-4">
                            <FloatingInput
                                id="cancel_reason"
                                label="Cancellation reason"
                                required
                                value={cancelModal.reason}
                                onChange={(e) => setCancelModal((prev) => ({ ...prev, reason: e.target.value }))}
                            />
                            <div className="flex justify-end gap-2">
                                <SecondaryButton type="button" onClick={() => setCancelModal({ open: false, uuid: '', reason: '' })} className="h-11 rounded-lg text-sm font-semibold normal-case">Close</SecondaryButton>
                                <PrimaryButton type="button" onClick={submitCancel} className="h-11 rounded-lg bg-rose-600 px-4 text-sm font-semibold normal-case text-white hover:bg-rose-700">Confirm Cancel</PrimaryButton>
                            </div>
                        </div>
                    </div>
                </Modal>

                <Modal show={scheduleModal.open} onClose={() => setScheduleModal({ open: false, uuid: '', scheduled_service_date: '', assigned_to_user_uuid: '', notes: '' })} maxWidth="lg">
                    <div className="p-6">
                        <ModalHeader
                            title="Schedule Service Request"
                            subtitle="Assign a date and responsible user."
                            onClose={() => setScheduleModal({ open: false, uuid: '', scheduled_service_date: '', assigned_to_user_uuid: '', notes: '' })}
                            showRequiredNote
                        />
                        <div className="mt-4 space-y-4">
                            <FloatingInput
                                id="scheduled_service_date"
                                type="date"
                                label="Scheduled service date"
                                required
                                value={scheduleModal.scheduled_service_date}
                                onChange={(e) => setScheduleModal((prev) => ({ ...prev, scheduled_service_date: e.target.value }))}
                            />
                            <div>
                                <label className="mb-1 block text-xs font-semibold text-slate-600">Assign to user</label>
                                <select
                                    value={scheduleModal.assigned_to_user_uuid}
                                    onChange={(e) => setScheduleModal((prev) => ({ ...prev, assigned_to_user_uuid: e.target.value }))}
                                    className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm"
                                >
                                    <option value="">Select user</option>
                                    {scheduleUsers.map((user) => (
                                        <option key={user.uuid} value={user.uuid}>{user.name}</option>
                                    ))}
                                </select>
                            </div>
                            <FloatingInput
                                id="schedule_notes"
                                label="Scheduling note (optional)"
                                value={scheduleModal.notes}
                                onChange={(e) => setScheduleModal((prev) => ({ ...prev, notes: e.target.value }))}
                            />
                            <div className="flex justify-end gap-2">
                                <SecondaryButton type="button" onClick={() => setScheduleModal({ open: false, uuid: '', scheduled_service_date: '', assigned_to_user_uuid: '', notes: '' })} className="h-11 rounded-lg text-sm font-semibold normal-case">Close</SecondaryButton>
                                <PrimaryButton type="button" onClick={submitSchedule} className="h-11 rounded-lg bg-indigo-600 px-4 text-sm font-semibold normal-case text-white hover:bg-indigo-700">Save Schedule</PrimaryButton>
                            </div>
                        </div>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}

function StatusBadge({ status }) {
    const map = {
        draft: 'bg-slate-100 text-slate-700 ring-slate-200',
        submitted: 'bg-amber-50 text-amber-700 ring-amber-200',
        in_progress: 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        cancelled: 'bg-rose-50 text-rose-700 ring-rose-200',
    };

    const cls = map[status] ?? 'bg-slate-100 text-slate-700 ring-slate-200';

    return <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${cls}`}>{status?.replace('_', ' ') ?? '-'}</span>;
}

function FloatingSelect({ id, label, value, onChange, className = '', children }) {
    return (
        <div className={`relative ${className}`}>
            <select
                id={id}
                value={value}
                onChange={onChange}
                className="peer h-11 w-full rounded-lg border border-slate-300 bg-white px-3 pt-5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500"
            >
                {children}
            </select>
            <label
                htmlFor={id}
                className="pointer-events-none absolute left-3 right-3 top-2.5 origin-[0] -translate-y-1 scale-75 truncate whitespace-nowrap text-xs font-semibold text-slate-500"
            >
                {label}
            </label>
        </div>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) return null;

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, idx) => (
                link.url ? (
                    <Link
                        key={idx}
                        href={link.url}
                        preserveScroll
                        className={`inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'}`}
                    >
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    </Link>
                ) : (
                    <span key={idx} className={`inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active ? 'bg-indigo-600 text-white' : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-400'}`}>
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    </span>
                )
            ))}
        </div>
    );
}

function PaginationSummary({ meta }) {
    if (!meta || typeof meta !== 'object') return null;
    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    return total
        ? <div className="text-sm text-slate-500">Showing <span className="font-semibold text-slate-700">{from}</span>-<span className="font-semibold text-slate-700">{to}</span> of <span className="font-semibold text-slate-700">{total}</span></div>
        : <div className="text-sm text-slate-500">Showing 0 results</div>;
}
