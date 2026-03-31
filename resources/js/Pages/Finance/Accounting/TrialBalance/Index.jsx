import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import SecondaryButton from '@/Components/SecondaryButton';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function TrialBalanceIndex({ rows, totals, filters }) {
    const [asAt, setAsAt] = useState(filters?.as_at ?? '');
    const [perPage, setPerPage] = useState(String(filters?.per_page ?? 50));

    const tableRows = useMemo(() => rows?.data ?? [], [rows?.data]);

    const apply = (e) => {
        e.preventDefault();
        router.get(
            route('finance.trial-balance.index'),
            {
                as_at: asAt || undefined,
                per_page: perPage || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clear = () => {
        const today = new Date().toISOString().slice(0, 10);
        setAsAt(today);
        setPerPage('50');
        router.get(
            route('finance.trial-balance.index'),
            { as_at: today, per_page: 50 },
            { preserveState: true, replace: true },
        );
    };

    const visitPage = (url) => {
        if (!url) return;
        router.visit(url, { preserveState: true, replace: true });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Trial Balance" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Trial Balance</h1>
                        <p className="mt-1 text-sm text-slate-500">As-at summary of ledger balances from posted general ledger entries, optimized for large datasets.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('finance.general-ledger.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            General Ledger
                        </Link>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={apply} className="grid gap-3 lg:grid-cols-12 lg:items-end">
                        <FloatingInput
                            id="trial_balance_as_at"
                            label="As at date"
                            type="date"
                            value={asAt}
                            onChange={(e) => setAsAt(e.target.value)}
                            className="lg:col-span-4"
                        />
                        <FloatingSelect
                            id="trial_balance_per_page"
                            label="Rows"
                            value={perPage}
                            onChange={(e) => setPerPage(e.target.value)}
                            className="lg:col-span-3"
                        >
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </FloatingSelect>
                        <div className="flex items-center gap-2 lg:col-span-5 lg:justify-end">
                            <button type="submit" className="h-11 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">
                                Load Report
                            </button>
                            <SecondaryButton type="button" onClick={clear} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                Reset
                            </SecondaryButton>
                        </div>
                    </form>

                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">As at</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{filters?.as_at ?? '-'}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Debit</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{formatAmount(totals?.debit ?? 0)}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Credit</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{formatAmount(totals?.credit ?? 0)}</div>
                        </div>
                    </div>

                    <div className="mt-6 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Ledger Name</th>
                                        <th>Balance Type</th>
                                        <th className="w-40">Debit</th>
                                        <th className="w-40">Credit</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {tableRows.map((row) => (
                                        <tr key={row.ledger_uuid} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 text-sm font-medium text-slate-900">{toTitleCase(row.ledger_name ?? '')}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.balance_type}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.debit_balance_formatted}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{row.credit_balance_formatted}</td>
                                        </tr>
                                    ))}
                                    {tableRows.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-10 text-center text-sm text-slate-500">No trial balance rows found for the selected date.</td>
                                        </tr>
                                    )}
                                </tbody>
                                <tfoot className="bg-slate-50">
                                    <tr>
                                        <td className="px-4 py-3 text-sm font-semibold text-slate-900" colSpan={2}>Totals</td>
                                        <td className="px-4 py-3 text-sm font-semibold text-slate-900">{formatAmount(totals?.debit ?? 0)}</td>
                                        <td className="px-4 py-3 text-sm font-semibold text-slate-900">{formatAmount(totals?.credit ?? 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="text-sm text-slate-600">
                            Cursor pagination is enabled for better performance on large finance datasets.
                        </div>
                        <div className="flex items-center gap-2">
                            <button
                                type="button"
                                disabled={!rows?.prev_page_url}
                                onClick={() => visitPage(rows?.prev_page_url)}
                                className={`rounded-lg px-4 py-2 text-sm font-semibold ${rows?.prev_page_url ? 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' : 'bg-slate-100 text-slate-400'}`}
                            >
                                Previous
                            </button>
                            <button
                                type="button"
                                disabled={!rows?.next_page_url}
                                onClick={() => visitPage(rows?.next_page_url)}
                                className={`rounded-lg px-4 py-2 text-sm font-semibold ${rows?.next_page_url ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-slate-100 text-slate-400'}`}
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}

function formatAmount(value) {
    const numeric = parseFloat(value ?? 0);

    return numeric.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}
