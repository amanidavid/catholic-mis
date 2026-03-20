import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import FloatingInput from '@/Components/FloatingInput';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';

function isoToday() {
    return new Date().toISOString().slice(0, 10);
}

function ExcelButton({ onClick, disabled }) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            className={
                `inline-flex items-center rounded-md border border-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 ` +
                (disabled
                    ? 'cursor-not-allowed bg-emerald-400 focus:ring-emerald-500'
                    : 'bg-emerald-600 hover:bg-emerald-500 active:bg-emerald-700 focus:ring-emerald-500')
            }
        >
            <svg viewBox="0 0 20 20" fill="currentColor" className="-ml-0.5 mr-2 h-4 w-4" aria-hidden="true">
                <path
                    fillRule="evenodd"
                    d="M10 2a1 1 0 0 1 1 1v7.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4A1 1 0 0 1 6.707 8.293L9 10.586V3a1 1 0 0 1 1-1ZM4 14a1 1 0 0 1 1 1v1h10v-1a1 1 0 1 1 2 0v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-2a1 1 0 0 1 1-1Z"
                    clipRule="evenodd"
                />
            </svg>
            Export Excel
        </button>
    );
}

export default function ActionList({ scoped_jumuiya, can_select_jumuiya }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);
    const canView = useMemo(
        () => Array.isArray(permissions) && permissions.includes('weekly-attendance.view'),
        [permissions],
    );

    const [asOf, setAsOf] = useState(() => isoToday());
    const [weeks, setWeeks] = useState(12);
    const [minConsecutive, setMinConsecutive] = useState(3);

    const [zoneUuid, setZoneUuid] = useState('');
    const [jumuiyaUuid, setJumuiyaUuid] = useState(scoped_jumuiya?.uuid ?? '');

    const [loading, setLoading] = useState(false);
    const [pageError, setPageError] = useState('');

    const [rows, setRows] = useState([]);

    const canSelectJumuiya = !!can_select_jumuiya && !scoped_jumuiya;

    const run = async () => {
        setPageError('');

        if (!jumuiyaUuid) {
            setPageError('Christian Community is required.');
            return;
        }

        setLoading(true);
        try {
            const res = await axios.get(route('weekly-attendance.reports.action-list.data'), {
                params: {
                    as_of: asOf,
                    weeks,
                    min_consecutive_absences: minConsecutive,
                    jumuiya_uuid: jumuiyaUuid,
                },
            });

            setRows(Array.isArray(res?.data?.rows) ? res.data.rows : []);
        } catch (e) {
            const msg = e?.response?.data?.message || e?.message || 'Unable to load report.';
            setPageError(String(msg));
        } finally {
            setLoading(false);
        }
    };

    const exportExcel = () => {
        if (!jumuiyaUuid) {
            setPageError('Christian Community is required.');
            return;
        }

        const url = route('weekly-attendance.reports.action-list.export', {
            as_of: asOf,
            weeks,
            min_consecutive_absences: minConsecutive,
            jumuiya_uuid: jumuiyaUuid,
        });

        window.open(url, '_blank', 'noopener,noreferrer');
    };

    useEffect(() => {
        if (!canView) return;
        if (!jumuiyaUuid) return;
        run();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    if (!canView) {
        return (
            <AuthenticatedLayout>
                <Head title="Action List Report" />
                <div className="mx-auto max-w-5xl">
                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h1 className="text-xl font-semibold text-slate-900">Action List Report</h1>
                        <p className="mt-2 text-sm text-slate-600">You do not have permission to view weekly attendance reports.</p>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Action List Report" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Action List Report</h1>
                            <p className="mt-1 text-sm text-slate-500">Members who have been Absent for N consecutive meetings (counting backwards from As Of date).</p>
                        </div>
                        <div className="text-xs text-slate-500">Today: {isoToday()}</div>
                    </div>

                    <div className="mt-5 grid gap-3 sm:grid-cols-6 sm:items-end">
                        <FloatingInput id="as_of" label="As Of" type="date" value={asOf} onChange={(e) => setAsOf(e.target.value)} error={null} />
                        <FloatingInput id="weeks" label="Weeks" type="number" value={weeks} onChange={(e) => setWeeks(Number(e.target.value || 0))} error={null} />
                        <FloatingInput id="min_consecutive" label="Min Consecutive" type="number" value={minConsecutive} onChange={(e) => setMinConsecutive(Number(e.target.value || 0))} error={null} />

                        {scoped_jumuiya ? (
                            <div className="sm:col-span-3 rounded-lg border border-slate-300 bg-slate-50 px-3 py-3">
                                <div className="text-xs font-semibold text-slate-500">Christian Community</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{scoped_jumuiya?.name ?? ''}</div>
                            </div>
                        ) : (
                            <>
                                <div className="sm:col-span-2">
                                    <SearchableZoneSelect
                                        id="zone_uuid"
                                        label="Zone"
                                        value={zoneUuid}
                                        onChange={(v) => {
                                            setZoneUuid(v);
                                            setJumuiyaUuid('');
                                        }}
                                        error={null}
                                    />
                                </div>

                                <div className="sm:col-span-3">
                                    <SearchableJumuiyaSelect
                                        id="jumuiya_uuid"
                                        label="Christian Community"
                                        value={jumuiyaUuid}
                                        onChange={setJumuiyaUuid}
                                        zoneUuid={canSelectJumuiya ? zoneUuid : ''}
                                        disabled={!canSelectJumuiya || !zoneUuid}
                                        error={null}
                                    />
                                </div>
                            </>
                        )}
                    </div>

                    <div className="mt-4 flex flex-wrap gap-2">
                        <PrimaryButton type="button" onClick={run} disabled={loading}>
                            {loading ? 'Loading...' : 'Run Report'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => {
                            setWeeks(12);
                            setMinConsecutive(3);
                        }} disabled={loading}>
                            Reset
                        </SecondaryButton>
                        <ExcelButton onClick={exportExcel} disabled={loading || !jumuiyaUuid} />
                    </div>

                    <InputError className="mt-3" message={pageError} />
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="text-sm text-slate-600">
                        <span className="font-semibold">Results:</span> {rows.length}
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="mis-table divide-y divide-slate-200">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Family</th>
                                    <th className="text-right">Consecutive Absences</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td className="px-3 py-4 text-sm text-slate-500" colSpan={3}>
                                            No members found.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((r) => (
                                        <tr key={r.member_uuid}>
                                            <td className="text-sm font-semibold text-slate-900">{r.member_name}</td>
                                            <td className="text-sm text-slate-700">{r.family_name ?? ''}</td>
                                            <td className="text-right text-sm font-semibold text-slate-900">{r.consecutive_absences}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
