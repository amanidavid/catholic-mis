import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function CommunionsIndex({ registrations, filters, cycles }) {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const can = (permissionName) => Array.isArray(permissions) && permissions.includes(permissionName);

    const [q, setQ] = useState(filters?.q ?? '');
    const [cycleUuid, setCycleUuid] = useState(filters?.cycle_uuid ?? '');
    const [from, setFrom] = useState(filters?.from ?? '');
    const [to, setTo] = useState(filters?.to ?? '');

    const rows = useMemo(() => registrations?.data ?? [], [registrations]);

    const statusMeta = (status) => {
        const s = (status ?? '').toString().toLowerCase();
        if (s === 'draft') return { label: 'Draft', cls: 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' };
        if (s === 'submitted') return { label: 'Submitted', cls: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' };
        if (s === 'approved') return { label: 'Approved', cls: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' };
        if (s === 'completed') return { label: 'Completed', cls: 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200' };
        if (s === 'rejected') return { label: 'Rejected', cls: 'bg-rose-50 text-rose-800 ring-1 ring-rose-200' };
        if (s === 'issued') return { label: 'Issued', cls: 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200' };
        return { label: status ?? '—', cls: 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' };
    };

    const submitSearch = (e) => {
        e.preventDefault();
        router.get(route('communions.index'), { q, cycle_uuid: cycleUuid, from, to }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        setCycleUuid('');
        setFrom('');
        setTo('');
        router.get(route('communions.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Communions" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">First Communion Registrations</h1>
                        <p className="mt-1 text-sm text-slate-600">Register candidates, upload required documents, then submit for parish approval.</p>
                    </div>
                    {can('communions.register') && (
                        <PrimaryButton
                            type="button"
                            className="h-11 bg-indigo-700 hover:bg-indigo-800"
                            onClick={() => router.get(route('communions.create'))}
                        >
                            <span className="inline-flex items-center gap-2">
                                <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
                                </svg>
                                New registration
                            </span>
                        </PrimaryButton>
                    )}
                </div>

                <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <form onSubmit={submitSearch} className="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                        <div className="md:col-span-4">
                            <label className="block text-xs font-semibold text-slate-600">Search</label>
                            <input
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Search by name or phone"
                                className="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400"
                            />
                        </div>
                        <div className="md:col-span-3">
                            <label className="block text-xs font-semibold text-slate-600">Cycle</label>
                            <select
                                value={cycleUuid}
                                onChange={(e) => setCycleUuid(e.target.value)}
                                className="mt-1 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none focus:border-slate-400"
                            >
                                <option value="">All cycles</option>
                                {(cycles ?? []).map((c) => {
                                    const label = `${c?.name ?? '—'}${c?.status ? ` (${c.status})` : ''}`;
                                    return (
                                        <option key={c.uuid} value={c.uuid}>{label}</option>
                                    );
                                })}
                            </select>
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-xs font-semibold text-slate-600">From</label>
                            <input
                                type="date"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                                className="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400"
                            />
                        </div>
                        <div className="md:col-span-2">
                            <label className="block text-xs font-semibold text-slate-600">To</label>
                            <input
                                type="date"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                                className="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2 md:col-span-12 md:flex md:justify-end">
                            <SecondaryButton type="button" className="h-11 w-full md:w-auto md:min-w-28" onClick={clearSearch}>
                                Clear
                            </SecondaryButton>
                            <PrimaryButton type="submit" className="h-11 w-full md:w-auto md:min-w-28">
                                Search
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div className="mt-4 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="overflow-x-auto">
                        <table className="mis-table divide-y divide-slate-200">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Cycle</th>
                                    <th>Jumuiya</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th className="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-sm text-slate-500">No registrations found.</td>
                                    </tr>
                                ) : (
                                    rows.map((r) => {
                                        const candidateName = r?.member?.full_name ?? '—';
                                        const meta = statusMeta(r?.status);
                                        const openLabel = (r?.status ?? '').toString().toLowerCase() === 'draft' ? 'Continue' : 'Open';

                                        return (
                                            <tr
                                                key={r.uuid}
                                                className="cursor-pointer"
                                                onClick={() => router.get(route('communions.show', r.uuid))}
                                            >
                                                <td className="whitespace-nowrap font-medium text-slate-900">{candidateName}</td>
                                                <td className="whitespace-nowrap">{r?.cycle?.name ?? '—'}</td>
                                                <td className="whitespace-nowrap">{r?.origin_jumuiya?.name ?? '—'}</td>
                                                <td className="whitespace-nowrap">
                                                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${meta.cls}`}>{meta.label}</span>
                                                    {r?.is_transfer ? (
                                                        <span className="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">Transfer</span>
                                                    ) : null}
                                                </td>
                                                <td className="whitespace-nowrap text-slate-600">{r?.created_at ?? '—'}</td>
                                                <td className="whitespace-nowrap text-right">
                                                    <button
                                                        type="button"
                                                        className="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            e.stopPropagation();
                                                            router.get(route('communions.show', r.uuid));
                                                        }}
                                                    >
                                                        {openLabel}
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {Array.isArray(registrations?.links) && registrations.links.length > 0 && (
                        <div className="flex flex-col gap-2 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="text-xs text-slate-500">Page {registrations.current_page} of {registrations.last_page}</div>
                            <div className="flex flex-wrap items-center justify-end gap-1">
                                {registrations.links.map((l) => {
                                    const label = (l.label ?? '').toString().replace('&laquo;', '«').replace('&raquo;', '»');
                                    const isActive = !!l.active;
                                    const isDisabled = !l.url;

                                    return (
                                        <button
                                            key={`${label}-${l.url ?? 'disabled'}`}
                                            type="button"
                                            disabled={isDisabled}
                                            onClick={() => {
                                                if (l.url) router.get(l.url, {}, { preserveState: true, preserveScroll: true, replace: true });
                                            }}
                                            className={
                                                `h-9 min-w-9 rounded-lg border px-3 text-xs font-semibold ` +
                                                (isActive
                                                    ? 'border-slate-900 bg-slate-900 text-white'
                                                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50') +
                                                (isDisabled ? ' opacity-50' : '')
                                            }
                                            aria-current={isActive ? 'page' : undefined}
                                        >
                                            {label}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
