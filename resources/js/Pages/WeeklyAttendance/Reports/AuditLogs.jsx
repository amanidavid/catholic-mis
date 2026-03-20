import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import FloatingInput from '@/Components/FloatingInput';
import FloatingSelect from '@/Components/FloatingSelect';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useMemo, useState } from 'react';

function monthStartIso(d = new Date()) {
    const x = new Date(d.getFullYear(), d.getMonth(), 1);
    return x.toISOString().slice(0, 10);
}

function monthEndIso(d = new Date()) {
    const x = new Date(d.getFullYear(), d.getMonth() + 1, 0);
    return x.toISOString().slice(0, 10);
}

export default function AuditLogs({ scoped_jumuiya, can_select_jumuiya }) {
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

    const [q, setQ] = useState('');
    const [action, setAction] = useState('');

    const [perPage, setPerPage] = useState(20);
    const [page, setPage] = useState(1);

    const [loading, setLoading] = useState(false);
    const [pageError, setPageError] = useState('');

    const [rows, setRows] = useState([]);
    const [pagination, setPagination] = useState(null);

    const canSelectJumuiya = !!can_select_jumuiya && !scoped_jumuiya;

    const run = async (nextPage = 1) => {
        setPageError('');

        if (!jumuiyaUuid) {
            setPageError('Christian Community is required.');
            return;
        }

        setLoading(true);
        try {
            const res = await axios.get(route('weekly-attendance.reports.audit-logs.data'), {
                params: {
                    from,
                    to,
                    jumuiya_uuid: jumuiyaUuid,
                    q,
                    action,
                    page: nextPage,
                    per_page: perPage,
                },
            });

            setRows(Array.isArray(res?.data?.rows) ? res.data.rows : []);
            setPagination(res?.data?.pagination ?? null);
            setPage(nextPage);
        } catch (e) {
            const msg = e?.response?.data?.message || e?.message || 'Unable to load audit logs.';
            setPageError(String(msg));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (!canView) return;
        if (!jumuiyaUuid) return;
        run(1);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    if (!canView) {
        return (
            <AuthenticatedLayout>
                <Head title="Weekly Attendance Audit Logs" />
                <div className="mx-auto max-w-5xl">
                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h1 className="text-xl font-semibold text-slate-900">Weekly Attendance Audit Logs</h1>
                        <p className="mt-2 text-sm text-slate-600">You do not have permission to view weekly attendance reports.</p>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Weekly Attendance Audit Logs" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Weekly Attendance Audit Logs</h1>
                        <p className="mt-1 text-sm text-slate-500">Track attendance status changes and who performed them.</p>
                    </div>

                    <div className="mt-5 grid gap-3 sm:grid-cols-10 sm:items-end">
                        <FloatingInput id="from" label="From" type="date" value={from} onChange={(e) => setFrom(e.target.value)} error={null} />
                        <FloatingInput id="to" label="To" type="date" value={to} onChange={(e) => setTo(e.target.value)} error={null} />
                        <FloatingInput id="q" label="Search (member/user email)" type="text" value={q} onChange={(e) => setQ(e.target.value)} error={null} />
                        <FloatingSelect id="action" label="Action" value={action} onChange={(e) => setAction(e.target.value)} className="">
                            <option value="">All</option>
                            <option value="created">created</option>
                            <option value="updated">updated</option>
                        </FloatingSelect>
                        <FloatingInput id="per_page" label="Per Page" type="number" value={perPage} onChange={(e) => setPerPage(Number(e.target.value || 20))} error={null} />

                        {scoped_jumuiya ? (
                            <div className="sm:col-span-5 rounded-lg border border-slate-300 bg-slate-50 px-3 py-3">
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
                        <PrimaryButton type="button" onClick={() => run(1)} disabled={loading}>
                            {loading ? 'Loading...' : 'Run'}
                        </PrimaryButton>
                        <SecondaryButton
                            type="button"
                            onClick={() => {
                                setFrom(monthStartIso());
                                setTo(monthEndIso());
                                setPage(1);
                            }}
                            disabled={loading}
                        >
                            Current Month
                        </SecondaryButton>
                    </div>

                    <InputError className="mt-3" message={pageError} />
                </div>

                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div className="text-sm text-slate-600">
                            <span className="font-semibold">Results:</span> {pagination?.total ?? rows.length}
                        </div>

                        <div className="flex items-center gap-2">
                            <SecondaryButton type="button" onClick={() => run(Math.max(1, page - 1))} disabled={loading || page <= 1}>
                                Prev
                            </SecondaryButton>
                            <div className="text-sm text-slate-600">
                                Page {pagination?.current_page ?? page} / {pagination?.last_page ?? 1}
                            </div>
                            <SecondaryButton
                                type="button"
                                onClick={() => run(Math.min(pagination?.last_page ?? page + 1, page + 1))}
                                disabled={loading || (pagination && page >= pagination.last_page)}
                            >
                                Next
                            </SecondaryButton>
                        </div>
                    </div>

                    <div className="mt-4 overflow-x-auto">
                        <table className="mis-table divide-y divide-slate-200">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Meeting Date</th>
                                    <th>Member</th>
                                    <th>Action</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Performed By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rows.length === 0 ? (
                                    <tr>
                                        <td className="px-3 py-4 text-sm text-slate-500" colSpan={8}>
                                            No results.
                                        </td>
                                    </tr>
                                ) : (
                                    rows.map((r) => (
                                        <tr key={r.uuid}>
                                            <td className="text-sm text-slate-700 whitespace-nowrap">{r.performed_at ?? '-'}</td>
                                            <td className="text-sm text-slate-700 whitespace-nowrap">{r.meeting_date ?? '-'}</td>
                                            <td className="text-sm font-semibold text-slate-900">{r.member_name ?? '-'}</td>
                                            <td className="text-sm text-slate-700">{r.action ?? '-'}</td>
                                            <td className="text-sm text-slate-700">{r.old_status ?? '-'}</td>
                                            <td className="text-sm text-slate-700">{r.new_status ?? '-'}</td>
                                            <td className="text-sm text-slate-700">{r.performed_by ?? '-'}</td>
                                            <td className="text-sm text-slate-700">{r.notes ?? '-'}</td>
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
