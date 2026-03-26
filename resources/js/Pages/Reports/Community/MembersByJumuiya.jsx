import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function MembersByJumuiya({ rows, filters, pagination }) {
    const [q, setQ] = useState(filters?.q ?? '');
    const [perPage, setPerPage] = useState(filters?.per_page ?? 50);

    useEffect(() => {
        setQ(filters?.q ?? '');
        setPerPage(filters?.per_page ?? 50);
    }, [filters?.q, filters?.per_page]);
    useEffect(() => {
        if (!perPage) return;
        run(1);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [perPage]);


    const run = (nextPage = 1) => {
        router.get(
            route('reports.community.members-by-jumuiya'),
            {
                q,
                per_page: perPage,
                page: nextPage,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    };

    const page = pagination?.current_page ?? 1;
    const hasMore = Boolean(pagination?.has_more);
    const from = pagination?.from ?? null;
    const to = pagination?.to ?? null;

    return (
        <AuthenticatedLayout header="Community Summary">
            <Head title="Community Summary" />

            <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div className="flex w-full flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="w-full sm:max-w-md">
                            <FloatingInput
                                id="q"
                                label="Search (zone/community)"
                                type="text"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                error={null}
                            />
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <PrimaryButton type="button" onClick={() => run(1)} className="normal-case">
                                Search
                            </PrimaryButton>

                            <SecondaryButton
                                type="button"
                                onClick={() => {
                                    setQ('');
                                    setPerPage(50);
                                    router.get(
                                        route('reports.community.members-by-jumuiya'),
                                        {
                                            q: '',
                                            per_page: 50,
                                            page: 1,
                                        },
                                        {
                                            preserveState: true,
                                            replace: true,
                                            preserveScroll: true,
                                        },
                                    );
                                }}
                                className="normal-case"
                            >
                                Clear
                            </SecondaryButton>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-end justify-between gap-3 sm:justify-end">
                        <div className="w-full sm:w-40">
                            <FloatingSelect
                                id="per_page"
                                label="Per Page"
                                value={String(perPage)}
                                onChange={(e) => setPerPage(Number(e.target.value || 50))}
                                className=""
                            >
                                {Array.from({ length: 20 }, (_, i) => (i + 1) * 10).map((n) => (
                                    <option key={n} value={n}>{n}</option>
                                ))}
                            </FloatingSelect>
                        </div>

                        <div className="text-sm text-slate-600">
                            <span className="font-semibold">Rows:</span> {from && to ? `${from}-${to}` : (rows?.length ?? 0)}
                        </div>
                    </div>
                </div>

                <div className="mt-4 flex items-center justify-between gap-2">
                    <div className="text-sm text-slate-600">
                        Page {page}
                    </div>
                    <div className="flex items-center gap-2">
                        <SecondaryButton type="button" onClick={() => run(Math.max(1, page - 1))} disabled={page <= 1}>
                            Prev
                        </SecondaryButton>
                        <SecondaryButton type="button" onClick={() => run(page + 1)} disabled={!hasMore}>
                            Next
                        </SecondaryButton>
                    </div>
                </div>

                <div className="mt-4 overflow-x-auto">
                    <table className="mis-table w-full">
                        <thead>
                            <tr>
                                <th>Zone</th>
                                <th>Christian Community</th>
                                <th className="text-right">Families</th>
                                <th className="text-right">Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(rows ?? []).length === 0 ? (
                                <tr>
                                    <td className="px-3 py-4 text-sm text-slate-500" colSpan={4}>
                                        No results.
                                    </td>
                                </tr>
                            ) : (
                                (rows ?? []).map((r) => (
                                    <tr key={r.jumuiya_uuid}>
                                        <td>{r.zone_name}</td>
                                        <td>{r.jumuiya_name}</td>
                                        <td className="text-right">{r.families}</td>
                                        <td className="text-right">{r.members}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
