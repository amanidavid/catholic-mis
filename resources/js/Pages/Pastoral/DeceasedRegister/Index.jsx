import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FloatingInput from '@/Components/FloatingInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function DeceasedRegisterIndex({ entries, filters, can }) {
    const rows = entries?.data ?? [];
    const canCreate = !!can?.create;
    const canUpdate = !!can?.update;
    const canDelete = !!can?.delete;

    const [q, setQ] = useState(filters?.q ?? '');
    const [dateFilter, setDateFilter] = useState(filters?.date_filter ?? 'all');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');
    const [deleteTarget, setDeleteTarget] = useState(null);

    const runSearch = (event) => {
        event?.preventDefault?.();
        router.get(route('pastoral.deceased-register.index'), {
            q: q || undefined,
            date_filter: dateFilter === 'all' ? undefined : dateFilter,
            date_from: dateFilter === 'custom' ? (dateFrom || undefined) : undefined,
            date_to: dateFilter === 'custom' ? (dateTo || undefined) : undefined,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const quickDate = (next) => {
        setDateFilter(next);
        router.get(route('pastoral.deceased-register.index'), {
            q: q || undefined,
            date_filter: next === 'all' ? undefined : next,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Order of Christian Funerals" />

            <div className="mx-auto max-w-7xl space-y-6">
                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Order of Christian Funerals</h1>
                            <p className="mt-1 text-sm text-slate-500">Record and maintain member death and funeral details.</p>
                        </div>
                        {canCreate && (
                            <Link
                                href={route('pastoral.deceased-register.create')}
                                className="inline-flex h-11 items-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700"
                            >
                                New Record
                            </Link>
                        )}
                    </div>
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 space-y-3">
                    <form onSubmit={runSearch} className="flex flex-col gap-3 lg:flex-row lg:items-end">
                        <div className="min-w-0 flex-1">
                            <FloatingInput id="deceased_q" label="Search member name" value={q} onChange={(e) => setQ(e.target.value)} />
                        </div>
                        <div className="w-full lg:w-44">
                            <label className="mb-1 block text-xs font-semibold text-slate-600">Date filter</label>
                            <select value={dateFilter} onChange={(e) => setDateFilter(e.target.value)} className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900">
                                <option value="all">All</option>
                                <option value="today">Today</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        {dateFilter === 'custom' && (
                            <>
                                <div className="w-full lg:w-44">
                                    <label className="mb-1 block text-xs font-semibold text-slate-600">From</label>
                                    <input type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm" />
                                </div>
                                <div className="w-full lg:w-44">
                                    <label className="mb-1 block text-xs font-semibold text-slate-600">To</label>
                                    <input type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm" />
                                </div>
                            </>
                        )}
                        <PrimaryButton type="submit" className="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold normal-case text-white hover:bg-indigo-700">Search</PrimaryButton>
                        <SecondaryButton type="button" onClick={() => quickDate('all')} className="h-11 rounded-lg px-5 text-sm font-semibold normal-case">Clear</SecondaryButton>
                    </form>
                    <div className="flex flex-wrap gap-2">
                        <QuickButton active={dateFilter === 'today'} onClick={() => quickDate('today')} label="Today" />
                        <QuickButton active={dateFilter === 'this_week'} onClick={() => quickDate('this_week')} label="This Week" />
                        <QuickButton active={dateFilter === 'this_month'} onClick={() => quickDate('this_month')} label="This Month" />
                    </div>

                    <div className="overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Member</th>
                                        <th>Family / Jumuiya</th>
                                        <th>Date of Death</th>
                                        <th>Place</th>
                                        {(canUpdate || canDelete) && <th className="text-right">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.length === 0 ? (
                                        <tr>
                                            <td colSpan={(canUpdate || canDelete) ? 5 : 4} className="px-4 py-10 text-center text-sm text-slate-500">No records found.</td>
                                        </tr>
                                    ) : rows.map((row, idx) => (
                                        <tr key={row.uuid} className={idx % 2 ? 'bg-slate-50/40' : 'bg-white'}>
                                            <td className="px-4 py-3">
                                                <div className="font-semibold text-slate-900">{row.member_name}</div>
                                                <div className="text-xs text-slate-500">{row.death_certificate_number || '-'}</div>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.family_name || '-'} / {row.jumuiya_name || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.date_of_death || '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.place_of_death || '-'}</td>
                                            {(canUpdate || canDelete) && (
                                                <td className="px-4 py-3 text-right">
                                                    <div className="inline-flex gap-2">
                                                        {canUpdate && (
                                                            <Link
                                                                href={route('pastoral.deceased-register.edit', row.uuid)}
                                                                className="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700"
                                                            >
                                                                Edit
                                                            </Link>
                                                        )}
                                                        {canDelete && <button type="button" onClick={() => setDeleteTarget(row)} className="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">Delete</button>}
                                                    </div>
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={entries?.meta} />
                        <Pagination links={entries?.meta?.links ?? entries?.links ?? []} />
                    </div>
                </section>
            </div>

            <ConfirmDialog
                open={!!deleteTarget}
                onClose={() => setDeleteTarget(null)}
                title="Delete deceased record"
                message={deleteTarget ? `Delete record for ${deleteTarget.member_name}?` : ''}
                confirmText="Delete"
                onConfirm={() => {
                    if (!deleteTarget?.uuid) return;
                    router.delete(route('pastoral.deceased-register.destroy', deleteTarget.uuid), {
                        preserveScroll: true,
                        onFinish: () => setDeleteTarget(null),
                    });
                }}
            />
        </AuthenticatedLayout>
    );
}

function QuickButton({ active, onClick, label }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={`h-9 rounded-lg px-3 text-xs font-semibold ${active ? 'bg-indigo-600 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'}`}
        >
            {label}
        </button>
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
