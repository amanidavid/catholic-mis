import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import SecondaryButton from '@/Components/SecondaryButton';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function TrialBalanceIndex({ rows, totals, filters }) {
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');
    const [perPage, setPerPage] = useState(String(filters?.per_page ?? 50));

    const tableRows = useMemo(() => rows?.data ?? [], [rows?.data]);
    const hasInvalidDateRange = Boolean(dateFrom && dateTo && dateFrom > dateTo);

    const apply = (e) => {
        e.preventDefault();
        if (hasInvalidDateRange) return;
        router.get(
            route('finance.trial-balance.index'),
            {
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                per_page: perPage || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const clear = () => {
        const today = new Date().toISOString().slice(0, 10);
        const monthStart = `${today.slice(0, 8)}01`;
        setDateFrom(monthStart);
        setDateTo(today);
        setPerPage('50');
        router.get(
            route('finance.trial-balance.index'),
            { date_from: monthStart, date_to: today, per_page: 50 },
            { preserveState: true, replace: true },
        );
    };

    const exportExcel = () => {
        if (hasInvalidDateRange) return;
        window.location.assign(route('finance.trial-balance.export', {
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }));
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
                        <button
                            type="button"
                            onClick={exportExcel}
                            className="inline-flex h-11 items-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700"
                        >
                            Export Excel
                        </button>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={apply} className="space-y-4">
                        <div className="grid gap-3 xl:grid-cols-12 xl:items-end">
                            <FloatingInput
                                id="trial_balance_date_from"
                                label="Date from"
                                type="date"
                                value={dateFrom}
                                max={dateTo || undefined}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="xl:col-span-4"
                            />
                            <FloatingInput
                                id="trial_balance_date_to"
                                label="Date to"
                                type="date"
                                value={dateTo}
                                min={dateFrom || undefined}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="xl:col-span-4"
                            />
                            <FloatingSelect
                                id="trial_balance_per_page"
                                label="Rows"
                                value={perPage}
                                onChange={(e) => setPerPage(e.target.value)}
                                className="xl:col-span-4"
                            >
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </FloatingSelect>
                        </div>

                        <div className="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-h-[1.25rem] text-sm font-medium text-red-600">
                                {hasInvalidDateRange ? 'Date from cannot be later than Date to.' : ''}
                            </div>
                            <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                                <button
                                    type="submit"
                                    disabled={hasInvalidDateRange}
                                    className={`inline-flex h-11 items-center rounded-lg px-4 text-sm font-semibold text-white ${hasInvalidDateRange ? 'cursor-not-allowed bg-slate-300' : 'bg-blue-600 hover:bg-blue-700'}`}
                                >
                                    Load Report
                                </button>
                                <SecondaryButton type="button" onClick={clear} className="h-11 rounded-lg text-sm font-semibold normal-case tracking-normal">
                                    Reset
                                </SecondaryButton>
                            </div>
                        </div>
                    </form>

                    <div className="mt-6 grid gap-4 md:grid-cols-3">
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold uppercase tracking-wide text-slate-500">Period</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{filters?.date_from ?? '-'} to {filters?.date_to ?? '-'}</div>
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
