import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function GeneralLedgerIndex({ ledgers, selected_ledger, opening_balance_signed, entries, filters }) {
    const [ledgerUuid, setLedgerUuid] = useState(filters?.ledger_uuid ?? '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');
    const perPage = filters?.per_page ?? 15;

    const apply = (e) => {
        e.preventDefault();
        router.get(
            route('finance.general-ledger.index'),
            {
                ledger_uuid: ledgerUuid || undefined,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                per_page: perPage,
            },
            { preserveState: true, replace: true },
        );
    };

    const clear = () => {
        setLedgerUuid('');
        setDateFrom('');
        setDateTo('');
        router.get(route('finance.general-ledger.index'), { per_page: perPage }, { preserveState: true, replace: true });
    };

    const entryCollection = entries?.data ?? entries ?? null;
    const tableRows = useMemo(() => entryCollection?.data ?? entryCollection ?? [], [entryCollection]);

    const running = useMemo(() => {
        let bal = parseFloat(opening_balance_signed ?? 0);
        return tableRows.map((r) => {
            bal += parseFloat(r.debit_amount ?? 0) - parseFloat(r.credit_amount ?? 0);
            return {
                ...r,
                running_balance: bal,
                running_balance_display: formatFinanceBalance(bal),
            };
        });
    }, [tableRows, opening_balance_signed]);

    return (
        <AuthenticatedLayout>
            <Head title="General Ledger" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">General Ledger</h1>
                        <p className="mt-1 text-sm text-slate-500">Filter by ledger and date range. Pagination is server-side.</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('finance.journals.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Journals
                        </Link>
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={apply} className="grid gap-3 lg:grid-cols-12 lg:items-end">
                        <FloatingSelect
                            id="gl_ledger"
                            label="Ledger"
                            value={ledgerUuid}
                            onChange={(e) => setLedgerUuid(e.target.value)}
                            className="lg:col-span-5"
                        >
                            <option value="">Select ledger</option>
                            {(ledgers ?? []).map((l) => (
                                <option key={l.uuid} value={l.uuid}>
                                    {l.account_code ? `${l.account_code} - ${toTitleCase(l.name)}` : toTitleCase(l.name)}
                                </option>
                            ))}
                        </FloatingSelect>
                        <FloatingInput
                            id="gl_date_from"
                            label="Date from"
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            className="lg:col-span-3"
                        />
                        <FloatingInput
                            id="gl_date_to"
                            label="Date to"
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            className="lg:col-span-3"
                        />
                        <div className="flex flex-wrap items-center gap-2 lg:col-span-12 lg:justify-end">
                            <button type="submit" className="h-11 rounded-lg px-4 text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">Search</button>
                            <button type="button" onClick={clear} className="h-11 rounded-lg px-4 text-sm font-semibold border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">Clear</button>
                        </div>
                    </form>

                    {selected_ledger && (
                        <div className="mt-6 grid gap-4 md:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 p-4">
                                <div className="text-xs font-semibold text-slate-500">Ledger</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{toTitleCase(selected_ledger.name ?? '')}</div>
                            </div>
                            <div className="rounded-xl border border-slate-200 p-4">
                                <div className="text-xs font-semibold text-slate-500">Opening balance (carry forward)</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{formatFinanceBalance(opening_balance_signed ?? 0)}</div>
                            </div>
                        </div>
                    )}

                    {entries && (
                        <>
                            <div className="mt-6 overflow-x-auto">
                                <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                                    <table className="mis-table divide-y divide-slate-200">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Journal</th>
                                                <th className="w-40">Debit</th>
                                                <th className="w-40">Credit</th>
                                                <th className="w-48">Running balance</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {running.map((r) => (
                                                <tr key={r.uuid} className="hover:bg-slate-50">
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.transaction_date}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(r.description ?? '')}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.journal_no ?? '-'}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.debit_amount_formatted ?? r.debit_amount}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.credit_amount_formatted ?? r.credit_amount}</td>
                                                    <td className="px-4 py-3 text-sm font-semibold text-slate-900">{r.running_balance_display}</td>
                                                </tr>
                                            ))}
                                            {running.length === 0 && (
                                                <tr>
                                                    <td colSpan={6} className="px-4 py-10 text-center text-sm text-slate-500">No entries found.</td>
                                                </tr>
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <PaginationSummary meta={entryCollection?.meta ?? entries?.meta} />
                        <Pagination links={entryCollection?.meta?.links ?? entryCollection?.links ?? entries?.meta?.links ?? entries?.links ?? []} />
                    </div>
                </>
            )}
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

function formatFinanceBalance(value) {
    const numeric = parseFloat(value ?? 0);

    if (!Number.isFinite(numeric) || numeric === 0) {
        return '0.00';
    }

    const side = numeric >= 0 ? 'Dr' : 'Cr';

    return `${Math.abs(numeric).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ${side}`;
}
