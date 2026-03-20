import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo } from 'react';

export default function ConfirmationCyclesIndex({ cycles }) {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const canManage = Array.isArray(permissions) && permissions.includes('confirmations.cycles.manage');

    const rows = useMemo(() => cycles?.data ?? [], [cycles]);

    const fmtDate = (v) => {
        if (!v) return '—';
        const s = v.toString();
        return s.length >= 10 ? s.slice(0, 10) : s;
    };

    const statusMeta = (status) => {
        const s = (status ?? '').toString();
        const base = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset';
        if (s === 'open') return <span className={`${base} bg-emerald-50 text-emerald-700 ring-emerald-200`}>Open</span>;
        if (s === 'closed') return <span className={`${base} bg-rose-50 text-rose-700 ring-rose-200`}>Closed</span>;
        if (s === 'archived') return <span className={`${base} bg-slate-100 text-slate-700 ring-slate-200`}>Archived</span>;
        return <span className={`${base} bg-amber-50 text-amber-800 ring-amber-200`}>Draft</span>;
    };

    const setStatus = (cycleUuid, status) => {
        router.post(route('confirmations.cycles.status', cycleUuid), { status }, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Confirmation Cycles" />

            <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Confirmation Cycles</h1>
                        <p className="mt-1 text-sm text-slate-600">Control registration windows for Confirmation.</p>
                    </div>
                    {canManage ? (
                        <PrimaryButton type="button" className="h-11 bg-indigo-700 hover:bg-indigo-800" onClick={() => router.get(route('confirmations.cycles.create'))}>
                            <span className="inline-flex items-center gap-2">
                                <span className="text-base font-semibold leading-none">+</span>
                                New cycle
                            </span>
                        </PrimaryButton>
                    ) : null}
                </div>

                <div className="rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="overflow-x-auto">
                        <div className="overflow-hidden rounded-2xl">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Opens</th>
                                        <th>Closes</th>
                                        <th>Late closes</th>
                                        <th className="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-200">
                                    {rows.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-8 text-center text-sm text-slate-500">No cycles found.</td>
                                        </tr>
                                    ) : (
                                        rows.map((c, idx) => (
                                            <tr key={c.uuid} className={`${idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-900">{c.name}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">{statusMeta(c.status)}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{fmtDate(c.registration_opens_at)}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{fmtDate(c.registration_closes_at)}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{fmtDate(c.late_registration_closes_at)}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right">
                                                    <div className="flex flex-wrap items-center justify-end gap-2">
                                                        <button
                                                            type="button"
                                                            onClick={() => router.get(route('confirmations.cycles.edit', c.uuid))}
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100"
                                                            title="Edit"
                                                        >
                                                            <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 3.487a2.25 2.25 0 013.182 3.182L8.25 18.463 3 19.5l1.037-5.25L16.862 3.487z" />
                                                            </svg>
                                                        </button>
                                                        {canManage ? (
                                                            <>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setStatus(c.uuid, 'open')}
                                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                                                    title="Open"
                                                                >
                                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
                                                                    </svg>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setStatus(c.uuid, 'closed')}
                                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100"
                                                                    title="Close"
                                                                >
                                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setStatus(c.uuid, 'archived')}
                                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100"
                                                                    title="Archive"
                                                                >
                                                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M20 7H4m16 0l-1 14H5L4 7m16 0l-2-3H6L4 7" />
                                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M10 11h4" />
                                                                    </svg>
                                                                </button>
                                                            </>
                                                        ) : null}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {Array.isArray(cycles?.links) && cycles.links.length > 0 ? (
                        <div className="flex flex-col gap-2 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="text-xs text-slate-500">Page {cycles.current_page} of {cycles.last_page}</div>
                            <div className="flex flex-wrap items-center justify-end gap-1">
                                {cycles.links.map((l) => {
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
                    ) : null}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
