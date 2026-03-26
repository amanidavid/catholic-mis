import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Spinner from '@/Components/Spinner';
import { toTitleCase } from '@/lib/formatters';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

export default function JournalShow({ journal }) {
    const permissions = usePage().props?.auth?.user?.permissions ?? [];
    const can = (perm) => Array.isArray(permissions) && permissions.includes(perm);
    const canPost = can('finance.journals.post');

    const [posting, setPosting] = useState(false);

    const totals = useMemo(() => {
        const lines = journal?.lines ?? [];
        let d = 0;
        let c = 0;
        lines.forEach((l) => {
            d += parseFloat(l.debit_amount ?? 0);
            c += parseFloat(l.credit_amount ?? 0);
        });
        return { debit: d, credit: c };
    }, [journal?.lines]);

    const postJournal = () => {
        if (!journal?.uuid) return;
        setPosting(true);
        router.post(route('finance.journals.post', journal.uuid), {}, { preserveScroll: true, onFinish: () => setPosting(false) });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Journal ${journal?.journal_no ?? ''}`} />

            <div className="mx-auto max-w-5xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Journal {journal?.journal_no}</h1>
                        <p className="mt-1 text-sm text-slate-500">Transaction date: {journal?.transaction_date}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href={route('finance.journals.index')}
                            className="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Back
                        </Link>
                        {canPost && !journal?.is_posted && (
                            <PrimaryButton
                                type="button"
                                onClick={postJournal}
                                disabled={posting}
                                className="h-11 gap-2 rounded-lg text-sm font-semibold normal-case tracking-normal bg-emerald-600 text-white hover:bg-emerald-700"
                            >
                                {posting && <Spinner size="sm" className="text-white" />}
                                <span>Post</span>
                            </PrimaryButton>
                        )}
                        {journal?.is_posted && (
                            <span className="inline-flex h-11 items-center rounded-lg bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                Posted
                            </span>
                        )}
                    </div>
                </div>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-500">Description</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">{toTitleCase(journal?.description ?? '') || '-'}</div>
                        </div>
                        <div className="rounded-xl border border-slate-200 p-4">
                            <div className="text-xs font-semibold text-slate-500">Totals</div>
                            <div className="mt-1 text-sm font-semibold text-slate-900">
                                Debit: {totals.debit.toFixed(2)} | Credit: {totals.credit.toFixed(2)}
                            </div>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table divide-y divide-slate-200">
                                <thead>
                                    <tr>
                                        <th>Ledger</th>
                                        <th>Description</th>
                                        <th className="w-40">Debit</th>
                                        <th className="w-40">Credit</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {(journal?.lines ?? []).map((l) => (
                                        <tr key={l.uuid} className="hover:bg-slate-50">
                                            <td className="px-4 py-3 text-sm font-semibold text-slate-900">
                                                {l.ledger_account_code ? `${l.ledger_account_code} - ${toTitleCase(l.ledger_name ?? '')}` : toTitleCase(l.ledger_name ?? '')}
                                            </td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{toTitleCase(l.description ?? '')}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{l.debit_amount}</td>
                                            <td className="px-4 py-3 text-sm text-slate-700">{l.credit_amount}</td>
                                        </tr>
                                    ))}
                                    {(journal?.lines ?? []).length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="px-4 py-10 text-center text-sm text-slate-500">No lines.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {!journal?.is_posted && (
                        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Posting will validate that total debit equals total credit and then write entries to the General Ledger.
                        </div>
                    )}
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
