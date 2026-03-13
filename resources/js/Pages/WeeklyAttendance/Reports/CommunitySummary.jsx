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

function monthStartIso(d = new Date()) {
    const x = new Date(d.getFullYear(), d.getMonth(), 1);
    return x.toISOString().slice(0, 10);
}

function monthEndIso(d = new Date()) {
    const x = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    return x.toISOString().slice(0, 10);
}

const STATUS_COLS = [
    { key: 'eligible', label: 'Eligible' },
    { key: 'present', label: 'Present' },
    { key: 'absent', label: 'Absent' },
    { key: 'sick', label: 'Sick' },
    { key: 'travel', label: 'Travel' },
    { key: 'other', label: 'Other' },
];

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

export default function CommunitySummary({ scoped_jumuiya, can_select_jumuiya }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);
    const canView = useMemo(
        () => Array.isArray(permissions) && permissions.includes('weekly-attendance.view'),
        [permissions],
    );

    const [from, setFrom] = useState(() => monthStartIso());
    const [to, setTo] = useState(() => monthEndIso());

    const [zoneUuid, setZoneUuid] = useState('');
    const [jumuiyaUuid, setJumuiyaUuid] = useState(scoped_jumuiya?.uuid ?? '');

    const [loading, setLoading] = useState(false);
    const [pageError, setPageError] = useState('');

    const [rows, setRows] = useState([]);
    const [totals, setTotals] = useState(null);

    const canSelectJumuiya = !!can_select_jumuiya && !scoped_jumuiya;

    const run = async () => {
        setPageError('');

        if (!jumuiyaUuid) {
            setPageError('Christian Community is required.');
            return;
        }

        setLoading(true);
        try {
            const res = await axios.get(route('weekly-attendance.reports.community.data'), {
                params: {
                    from,
                    to,
                    jumuiya_uuid: jumuiyaUuid,
                },
            });

            setRows(Array.isArray(res?.data?.rows) ? res.data.rows : []);
            setTotals(res?.data?.totals ?? null);
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

        const url = route('weekly-attendance.reports.community.export', {
            from,
            to,
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
                <Head title="Community Attendance Report" />
                <div className="mx-auto max-w-5xl">
                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h1 className="text-xl font-semibold text-slate-900">Community Attendance Report</h1>
                        <p className="mt-2 text-sm text-slate-600">You do not have permission to view weekly attendance reports.</p>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Community Attendance Report" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Community Attendance Report</h1>
                            <p className="mt-1 text-sm text-slate-500">Attendance % = (Present / Eligible) × 100. Default range is current month.</p>
                        </div>
                        <div className="text-xs text-slate-500">Today: {isoToday()}</div>
                    </div>

                    <div className="mt-5 grid gap-3 sm:grid-cols-4 sm:items-end">
                        <FloatingInput id="from" label="From" type="date" value={from} onChange={(e) => setFrom(e.target.value)} error={null} />
                        <FloatingInput id="to" label="To" type="date" value={to} onChange={(e) => setTo(e.target.value)} error={null} />

                        {scoped_jumuiya ? (
                            <div className="rounded-lg border border-slate-300 bg-slate-50 px-3 py-3">
                                <div className="text-xs font-semibold text-slate-500">Christian Community</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{scoped_jumuiya?.name ?? ''}</div>
                            </div>
                        ) : (
                            <>
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

                                <SearchableJumuiyaSelect
                                    id="jumuiya_uuid"
                                    label="Christian Community"
                                    value={jumuiyaUuid}
                                    onChange={setJumuiyaUuid}
                                    zoneUuid={canSelectJumuiya ? zoneUuid : ''}
                                    disabled={!canSelectJumuiya || !zoneUuid}
                                    error={null}
                                />
                            </>
                        )}
                    </div>

                    <div className="mt-4 flex flex-wrap gap-2">
                        <PrimaryButton type="button" onClick={run} disabled={loading}>
                            {loading ? 'Loading...' : 'Run Report'}
                        </PrimaryButton>
                        <SecondaryButton type="button" onClick={() => {
                            setFrom(monthStartIso());
                            setTo(monthEndIso());
                        }} disabled={loading}>
                            Current Month
                        </SecondaryButton>
                        <ExcelButton onClick={exportExcel} disabled={loading || !jumuiyaUuid} />
                    </div>

                    <InputError className="mt-3" message={pageError} />
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="grid gap-3 sm:grid-cols-4">
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold text-slate-500">Total Eligible</div>
                            <div className="mt-1 text-2xl font-semibold text-slate-900">{totals?.eligible ?? 0}</div>
                        </div>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold text-slate-500">Total Present</div>
                            <div className="mt-1 text-2xl font-semibold text-slate-900">{totals?.present ?? 0}</div>
                        </div>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold text-slate-500">Attendance %</div>
                            <div className="mt-1 text-2xl font-semibold text-slate-900">{totals?.attendance_percent ?? 0}%</div>
                        </div>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div className="text-xs font-semibold text-slate-500">Meetings</div>
                            <div className="mt-1 text-2xl font-semibold text-slate-900">{rows.length}</div>
                        </div>
                    </div>

                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Meeting Date</th>
                                    {STATUS_COLS.map((c) => (
                                        <th key={c.key} className="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{c.label}</th>
                                    ))}
                                    <th className="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Attendance %</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 bg-white">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td className="px-3 py-4 text-sm text-slate-500" colSpan={STATUS_COLS.length + 2}>
                                            No meetings found for the selected range.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((r) => (
                                        <tr key={r.meeting_date}>
                                            <td className="whitespace-nowrap px-3 py-2 text-sm font-semibold text-slate-900">{r.meeting_date}</td>
                                            {STATUS_COLS.map((c) => (
                                                <td key={c.key} className="whitespace-nowrap px-3 py-2 text-right text-sm text-slate-700">{r[c.key] ?? 0}</td>
                                            ))}
                                            <td className="whitespace-nowrap px-3 py-2 text-right text-sm font-semibold text-slate-900">{r.attendance_percent ?? 0}%</td>
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
