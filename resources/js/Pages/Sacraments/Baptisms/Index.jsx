import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import FloatingInput from '@/Components/FloatingInput';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function BaptismsIndex({ baptisms, filters }) {
    const [q, setQ] = useState(filters?.q ?? '');
    const [from, setFrom] = useState(filters?.from ?? '');
    const [to, setTo] = useState(filters?.to ?? '');
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const can = (permissionName) => Array.isArray(permissions) && permissions.includes(permissionName);

    const rows = useMemo(() => baptisms?.data ?? [], [baptisms]);

    const statusMeta = (status) => {
        const s = (status ?? '').toString().toLowerCase();
        if (s === 'draft') return { label: 'Draft', cls: 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' };
        if (s === 'submitted') return { label: 'Submitted', cls: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' };
        if (s === 'approved') return { label: 'Approved', cls: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' };
        if (s === 'completed') return { label: 'Completed', cls: 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200' };
        if (s === 'rejected') return { label: 'Rejected', cls: 'bg-rose-50 text-rose-800 ring-1 ring-rose-200' };
        if (s === 'scheduled') return { label: 'Scheduled', cls: 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200' };
        if (s === 'issued') return { label: 'Issued', cls: 'bg-indigo-50 text-indigo-800 ring-1 ring-indigo-200' };
        return { label: status ?? '—', cls: 'bg-slate-50 text-slate-700 ring-1 ring-slate-200' };
    };

    const submitSearch = (e) => {
        e.preventDefault();

        router.get(route('baptisms.index'), { q, from, to }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const clearSearch = () => {
        setQ('');
        setFrom('');
        setTo('');
        router.get(route('baptisms.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Baptisms" />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Baptism Requests</h1>
                        <p className="mt-1 text-sm text-slate-600">Requests from Jumuiya leadership for parish review, scheduling, and issuance.</p>
                    </div>
                    {can('baptisms.request.create') && (
                        <PrimaryButton
                            type="button"
                            className="h-11 bg-indigo-700 hover:bg-indigo-800"
                            onClick={() => router.get(route('baptisms.create'))}
                            title="Baptism Request"
                            aria-label="Baptism Request"
                        >
                            <span className="inline-flex items-center gap-2">
                                <svg className="h-5 w-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
                                </svg>
                                Baptism Request
                            </span>
                        </PrimaryButton>
                    )}
                </div>

                <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <form onSubmit={submitSearch} className="flex flex-col gap-3 md:flex-row md:items-end">
                        <div className="flex-1">
                            <label className="block text-xs font-semibold text-slate-600">Search</label>
                            <input
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Search by name or phone"
                                className="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-600">From</label>
                            <input
                                type="date"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                                className="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400 md:w-44"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-semibold text-slate-600">To</label>
                            <input
                                type="date"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                                className="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm outline-none focus:border-slate-400 md:w-44"
                            />
                        </div>
                        <div className="flex items-center gap-2 md:pl-2">
                            <SecondaryButton type="button" className="h-11" onClick={clearSearch}>
                                Clear
                            </SecondaryButton>
                            <PrimaryButton type="submit" className="h-11 bg-slate-900 hover:bg-slate-800">
                                Search
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div className="mt-4 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Child</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Jumuiya</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Status</th>
                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Created</th>
                                    <th className="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-8 text-center text-sm text-slate-500">No baptism requests found.</td>
                                    </tr>
                                ) : (
                                    rows.map((b) => {
                                        const childName = b?.member
                                            ? [b.member.first_name, b.member.middle_name, b.member.last_name].filter(Boolean).join(' ')
                                            : '—';

                                        const meta = statusMeta(b?.status);
                                        const openLabel = (b?.status ?? '').toString().toLowerCase() === 'draft' ? 'Continue' : 'Open';

                                        return (
                                            <tr key={b.uuid} className="cursor-pointer hover:bg-slate-50" onClick={() => router.get(route('baptisms.show', b.uuid))}>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">{childName}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-700">{b?.origin_jumuiya?.name ?? '—'}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm">
                                                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${meta.cls}`}>{meta.label}</span>
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{b?.created_at ?? '—'}</td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right text-sm">
                                                    <button
                                                        type="button"
                                                        className="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            e.stopPropagation();
                                                            router.get(route('baptisms.show', b.uuid));
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

                    {Array.isArray(baptisms?.links) && baptisms.links.length > 0 && (
                        <div className="flex flex-col gap-2 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="text-xs text-slate-500">Page {baptisms.current_page} of {baptisms.last_page}</div>
                            <div className="flex flex-wrap items-center justify-end gap-1">
                                {baptisms.links.map((l) => {
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
