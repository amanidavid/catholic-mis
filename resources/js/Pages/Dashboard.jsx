import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function Dashboard({ cards = [], filters = {} }) {
    const fromProp = typeof filters?.from === 'string' ? filters.from : '';
    const toProp = typeof filters?.to === 'string' ? filters.to : '';

    const [from, setFrom] = useState(fromProp);
    const [to, setTo] = useState(toProp);

    useEffect(() => {
        setFrom(fromProp);
        setTo(toProp);
    }, [fromProp, toProp]);

    return (
        <AuthenticatedLayout
            header="Dashboard"
        >
            <Head title="Dashboard" />

            <div className="space-y-8">
                <div className="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-semibold text-slate-900">Sacraments summary period</p>
                            <p className="text-xs text-slate-500">Dashboard cards update based on this range</p>
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div>
                                <label className="block text-xs font-semibold text-slate-600">From</label>
                                <input
                                    type="date"
                                    value={from}
                                    onChange={(e) => {
                                        const nextFrom = e.target.value;
                                        setFrom(nextFrom);
                                        router.get(route('dashboard'), { from: nextFrom, to }, { preserveState: true, replace: true });
                                    }}
                                    className="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-600">To</label>
                                <input
                                    type="date"
                                    value={to}
                                    onChange={(e) => {
                                        const nextTo = e.target.value;
                                        setTo(nextTo);
                                        router.get(route('dashboard'), { from, to: nextTo }, { preserveState: true, replace: true });
                                    }}
                                    className="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                />
                            </div>
                            <button
                                type="button"
                                onClick={() => {
                                    setFrom('');
                                    setTo('');
                                    router.get(route('dashboard'), {}, { preserveState: true, replace: true });
                                }}
                                className="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <MetricCard
                            key={card.key}
                            label={card.label}
                            value={card.value}
                            href={card.href}
                            breakdown={card.breakdown}
                        />
                    ))}
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex items-center justify-between">
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900">
                                Quick Actions
                            </h3>
                            <p className="text-sm text-slate-500">
                                Common actions you can take
                            </p>
                        </div>
                        <Link
                            href={route('profile.edit')}
                            className="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
                        >
                            Profile
                        </Link>
                    </div>

                    <div className="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            href={route('profile.edit')}
                            className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-800 hover:border-indigo-200 hover:bg-indigo-50"
                        >
                            Profile
                            <span className="text-xs text-indigo-600">Edit</span>
                        </Link>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function MetricCard({ label, value, href, breakdown }) {
    const items = normalizeBreakdown(breakdown);

    return (
        <Link
            href={href}
            className="group relative overflow-hidden rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:-translate-y-0.5 hover:shadow-md"
        >
            <div className="flex items-start justify-between gap-4">
                <div>
                    <p className="text-sm font-medium text-slate-500">{label}</p>
                    <p className="mt-2 text-3xl font-bold tracking-tight text-slate-900">{formatNumber(value)}</p>
                    {items.length > 0 ? (
                        <div className="mt-2 flex flex-wrap gap-1.5">
                            {items.slice(0, 4).map((it) => (
                                <span
                                    key={it.key}
                                    className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ${statusChipClass(it.key)}`}
                                >
                                    <span className="text-slate-500">{it.label}:</span>
                                    <span className="text-slate-900">{formatNumber(it.value)}</span>
                                </span>
                            ))}
                        </div>
                    ) : (
                        <p className="mt-1 text-xs font-semibold text-indigo-600 opacity-0 transition group-hover:opacity-100">
                            View details
                        </p>
                    )}
                </div>

                <div className="mt-1 flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                    <svg viewBox="0 0 24 24" fill="none" className="h-5 w-5" aria-hidden="true">
                        <path
                            d="M7 17L17 7"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                        <path
                            d="M9 7h8v8"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                    </svg>
                </div>
            </div>

            <div className="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-indigo-50/70 to-transparent opacity-0 transition group-hover:opacity-100" />
        </Link>
    );
}

function formatNumber(value) {
    const n = typeof value === 'number' ? value : Number(value);
    if (Number.isNaN(n)) return String(value ?? '');
    return new Intl.NumberFormat().format(n);
}

function normalizeBreakdown(breakdown) {
    if (!breakdown || typeof breakdown !== 'object') return [];

    const order = ['draft', 'submitted', 'approved', 'rejected', 'completed', 'issued'];
    const labels = {
        draft: 'Draft',
        submitted: 'Submitted',
        approved: 'Approved',
        rejected: 'Rejected',
        completed: 'Completed',
        issued: 'Issued',
    };

    const entries = Object.entries(breakdown)
        .filter(([k, v]) => k && v !== null && v !== undefined)
        .map(([k, v]) => ({
            key: k,
            label: labels[k] ?? k,
            value: typeof v === 'number' ? v : Number(v),
        }))
        .filter((e) => !Number.isNaN(e.value) && e.value > 0);

    entries.sort((a, b) => {
        const ai = order.indexOf(a.key);
        const bi = order.indexOf(b.key);
        return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
    });

    return entries;
}

function statusChipClass(status) {
    switch (status) {
        case 'draft':
            return 'bg-slate-50 text-slate-700 ring-slate-200';
        case 'submitted':
            return 'bg-amber-50 text-amber-800 ring-amber-200';
        case 'approved':
            return 'bg-sky-50 text-sky-800 ring-sky-200';
        case 'rejected':
            return 'bg-rose-50 text-rose-800 ring-rose-200';
        case 'completed':
            return 'bg-emerald-50 text-emerald-800 ring-emerald-200';
        case 'issued':
            return 'bg-violet-50 text-violet-800 ring-violet-200';
        default:
            return 'bg-slate-50 text-slate-700 ring-slate-200';
    }
}
