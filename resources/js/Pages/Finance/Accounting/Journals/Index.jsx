import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import SecondaryButton from '@/Components/SecondaryButton';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function JournalsIndex({ journals, ledgers, filters }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);

    const [q, setQ] = useState(filters?.q ?? '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');
    const perPage = filters?.per_page ?? 15;

    const applyFilters = (e) => {
        e.preventDefault();
        router.get(
            route('finance.journals.index'),
            {
                q: q || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                per_page: perPage,
            },
            { preserveState: true, replace: true },
        );
    };

    const clear = () => {
        setQ('');
        setDateFrom('');
        setDateTo('');
        router.get(route('finance.journals.index'), { per_page: perPage }, { preserveState: true, replace: true });
    };

    const tableRows = useMemo(() => journals?.data ?? [], [journals?.data]);

    return (
        <AuthenticatedLayout>
            <Head title="Journals" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Journals</h1>
                        <p className="mt-1 text-sm text-slate-500">Create journal entries and post them to the General Ledger.</p>
                    </div>
                    <div className="flex flex-wrap gap-2" />
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={applyFilters} className="grid gap-3 lg:grid-cols-12 lg:items-end">
                        <FloatingInput
                            id="journals_q"
                            label="Search (JV no / description)"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            className="lg:col-span-6"
                        />
                        <FloatingInput
                            id="journals_date_from"
                            label="Date from"
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            className="lg:col-span-2"
                        />
                        <FloatingInput
                            id="journals_date_to"
                            label="Date to"
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            className="lg:col-span-2"
                        />
                        <div className="flex items-center gap-2 lg:col-span-2 lg:justify-end">
                            <button type="submit" className="h-11 rounded-lg px-4 text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">
                                Search
                            </button>
                            <SecondaryButton type="button" onClick={clear} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Clear
                            </SecondaryButton>
                        </div>
                    </form>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th className="w-16">#</th>
                                        <th>Journal No</th>
                                        <th>Date</th>
                                        <th>Description</th>
                                        <th>Lines</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th className="w-24">View</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((j, idx) => (
                                        <tr key={j.uuid} className="hover:bg-emerald-50/40 transition">
                                            <td className="px-4 py-3 text-sm text-slate-600">{(journals?.meta?.from ?? 1) + idx}</td>
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">{j.journal_no}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{j.transaction_date}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(j.description ?? '')}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{j.lines_count ?? '-'}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{j.amount}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ${j.is_posted ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'}`}>
                                                    {j.is_posted ? 'Posted' : 'Unposted'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                <Link href={route('finance.journals.show', j.uuid)} className="inline-flex h-9 items-center rounded-lg ring-1 ring-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && (
                                        <tr>
                                            <td colSpan={8} className="px-4 py-10 text-center text-sm text-slate-500">No journals found.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={journals?.meta} />
                        <Pagination links={journals?.meta?.links ?? journals?.links ?? []} />
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function Pagination({ links }) {
    if (!Array.isArray(links) || links.length === 0) return null;

    return (
        <nav className="flex flex-wrap items-center justify-end gap-1">
            {links.map((link, idx) => (
                <button
                    key={idx}
                    type="button"
                    disabled={!link.url}
                    onClick={() => link.url && router.visit(link.url, { preserveState: true, replace: true })}
                    className={`min-w-[2.25rem] rounded-lg px-3 py-2 text-sm font-semibold transition ${link.active
                        ? 'bg-blue-600 text-white'
                        : link.url
                            ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'
                            : 'bg-slate-100 text-slate-400'
                        }`}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                />
            ))}
        </nav>
    );
}

function PaginationSummary({ meta }) {
    if (!meta || typeof meta !== 'object') return null;

    return (
        <div className="text-sm text-slate-600">
            Showing <span className="font-semibold text-slate-900">{meta.from ?? 0}</span> to{' '}
            <span className="font-semibold text-slate-900">{meta.to ?? 0}</span> of{' '}
            <span className="font-semibold text-slate-900">{meta.total ?? 0}</span>
        </div>
    );
}
