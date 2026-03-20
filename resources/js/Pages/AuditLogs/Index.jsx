import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function AuditLogsIndex({ logs, filters, modelTypes, actions }) {
    const [q, setQ] = useState(filters?.q ?? '');
    const [modelType, setModelType] = useState(filters?.model_type ?? '');
    const [action, setAction] = useState(filters?.action ?? '');
    const [fromDate, setFromDate] = useState(filters?.from_date ?? '');
    const [toDate, setToDate] = useState(filters?.to_date ?? '');

    const [viewing, setViewing] = useState(null);

    const apply = (e) => {
        e.preventDefault();
        router.get(
            route('audit-logs.index'),
            {
                q: q || undefined,
                model_type: modelType || undefined,
                action: action || undefined,
                from_date: fromDate || undefined,
                to_date: toDate || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clear = () => {
        setQ('');
        setModelType('');
        setAction('');
        setFromDate('');
        setToDate('');
        router.get(route('audit-logs.index'), {}, { preserveState: true, replace: true });
    };

    const rows = useMemo(() => (logs?.data ?? []), [logs?.data]);

    return (
        <AuthenticatedLayout>
            <Head title="Audit Logs" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Audit logs</h1>
                        <p className="mt-1 text-sm text-slate-500">Track who changed what, and when.</p>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={apply} className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid w-full gap-3 lg:grid-cols-12">
                            <FloatingInput
                                id="audit_logs_q"
                                label="Search (description, user email)"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                hint=""
                                className="lg:col-span-4"
                            />

                            <FloatingInput
                                id="audit_logs_from_date"
                                label="From"
                                type="date"
                                value={fromDate}
                                onChange={(e) => setFromDate(e.target.value)}
                                hint=""
                                className="lg:col-span-2"
                            />

                            <FloatingInput
                                id="audit_logs_to_date"
                                label="To"
                                type="date"
                                value={toDate}
                                onChange={(e) => setToDate(e.target.value)}
                                hint=""
                                className="lg:col-span-2"
                            />

                            <div className="lg:col-span-2">
                                <FloatingInput
                                    id="audit_logs_model"
                                    label="Model"
                                    value={modelType}
                                    onChange={(e) => setModelType(e.target.value)}
                                    hint=""
                                    list="audit_logs_model_options"
                                />
                                <datalist id="audit_logs_model_options">
                                    {(modelTypes ?? []).map((t) => (
                                        <option key={t.value} value={t.value}>{t.label}</option>
                                    ))}
                                </datalist>
                            </div>

                            <div className="lg:col-span-2">
                                <FloatingInput
                                    id="audit_logs_action"
                                    label="Action"
                                    value={action}
                                    onChange={(e) => setAction(e.target.value)}
                                    hint=""
                                    list="audit_logs_action_options"
                                />
                                <datalist id="audit_logs_action_options">
                                    {(actions ?? []).map((a) => (
                                        <option key={a} value={a} />
                                    ))}
                                </datalist>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <PrimaryButton
                                type="submit"
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal bg-indigo-600 text-white hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-800"
                            >
                                Search
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={clear}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                            >
                                Clear
                            </SecondaryButton>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>Model</th>
                                        <th>Action</th>
                                        <th>Changed by</th>
                                        <th>Description</th>
                                        <th className="text-right">Changes</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.map((r, idx) => (
                                        <tr key={idx}>
                                            <td className="text-sm text-slate-700 whitespace-nowrap">{r.created_at ?? '-'}</td>
                                            <td className="text-sm font-semibold text-slate-900">{r.model ?? '-'}</td>
                                            <td className="text-sm">
                                                <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                                    {r.action ?? '-'}
                                                </span>
                                            </td>
                                            <td className="text-sm text-slate-700">{r.changed_by ?? '-'}</td>
                                            <td className="text-sm text-slate-700">{r.description ?? '-'}</td>
                                            <td className="text-right">
                                                <button
                                                    type="button"
                                                    onClick={() => setViewing(r)}
                                                    className="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                                >
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                    {rows.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-10 text-center text-sm text-slate-500">No audit logs found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={logs?.meta} />
                        <Pagination links={logs?.meta?.links ?? []} />
                    </div>
                </section>
            </div>

            <Modal show={!!viewing} onClose={() => setViewing(null)} maxWidth="2xl">
                <div className="flex max-h-[85vh] flex-col">
                    <div className="border-b border-slate-200 bg-white px-6 py-4">
                        <div className="flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <h2 className="truncate text-lg font-semibold text-slate-900">Changes</h2>
                                <p className="mt-1 text-sm text-slate-500">{viewing?.model ?? '-'} · {viewing?.action ?? '-'}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setViewing(null)}
                                className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Close
                            </button>
                        </div>
                    </div>

                    <div className="flex-1 overflow-auto px-6 py-5">
                        {(viewing?.changes ?? []).length === 0 ? (
                            <div className="text-sm text-slate-600">No changes recorded.</div>
                        ) : (
                            <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                                <table className="mis-table divide-y divide-slate-200">
                                    <thead>
                                        <tr>
                                            <th>Field</th>
                                            <th>From</th>
                                            <th>To</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {(viewing?.changes ?? []).map((c, idx) => (
                                            <tr key={idx} className="align-top">
                                                <td className="text-sm font-semibold text-slate-900">{c.field}</td>
                                                <td className="text-sm text-slate-700">{c.from}</td>
                                                <td className="text-sm text-slate-700">{c.to}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) {
        return null;
    }

    const isPrev = (label) => typeof label === 'string' && /previous/i.test(label);
    const isNext = (label) => typeof label === 'string' && /next/i.test(label);

    return (
        <div className="flex flex-wrap gap-2">
            {links.map((link, idx) => {
                const prev = isPrev(link.label);
                const next = isNext(link.label);

                const className = `inline-flex h-11 items-center justify-center rounded-lg px-4 text-sm font-semibold ${link.active
                    ? 'bg-indigo-600 text-white'
                    : link.url
                        ? 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                        : 'cursor-not-allowed border border-slate-100 bg-slate-50 text-slate-400'
                    }`;

                const label = prev ? 'Prev' : next ? 'Next' : link.label?.replace(/&laquo;|&raquo;/g, '') ?? '';

                return (
                    <button
                        key={idx}
                        type="button"
                        disabled={!link.url}
                        onClick={() => link.url && router.visit(link.url, { preserveState: true, preserveScroll: true })}
                        className={className}
                        dangerouslySetInnerHTML={{ __html: label }}
                    />
                );
            })}
        </div>
    );
}

function PaginationSummary({ meta }) {
    if (!meta) return null;
    return (
        <div className="text-sm text-slate-600">
            Showing <span className="font-semibold">{meta.from ?? 0}</span> to <span className="font-semibold">{meta.to ?? 0}</span> of{' '}
            <span className="font-semibold">{meta.total ?? 0}</span>
        </div>
    );
}
