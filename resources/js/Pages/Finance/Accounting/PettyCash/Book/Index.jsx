import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import { toTitleCase } from '@/lib/formatters';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function PettyCashBookIndex({ funds, selected_fund, opening_balance_signed, entries, filters }) {
    const [fundUuid, setFundUuid] = useState(filters?.petty_cash_fund_uuid ?? '');
    const [dateFrom, setDateFrom] = useState(filters?.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters?.date_to ?? '');
    const perPage = filters?.per_page ?? 20;
    const showingAllFunds = !selected_fund;

    const search = (e) => {
        e.preventDefault();
        router.get(route('finance.petty-cash-book.index'), {
            petty_cash_fund_uuid: fundUuid || undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
            per_page: perPage,
        }, { preserveState: true, replace: true });
    };

    const clear = () => {
        setFundUuid('');
        setDateFrom('');
        setDateTo('');
        router.get(route('finance.petty-cash-book.index'), { per_page: perPage }, { preserveState: true, replace: true });
    };

    const rows = useMemo(() => entries?.data ?? [], [entries?.data]);
    const running = useMemo(() => {
        let bal = parseFloat(opening_balance_signed ?? 0);
        return rows.map((r) => {
            bal += parseFloat(r.debit_amount ?? 0) - parseFloat(r.credit_amount ?? 0);
            return { ...r, running_balance: bal };
        });
    }, [rows, opening_balance_signed]);

    return (
        <AuthenticatedLayout>
            <Head title="Petty Cash Book" />

            <div className="mx-auto max-w-7xl space-y-6">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">Petty Cash Book</h1>
                    <p className="mt-1 text-sm text-slate-500">Ledger-based report for petty cash movements from posted journals.</p>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <form onSubmit={search} className="grid gap-3 lg:grid-cols-12 lg:items-end">
                        <FloatingSelect id="pcb_fund" label="Petty cash fund" value={fundUuid} onChange={(e) => setFundUuid(e.target.value)} className="lg:col-span-5">
                            <option value="">All funds</option>
                            {(funds ?? []).map((f) => <option key={f.uuid} value={f.uuid}>{toTitleCase(f.name ?? '')}</option>)}
                        </FloatingSelect>
                        <FloatingInput id="pcb_from" label="Date from" type="date" value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} className="lg:col-span-2" />
                        <FloatingInput id="pcb_to" label="Date to" type="date" value={dateTo} onChange={(e) => setDateTo(e.target.value)} className="lg:col-span-2" />
                        <div className="flex items-center gap-2 lg:col-span-3 lg:justify-end">
                            <button type="submit" className="h-11 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700">Search</button>
                            <button type="button" onClick={clear} className="h-11 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</button>
                        </div>
                    </form>

                    {selected_fund && (
                        <div className="mt-6 grid gap-4 md:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 p-4">
                                <div className="text-xs font-semibold text-slate-500">Fund</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{toTitleCase(selected_fund.name ?? '')}</div>
                            </div>
                            <div className="rounded-xl border border-slate-200 p-4">
                                <div className="text-xs font-semibold text-slate-500">Opening balance (carry forward)</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{opening_balance_signed ?? '0.0000'}</div>
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
                                                {showingAllFunds && <th>Fund</th>}
                                                <th>Description</th>
                                                <th>Journal</th>
                                                <th>Voucher</th>
                                                <th>Replenishment</th>
                                                {/* <th>Created</th>
                                                <th>Updated</th> */}
                                                <th className="w-36">Debit</th>
                                                <th className="w-36">Credit</th>
                                                {!showingAllFunds && <th className="w-40">Running Balance</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {running.map((r) => (
                                                <tr key={r.uuid} className="hover:bg-slate-50">
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.transaction_date}</td>
                                                    {showingAllFunds && (
                                                        <td className="px-4 py-3 text-sm text-slate-700">
                                                            {r.fund_code ? `${r.fund_code} - ${toTitleCase(r.fund_name ?? '')}` : toTitleCase(r.fund_name ?? '') || '-'}
                                                        </td>
                                                    )}
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.description}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.journal_no ?? '-'}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.voucher_no ?? '-'}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.replenishment_no ?? '-'}</td>
                                                    {/* <td className="px-4 py-3 text-sm text-slate-700">{r.created_at ?? '-'}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.updated_at ?? '-'}</td> */}
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.debit_amount_formatted}</td>
                                                    <td className="px-4 py-3 text-sm text-slate-700">{r.credit_amount_formatted}</td>
                                                    {!showingAllFunds && <td className="px-4 py-3 text-sm font-semibold text-slate-900">{r.running_balance.toFixed(4)}</td>}
                                                </tr>
                                            ))}
                                            {running.length === 0 && <tr><td colSpan={showingAllFunds ? 9 : 9} className="px-4 py-10 text-center text-sm text-slate-500">No entries found.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <PaginationSummary meta={entries?.meta} />
                                <Pagination links={entries?.meta?.links ?? entries?.links ?? []} />
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
