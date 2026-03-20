import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function MarriagesIndex({ marriages, pagination, filters }) {
    const { auth } = usePage().props;
    const permissions = auth?.user?.permissions ?? [];
    const can = (permissionName) => Array.isArray(permissions) && permissions.includes(permissionName);

    const rows = Array.isArray(marriages) ? marriages : [];
    const p = pagination ?? {};
    const initial = filters ?? {};
    const [q, setQ] = useState(initial.q ?? '');
    const [from, setFrom] = useState(initial.from ?? '');
    const [to, setTo] = useState(initial.to ?? '');

    const queryParams = useMemo(() => {
        const params = {};
        if ((q ?? '').toString().trim() !== '') params.q = (q ?? '').toString().trim();
        if ((from ?? '').toString().trim() !== '') params.from = (from ?? '').toString().trim();
        if ((to ?? '').toString().trim() !== '') params.to = (to ?? '').toString().trim();
        return params;
    }, [q, from, to]);

    const applyFilters = (e) => {
        e?.preventDefault?.();
        router.get(route('marriages.index'), { ...queryParams, page: 1 }, { preserveScroll: true, replace: true });
    };

    const clearFilters = () => {
        setQ('');
        setFrom('');
        setTo('');
        router.get(route('marriages.index'), {}, { preserveScroll: true, replace: true });
    };

    const StatusBadge = ({ status }) => {
        const s = (status ?? '').toString();
        const base = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset';
        if (s === 'submitted') return <span className={`${base} bg-indigo-50 text-indigo-700 ring-indigo-200`}>Submitted</span>;
        if (s === 'approved') return <span className={`${base} bg-emerald-50 text-emerald-700 ring-emerald-200`}>Approved</span>;
        if (s === 'rejected') return <span className={`${base} bg-rose-50 text-rose-700 ring-rose-200`}>Rejected</span>;
        if (s === 'completed') return <span className={`${base} bg-slate-50 text-slate-700 ring-slate-200`}>Completed</span>;
        if (s === 'issued') return <span className={`${base} bg-slate-50 text-slate-700 ring-slate-200`}>Issued</span>;
        return <span className={`${base} bg-amber-50 text-amber-800 ring-amber-200`}>Draft</span>;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Marriage Requests" />

            <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
                <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Marriage Requests</h1>
                        <p className="mt-1 text-sm text-slate-600">Create, submit, and track Catholic marriage requests.</p>
                    </div>
                    {can('marriages.request.create') ? (
                        <PrimaryButton className="h-11 bg-indigo-700 hover:bg-indigo-800" onClick={() => router.get(route('marriages.create'))}>
                            <span className="inline-flex items-baseline gap-2">
                                <span className="text-base font-semibold leading-none">+</span>
                                <span className="text-sm font-semibold tracking-wide">MARRIAGE REQUEST</span>
                            </span>
                        </PrimaryButton>
                    ) : null}
                </div>

                <form onSubmit={applyFilters} className="mb-4 grid grid-cols-1 gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-12">
                    <div className="sm:col-span-3">
                        <label className="mb-1 block text-xs font-semibold text-slate-700" htmlFor="q">Search</label>
                        <input
                            id="q"
                            type="text"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            className="block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm shadow-sm"
                            placeholder="Search"
                        />
                    </div>
                    <div className="md:col-span-3">
                        <label className="mb-1 block text-xs font-semibold text-slate-700" htmlFor="from">From</label>
                        <input
                            id="from"
                            type="date"
                            value={from}
                            onChange={(e) => setFrom(e.target.value)}
                            className="block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm shadow-sm"
                        />
                    </div>
                    <div className="md:col-span-3">
                        <label className="mb-1 block text-xs font-semibold text-slate-700" htmlFor="to">To</label>
                        <input
                            id="to"
                            type="date"
                            value={to}
                            onChange={(e) => setTo(e.target.value)}
                            className="block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm shadow-sm"
                        />
                    </div>
                    <div className="md:col-span-3 flex flex-col items-stretch gap-2 md:flex-row md:flex-wrap md:items-end md:justify-end">
                        <SecondaryButton type="button" className="h-11" onClick={clearFilters}>
                            Clear
                        </SecondaryButton>
                        <PrimaryButton type="submit" className="h-11 bg-indigo-700 hover:bg-indigo-800">
                            Search
                        </PrimaryButton>
                    </div>
                </form>

                <div className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                    <div className="overflow-x-auto">
                        <table className="mis-table divide-y divide-slate-200">
                            <thead>
                                <tr>
                                    <th>Groom</th>
                                    <th>Bride</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th className="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-8 text-sm text-slate-600">No marriage requests found.</td>
                                    </tr>
                                ) : (
                                    rows.map((m) => {
                                        const brideName = m?.bride?.full_name ?? m?.bride_external?.full_name ?? '—';
                                        return (
                                            <tr key={m.uuid}>
                                                <td className="text-sm font-semibold text-slate-900">{m?.groom?.full_name ?? '—'}</td>
                                                <td className="text-sm font-semibold text-slate-900">{brideName}</td>
                                                <td className="text-sm"><StatusBadge status={m?.status} /></td>
                                                <td className="text-sm text-slate-600">{m?.created_at ?? ''}</td>
                                                <td className="text-right">
                                                    <SecondaryButton type="button" className="h-10" onClick={() => router.get(route('marriages.show', m.uuid))}>
                                                        View
                                                    </SecondaryButton>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-col gap-3 border-t border-slate-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div className="text-sm text-slate-600">
                            Total: {typeof p.total === 'number' ? p.total : rows.length}
                        </div>
                        <div className="flex items-center justify-end gap-2">
                            <SecondaryButton
                                type="button"
                                className="h-10"
                                disabled={!(p.current_page > 1)}
                                onClick={() => router.get(route('marriages.index'), { ...queryParams, page: (p.current_page ?? 1) - 1 }, { preserveScroll: true })}
                            >
                                Prev
                            </SecondaryButton>
                            <div className="text-sm text-slate-600">
                                Page {p.current_page ?? 1} of {p.last_page ?? 1}
                            </div>
                            <SecondaryButton
                                type="button"
                                className="h-10"
                                disabled={!(p.last_page > 1) || !((p.current_page ?? 1) < (p.last_page ?? 1))}
                                onClick={() => router.get(route('marriages.index'), { ...queryParams, page: (p.current_page ?? 1) + 1 }, { preserveScroll: true })}
                            >
                                Next
                            </SecondaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
