import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

export default function AssociationReportIndex({ rows = [], outstations = [], filters = {} }) {
    const rowList = rows?.data ?? rows ?? [];
    const [q, setQ] = useState(filters?.q ?? '');
    const [outstationUuid, setOutstationUuid] = useState(filters?.outstation_uuid ?? '');
    const maxMembers = useMemo(
        () => Math.max(1, ...rowList.map((row) => Number(row.total_members ?? 0))),
        [rowList],
    );

    useEffect(() => {
        setQ(filters?.q ?? '');
        setOutstationUuid(filters?.outstation_uuid ?? '');
    }, [filters?.q, filters?.outstation_uuid]);

    const run = () => {
        router.get(
            route('reports.associations.index'),
            { q: q || undefined, outstation_uuid: outstationUuid || undefined },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout header="Kitume Report">
            <Head title="Kitume Report" />

            <div className="space-y-6">
                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div className="w-full sm:max-w-md">
                            <FloatingInput
                                id="association_report_q"
                                label="Search group name"
                                type="text"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                            />
                        </div>

                        <div className="w-full sm:w-64">
                            <FloatingSelect
                                id="association_report_outstation"
                                label="Outstation"
                                value={outstationUuid}
                                onChange={(e) => setOutstationUuid(e.target.value)}
                            >
                                <option value="">All outstations</option>
                                {(outstations ?? []).map((outstation) => (
                                    <option key={outstation.uuid} value={outstation.uuid}>{outstation.name}</option>
                                ))}
                            </FloatingSelect>
                        </div>

                        <div className="flex gap-2">
                            <PrimaryButton type="button" onClick={run} className="normal-case">
                                Search
                            </PrimaryButton>
                            <SecondaryButton
                                type="button"
                                onClick={() => {
                                    setQ('');
                                    setOutstationUuid('');
                                    router.get(route('reports.associations.index'), {}, { preserveState: true, replace: true, preserveScroll: true });
                                }}
                                className="normal-case"
                            >
                                Clear
                            </SecondaryButton>
                        </div>
                    </div>
                </section>

                <section className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Kitume Groups Summary</h1>
                            <p className="mt-1 text-sm text-slate-500">Parish-level membership and leadership snapshot by group.</p>
                        </div>
                        <div className="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                            {rowList.length} groups
                        </div>
                    </div>

                    <div className="mt-5 overflow-x-auto">
                        <div className="overflow-hidden rounded-xl ring-1 ring-slate-200">
                            <table className="mis-table">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th>Status</th>
                                        <th>Members</th>
                                        <th className="text-right">Leaders</th>
                                        <th className="text-right">Outstations</th>
                                        <th>Gender split</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rowList.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-4 py-8 text-center text-sm text-slate-500">No groups found.</td>
                                        </tr>
                                    ) : (
                                        rowList.map((row) => (
                                            <tr key={row.uuid}>
                                                <td className="px-4 py-3">
                                                    <div className="text-sm font-semibold text-slate-900">{row.name}</div>
                                                    <div className="text-xs text-slate-500">{row.description || row.code || '-'}</div>
                                                </td>
                                                <td className="px-4 py-3 text-sm">
                                                    <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ${row.is_active
                                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                        : 'bg-rose-50 text-rose-700 ring-rose-200'
                                                        }`}>
                                                        {row.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-sm text-slate-700">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-28 overflow-hidden rounded-full bg-slate-100">
                                                            <div
                                                                className="h-2 rounded-full bg-indigo-500"
                                                                style={{ width: `${Number(row.total_members ?? 0) > 0 ? Math.max(6, (Number(row.total_members ?? 0) / maxMembers) * 100) : 0}%` }}
                                                            />
                                                        </div>
                                                        <span className="font-semibold">{row.total_members}</span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-right text-sm text-slate-700">{row.total_leaders}</td>
                                                <td className="px-4 py-3 text-right text-sm text-slate-700">{row.outstations}</td>
                                                <td className="px-4 py-3 text-sm text-slate-700">
                                                    <div className="flex min-w-44 items-center gap-3">
                                                        <div className="flex h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                                            <div
                                                                className="h-2 bg-sky-500"
                                                                style={{ width: `${Number(row.total_members ?? 0) > 0 ? (Number(row.men ?? 0) / Number(row.total_members ?? 1)) * 100 : 0}%` }}
                                                            />
                                                            <div
                                                                className="h-2 bg-rose-400"
                                                                style={{ width: `${Number(row.total_members ?? 0) > 0 ? (Number(row.women ?? 0) / Number(row.total_members ?? 1)) * 100 : 0}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-xs font-semibold text-slate-600">
                                                            M {row.men} / W {row.women}
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </AuthenticatedLayout>
    );
}
