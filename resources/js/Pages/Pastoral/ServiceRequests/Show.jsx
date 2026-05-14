import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function ServiceRequestShow({ record, events }) {
    const families = record?.families ?? [];

    return (
        <AuthenticatedLayout>
            <Head title="Service Request Details" />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Service Request Details</h1>
                            <p className="mt-1 text-sm text-slate-500">Status: <span className="font-semibold text-slate-700">{record?.status ?? '-'}</span></p>
                        </div>
                        <Link href={route('pastoral.service-requests.index')} className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</Link>
                    </div>

                    <div className="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                        <Meta label="Jumuiya" value={record?.jumuiya_name} />
                        <Meta label="Requested By" value={record?.requested_by_member_name} />
                        <Meta label="Request Date" value={record?.request_date} />
                        <Meta label="Preferred Date" value={record?.preferred_service_date} />
                        <Meta label="Scheduled Date" value={record?.scheduled_service_date} />
                        <Meta label="Assigned To" value={record?.assigned_to_user_name} />
                        <Meta label="Urgency" value={record?.urgency} />
                    </div>
                    {record?.notes && <p className="mt-3 text-sm text-slate-700">{record.notes}</p>}
                    {record?.cancel_reason && (
                        <p className="mt-2 text-sm font-semibold text-rose-700">Cancellation reason: {record.cancel_reason}</p>
                    )}
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 space-y-4">
                    <h2 className="text-lg font-semibold text-slate-900">Families and Items</h2>
                    {families.length === 0 ? (
                        <div className="text-sm text-slate-500">No family rows found.</div>
                    ) : families.map((family) => (
                        <div key={family.uuid} className="rounded-lg border border-slate-200 p-4">
                            <div className="font-semibold text-slate-900">{family.family_name || '-'}</div>
                            {family.family_notes && <div className="mt-1 text-sm text-slate-600">{family.family_notes}</div>}

                            <div className="mt-3 overflow-x-auto">
                                <table className="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead className="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th className="px-3 py-2">Category</th>
                                            <th className="px-3 py-2">Target Member</th>
                                            <th className="px-3 py-2">Description</th>
                                            <th className="px-3 py-2">Requested For</th>
                                            <th className="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {(family.items ?? []).map((item) => (
                                            <tr key={item.uuid}>
                                                <td className="px-3 py-2 text-slate-700">{item.service_category_name || '-'}</td>
                                                <td className="px-3 py-2 text-slate-700">{item.target_member_name || '-'}</td>
                                                <td className="px-3 py-2 text-slate-700">{item.description || '-'}</td>
                                                <td className="px-3 py-2 text-slate-700">{item.requested_for_date || '-'}</td>
                                                <td className="px-3 py-2 text-slate-700">{item.status || '-'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ))}
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <h2 className="text-lg font-semibold text-slate-900">Workflow Events</h2>
                    <div className="mt-3 space-y-2">
                        {(events ?? []).length === 0 ? (
                            <div className="text-sm text-slate-500">No events recorded yet.</div>
                        ) : (events ?? []).map((event) => (
                            <div key={event.uuid} className="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <div className="font-semibold text-slate-900">{event.action}</div>
                                <div className="text-xs text-slate-500">{event.performed_at} • {event.performed_by || '-'}</div>
                                <div className="text-xs text-slate-500">{event.old_status || '-'} → {event.new_status || '-'}</div>
                                {event.notes && <div className="mt-1 text-sm text-slate-700">{event.notes}</div>}
                            </div>
                        ))}
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function Meta({ label, value }) {
    return (
        <div className="rounded-lg border border-slate-200 p-3">
            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</div>
            <div className="mt-1 text-sm font-semibold text-slate-900">{value || '-'}</div>
        </div>
    );
}
