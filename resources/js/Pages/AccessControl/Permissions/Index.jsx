import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function AccessControlPermissionsIndex({ permissions, filters, modules }) {
    const rows = permissions?.data ?? [];

    const [q, setQ] = useState(filters?.q ?? '');
    const [module, setModule] = useState(filters?.module ?? '');

    const applySearch = (e) => {
        e.preventDefault();
        router.get(
            route('access-control.permissions.index'),
            {
                q: q || undefined,
                module: module || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clearSearch = () => {
        setQ('');
        setModule('');
        router.get(route('access-control.permissions.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout header="Access Control">
            <Head title="Access Control - Permissions" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Permissions</h1>
                        <p className="mt-1 text-sm text-slate-500">Browse permissions. Search is server-side.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('access-control.roles.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Back to roles
                        </Link>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applySearch} className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div className="grid w-full gap-3 lg:grid-cols-12">
                            <FloatingInput
                                id="perm_q"
                                label="Search (name, module, display)"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                className="lg:col-span-7"
                            />

                            <FloatingSelect
                                id="perm_module"
                                label="Module"
                                value={module}
                                onChange={(e) => setModule(e.target.value)}
                                className="lg:col-span-5"
                            >
                                <option value="">All modules</option>
                                {(modules ?? []).map((m) => (
                                    <option key={m} value={m}>{m}</option>
                                ))}
                            </FloatingSelect>
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
                                onClick={clearSearch}
                                className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal"
                            >
                                Clear
                            </SecondaryButton>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="w-16 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">#</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Module</th>
                                        <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Permission</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 bg-white">
                                    {rows.map((p, idx) => (
                                        <tr key={p.name} className={`${idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'} hover:bg-indigo-50/40 transition`}>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-700">{(permissions?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{p.module ?? '-'}</td>
                                            <td className="px-4 py-3">
                                                <div className="text-sm font-semibold text-slate-900">{p.display_name ?? p.name}</div>
                                                <div className="text-xs text-slate-500">{p.name}</div>
                                            </td>
                                        </tr>
                                    ))}
                                    {rows.length === 0 && (
                                        <tr>
                                            <td colSpan={3} className="px-4 py-10 text-center text-sm text-slate-500">No permissions found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={permissions?.meta} />
                        <Pagination links={permissions?.meta?.links ?? permissions?.links ?? []} />
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function PaginationSummary({ meta }) {
    if (!meta) return null;

    const from = meta.from ?? 0;
    const to = meta.to ?? 0;
    const total = meta.total ?? 0;

    return (
        <div className="text-sm text-slate-500">
            Showing <span className="font-semibold text-slate-700">{from}</span> to{' '}
            <span className="font-semibold text-slate-700">{to}</span> of{' '}
            <span className="font-semibold text-slate-700">{total}</span>
        </div>
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

                const content = prev ? (
                    <span className="inline-flex items-center gap-2">
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Previous</span>
                    </span>
                ) : next ? (
                    <span className="inline-flex items-center gap-2">
                        <span>Next</span>
                        <svg className="h-4 w-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>
                ) : (
                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                );

                if (!link.url) {
                    return (
                        <span
                            key={idx}
                            className={className}
                        >
                            {content}
                        </span>
                    );
                }

                return (
                    <Link
                        key={idx}
                        href={link.url}
                        preserveScroll
                        className={className}
                    >
                        {content}
                    </Link>
                );
            })}
        </div>
    );
}
