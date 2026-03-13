import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import FloatingInput from '@/Components/FloatingInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SearchableJumuiyaSelect from '@/Components/SearchableJumuiyaSelect';
import SearchableZoneSelect from '@/Components/SearchableZoneSelect';
import axios from 'axios';
import { Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const STATUS_OPTIONS = [
    { value: 'present', label: 'Present' },
    { value: 'absent', label: 'Absent' },
    { value: 'sick', label: 'Sick' },
    { value: 'travel', label: 'Travel' },
    { value: 'other', label: 'Other' },
];

const STATUS_STYLES = {
    present: 'bg-emerald-50 text-emerald-800 ring-emerald-200 hover:bg-emerald-100',
    absent: 'bg-rose-50 text-rose-800 ring-rose-200 hover:bg-rose-100',
    sick: 'bg-amber-50 text-amber-900 ring-amber-200 hover:bg-amber-100',
    travel: 'bg-sky-50 text-sky-900 ring-sky-200 hover:bg-sky-100',
    other: 'bg-slate-50 text-slate-800 ring-slate-200 hover:bg-slate-100',
};

function snapToNextSaturday(isoDate) {
    if (!isoDate) return isoDate;

    const d = new Date(`${isoDate}T00:00:00`);
    if (Number.isNaN(d.getTime())) return isoDate;

    const day = d.getDay();
    const saturday = 6;
    const diff = (saturday - day + 7) % 7;
    d.setDate(d.getDate() + diff);
    return d.toISOString().slice(0, 10);
}

function isSaturdayIso(isoDate) {
    if (!isoDate) return false;
    const d = new Date(`${isoDate}T00:00:00`);
    if (Number.isNaN(d.getTime())) return false;
    return d.getDay() === 6;
}

function toTitleCase(value) {
    const s = String(value ?? '').trim();
    if (!s) return '';
    return s
        .toLowerCase()
        .split(/\s+/)
        .filter(Boolean)
        .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
        .join(' ');
}

export default function WeeklyAttendanceIndex({ scoped_jumuiya, can_select_jumuiya }) {
    const { auth } = usePage().props;
    const permissions = useMemo(() => auth?.user?.permissions ?? [], [auth?.user?.permissions]);

    const canView = useMemo(
        () => Array.isArray(permissions) && permissions.includes('weekly-attendance.view'),
        [permissions],
    );

    const canRecord = useMemo(
        () => Array.isArray(permissions) && permissions.includes('weekly-attendance.record'),
        [permissions],
    );

    const canOverrideDate = useMemo(
        () => Array.isArray(permissions) && permissions.includes('weekly-attendance.override-lock'),
        [permissions],
    );

    const [meetingDate, setMeetingDate] = useState(() => {
        const d = new Date();
        const iso = d.toISOString().slice(0, 10);
        return snapToNextSaturday(iso);
    });

    const [zoneUuid, setZoneUuid] = useState('');
    const [jumuiyaUuid, setJumuiyaUuid] = useState(scoped_jumuiya?.uuid ?? '');
    const [meetingUuid, setMeetingUuid] = useState('');
    const [meetingClosed, setMeetingClosed] = useState(false);

    const [loading, setLoading] = useState(false);
    const [bulkApplying, setBulkApplying] = useState(false);
    const [families, setFamilies] = useState([]);

    const [pagination, setPagination] = useState(null);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(20);

    const [selectedMembers, setSelectedMembers] = useState(() => new Set());
    const [bulkStatus, setBulkStatus] = useState('');

    const [pageError, setPageError] = useState('');

    const flashSuccess = (message) => {
        window.dispatchEvent(new CustomEvent('app:flash', { detail: { type: 'success', message } }));
    };

    const fetchFamilyReport = async (meetingId, nextPage = 1, nextPerPage = perPage) => {
        const reportRes = await axios.get(route('weekly-attendance.meetings.family-report', meetingId), {
            params: {
                page: nextPage,
                per_page: nextPerPage,
            },
        });

        const nextFamilies = reportRes?.data?.families;
        setFamilies(Array.isArray(nextFamilies) ? nextFamilies : []);
        setPagination(reportRes?.data?.pagination ?? null);
        setPage(nextPage);
        setPerPage(nextPerPage);
    };

    const openSession = async () => {
        setPageError('');
        setMeetingClosed(false);

        const normalizedMeetingDate = canOverrideDate
            ? meetingDate
            : (isSaturdayIso(meetingDate) ? meetingDate : snapToNextSaturday(meetingDate));

        if (!canOverrideDate && normalizedMeetingDate !== meetingDate) {
            setMeetingDate(normalizedMeetingDate);
        }

        if (can_select_jumuiya && !scoped_jumuiya) {
            if (!zoneUuid) {
                setPageError('Zone is required to select a Christian Community.');
                return;
            }

            if (!jumuiyaUuid) {
                setPageError('Christian Community is required.');
                return;
            }
        }

        setLoading(true);

        try {
            clearSelection();
            const res = await axios.post(route('weekly-attendance.open'), {
                meeting_date: normalizedMeetingDate,
                jumuiya_uuid: can_select_jumuiya ? jumuiyaUuid || undefined : undefined,
            });

            const nextMeetingUuid = res?.data?.meeting_uuid;
            if (!nextMeetingUuid) {
                throw new Error('Missing meeting UUID');
            }

            setMeetingUuid(nextMeetingUuid);

            clearSelection();
            setPagination(null);
            setPage(1);
            await fetchFamilyReport(nextMeetingUuid, 1, perPage);
        } catch (e) {
            const msg = e?.response?.data?.message || e?.message || 'Unable to open weekly attendance session.';
            setPageError(String(msg));
        } finally {
            setLoading(false);
        }
    };

    const toggleSelectedMember = (memberUuid) => {
        setSelectedMembers((prev) => {
            const next = new Set(prev);
            if (next.has(memberUuid)) {
                next.delete(memberUuid);
            } else {
                next.add(memberUuid);
            }
            return next;
        });
    };

    const clearSelection = () => setSelectedMembers(new Set());

    const closeSession = async () => {
        if (!meetingUuid) return;

        setPageError('');
        setLoading(true);

        try {
            await axios.post(route('weekly-attendance.meetings.close', meetingUuid));

            await fetchFamilyReport(meetingUuid, page, perPage);
            clearSelection();
            setMeetingClosed(true);
            flashSuccess('Session closed successfully.');
        } catch (e) {
            const msg = e?.response?.data?.message || e?.message || 'Unable to close session.';
            setPageError(String(msg));
        } finally {
            setLoading(false);
        }
    };

    const bulkMarkSelected = async () => {
        if (!meetingUuid) return;
        if (meetingClosed) return;
        if (bulkApplying) return;

        const eligibleUuids = new Set(
            (Array.isArray(families) ? families : []).flatMap((f) =>
                (Array.isArray(f?.members) ? f.members : [])
                    .filter((m) => m?.eligible === true)
                    .map((m) => m.uuid),
            ),
        );

        const memberUuids = Array.from(selectedMembers).filter((u) => eligibleUuids.has(u));
        if (memberUuids.length === 0) {
            setPageError('No eligible members selected.');
            return;
        }

        setPageError('');
        setBulkApplying(true);

        try {
            const res = await axios.post(
                route('weekly-attendance.meetings.bulk-mark', meetingUuid),
                {
                    member_uuids: memberUuids,
                    status: bulkStatus,
                },
                { timeout: 30000 },
            );

            const updated = Array.isArray(res?.data?.updated) ? res.data.updated : [];
            const updateMap = new Map(updated.map((u) => [u.member_uuid, u.status]));

            setFamilies((prev) =>
                prev.map((f) => ({
                    ...f,
                    members: Array.isArray(f.members)
                        ? f.members.map((m) => (updateMap.has(m.uuid) ? { ...m, status: updateMap.get(m.uuid) } : m))
                        : f.members,
                })),
            );

            clearSelection();
            flashSuccess(`Saved successfully. Updated ${updated.length} member(s) as ${bulkStatus}.`);
        } catch (e) {
            const statusCode = e?.response?.status;
            const data = e?.response?.data;

            if (e?.code === 'ECONNABORTED') {
                setPageError('Request timed out. Please reload the session and try again.');
                return;
            }

            if (statusCode === 422 && Array.isArray(data?.invalid_member_uuids) && data.invalid_member_uuids.length > 0) {
                setSelectedMembers((prev) => {
                    const next = new Set(prev);
                    data.invalid_member_uuids.forEach((u) => next.delete(u));
                    return next;
                });
            }

            const msg = data?.message || e?.message || 'Unable to save attendance.';
            setPageError(String(msg));
        } finally {
            setBulkApplying(false);
        }
    };

    const setMemberStatus = async (memberUuid, status) => {
        if (!meetingUuid) return;
        if (meetingClosed) return;

        setPageError('');

        try {
            await axios.post(route('weekly-attendance.meetings.mark', meetingUuid), {
                member_uuid: memberUuid,
                status,
            });

            setFamilies((prev) =>
                prev.map((f) => ({
                    ...f,
                    members: Array.isArray(f.members)
                        ? f.members.map((m) => (m.uuid === memberUuid ? { ...m, status } : m))
                        : f.members,
                })),
            );

            flashSuccess('Saved successfully.');
        } catch (e) {
            const msg = e?.response?.data?.message || e?.message || 'Unable to save attendance.';
            setPageError(String(msg));
        }
    };

    if (!canView) {
        return (
            <AuthenticatedLayout>
                <Head title="Weekly Attendance" />
                <div className="mx-auto max-w-5xl">
                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <h1 className="text-xl font-semibold text-slate-900">Weekly Attendance</h1>
                        <p className="mt-2 text-sm text-slate-600">You do not have permission to view weekly attendance.</p>
                    </div>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title="Weekly Attendance" />

            <div className="mx-auto max-w-6xl space-y-6">
                <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-xl font-semibold text-slate-900">Weekly Attendance</h1>
                            <p className="mt-1 text-sm text-slate-500">Open a Saturday session and mark members one by one.</p>
                        </div>
                    </div>

                    {!!scoped_jumuiya ? (
                        <div className="mt-5 grid gap-3 sm:grid-cols-2 sm:items-end">
                            <div>
                                <FloatingInput
                                    id="meeting_date"
                                    label="Meeting Date (Saturday)"
                                    type="date"
                                    value={meetingDate}
                                    onChange={(e) => setMeetingDate(e.target.value)}
                                    onBlur={() => {
                                        if (canOverrideDate) return;
                                        setMeetingDate((v) => (isSaturdayIso(v) ? v : snapToNextSaturday(v)));
                                    }}
                                    error={null}
                                />
                                {!canOverrideDate && (
                                    <div className="mt-1 text-xs text-slate-500">Only Saturdays allowed. Date will snap to Saturday.</div>
                                )}
                                {!canOverrideDate && !isSaturdayIso(meetingDate) && (
                                    <div className="mt-1 text-xs font-semibold text-amber-700">Selected date is not Saturday.</div>
                                )}
                            </div>

                            <div className="rounded-lg border border-slate-300 bg-slate-50 px-3 py-3">
                                <div className="text-xs font-semibold text-slate-500">Christian Community</div>
                                <div className="mt-1 text-sm font-semibold text-slate-900">{scoped_jumuiya?.name ?? ''}</div>
                            </div>
                        </div>
                    ) : (
                        <div className="mt-5 grid gap-3 sm:grid-cols-3 sm:items-end">
                            <div>
                                <FloatingInput
                                    id="meeting_date"
                                    label="Meeting Date (Saturday)"
                                    type="date"
                                    value={meetingDate}
                                    onChange={(e) => setMeetingDate(e.target.value)}
                                    onBlur={() => {
                                        if (canOverrideDate) return;
                                        setMeetingDate((v) => (isSaturdayIso(v) ? v : snapToNextSaturday(v)));
                                    }}
                                    error={null}
                                />
                                {!canOverrideDate && (
                                    <div className="mt-1 text-xs text-slate-500">Only Saturdays allowed. Date will snap to Saturday.</div>
                                )}
                                {!canOverrideDate && !isSaturdayIso(meetingDate) && (
                                    <div className="mt-1 text-xs font-semibold text-amber-700">Selected date is not Saturday.</div>
                                )}
                            </div>

                            {can_select_jumuiya ? (
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
                            ) : (
                                <div />
                            )}

                            <SearchableJumuiyaSelect
                                id="jumuiya_uuid"
                                label="Christian Community"
                                value={jumuiyaUuid}
                                onChange={setJumuiyaUuid}
                                zoneUuid={can_select_jumuiya ? zoneUuid : ''}
                                disabled={(!can_select_jumuiya) || (can_select_jumuiya && !zoneUuid)}
                                error={null}
                            />
                        </div>
                    )}

                    <div className="mt-4 flex flex-wrap gap-2">
                        <PrimaryButton type="button" onClick={openSession} disabled={!canRecord || loading}>
                            {loading ? 'Loading...' : meetingUuid ? 'Reload' : 'Open Session'}
                        </PrimaryButton>

                        {!!meetingUuid && (
                            <PrimaryButton type="button" onClick={closeSession} disabled={!canRecord || loading || meetingClosed}>
                                {meetingClosed ? 'Closed' : 'Close Session'}
                            </PrimaryButton>
                        )}

                        {!!meetingUuid && (
                            <SecondaryButton type="button" onClick={() => {
                                setMeetingUuid('');
                                setMeetingClosed(false);
                                setBulkApplying(false);
                                setFamilies([]);
                                clearSelection();
                            }}>
                                Clear
                            </SecondaryButton>
                        )}
                    </div>

                    <InputError className="mt-3" message={pageError} />
                </div>

                {!!meetingUuid && (
                    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                        <div className="flex items-center justify-between">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-900">Families</h2>
                                <p className="text-sm text-slate-500">On opening, all eligible members are marked Absent by default. After 24 hours the session is locked.</p>
                            </div>
                            <div className="text-sm text-slate-600">
                                <span className="font-semibold">Meeting:</span> {meetingDate}
                            </div>
                        </div>

                        <div className="mt-4 flex flex-col gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="text-sm text-slate-700">
                                <span className="font-semibold">Selected:</span> {selectedMembers.size}
                            </div>
                            <div className="flex flex-wrap items-center gap-2">
                                <select
                                    className="h-10 rounded-lg border border-slate-300 bg-white px-2 text-sm"
                                    value={bulkStatus}
                                    onChange={(e) => setBulkStatus(e.target.value)}
                                    disabled={!canRecord || loading}
                                >
                                    <option value="">Bulk status...</option>
                                    {STATUS_OPTIONS.map((opt) => (
                                        <option key={opt.value} value={opt.value}>
                                            Mark as {opt.label}
                                        </option>
                                    ))}
                                </select>

                                <PrimaryButton type="button" onClick={bulkMarkSelected} disabled={!canRecord || loading || bulkApplying || meetingClosed || !bulkStatus || selectedMembers.size === 0}>
                                    {bulkApplying ? 'Applying...' : 'Apply to Selected'}
                                </PrimaryButton>

                                <SecondaryButton type="button" onClick={clearSelection} disabled={loading || selectedMembers.size === 0}>
                                    Clear Selection
                                </SecondaryButton>
                            </div>
                        </div>

                        <div className="mt-4 space-y-4">
                            {!!pagination && (
                                <div className="flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div className="text-sm text-slate-600">
                                        Showing {pagination.from ?? 0}-{pagination.to ?? 0} of {pagination.total ?? 0} families
                                        <span className="ml-2 text-xs text-slate-500">(Page {pagination.current_page ?? page} of {pagination.last_page ?? 1})</span>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <select
                                            className="h-9 rounded-lg border border-slate-300 bg-white px-2 text-sm"
                                            value={perPage}
                                            onChange={async (e) => {
                                                const next = Number(e.target.value);
                                                if (!Number.isFinite(next) || next < 1) return;
                                                if (!meetingUuid) return;
                                                setLoading(true);
                                                try {
                                                    clearSelection();
                                                    await fetchFamilyReport(meetingUuid, 1, next);
                                                } catch (err) {
                                                    const msg = err?.response?.data?.message || err?.message || 'Unable to load families.';
                                                    setPageError(String(msg));
                                                } finally {
                                                    setLoading(false);
                                                }
                                            }}
                                            disabled={loading}
                                        >
                                            {[10, 20, 30, 50].map((n) => (
                                                <option key={n} value={n}>{n} / page</option>
                                            ))}
                                        </select>

                                        <SecondaryButton
                                            type="button"
                                            disabled={loading || page <= 1}
                                            onClick={async () => {
                                                if (!meetingUuid) return;
                                                const next = Math.max(1, page - 1);
                                                setLoading(true);
                                                try {
                                                    clearSelection();
                                                    await fetchFamilyReport(meetingUuid, next, perPage);
                                                } catch (err) {
                                                    const msg = err?.response?.data?.message || err?.message || 'Unable to load families.';
                                                    setPageError(String(msg));
                                                } finally {
                                                    setLoading(false);
                                                }
                                            }}
                                        >
                                            Prev
                                        </SecondaryButton>

                                        <SecondaryButton
                                            type="button"
                                            disabled={loading || page >= (pagination?.last_page ?? 1)}
                                            onClick={async () => {
                                                if (!meetingUuid) return;
                                                const last = pagination?.last_page ?? page;
                                                const next = Math.min(last, page + 1);
                                                setLoading(true);
                                                try {
                                                    clearSelection();
                                                    await fetchFamilyReport(meetingUuid, next, perPage);
                                                } catch (err) {
                                                    const msg = err?.response?.data?.message || err?.message || 'Unable to load families.';
                                                    setPageError(String(msg));
                                                } finally {
                                                    setLoading(false);
                                                }
                                            }}
                                        >
                                            Next
                                        </SecondaryButton>
                                    </div>
                                </div>
                            )}

                            {families.length === 0 ? (
                                <div className="text-sm text-slate-500">No families found.</div>
                            ) : (
                                families.map((fam) => (
                                    <div key={fam.uuid} className="rounded-lg border border-slate-200">
                                        <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-2">
                                            <div className="font-semibold text-slate-800">Family: {toTitleCase(fam.family_name)}</div>
                                            <div className="text-xs text-slate-500">{Array.isArray(fam.members) ? fam.members.length : 0} members</div>
                                        </div>

                                        <div className="divide-y divide-slate-100">
                                            {(Array.isArray(fam.members) ? fam.members : []).map((m) => (
                                                <div key={m.uuid} className="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                                    <div>
                                                        <div className="font-semibold text-slate-900">
                                                            {toTitleCase([m.first_name, m.middle_name, m.last_name].filter(Boolean).join(' '))}
                                                        </div>
                                                        <div className="text-xs text-slate-500">Status: {m.status || 'Not marked'}</div>
                                                        {m.eligible === false && (
                                                            <div className="mt-1 text-xs font-semibold text-amber-700">Not eligible for this Christian Community on this date.</div>
                                                        )}
                                                    </div>

                                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                                        <label className="flex items-center gap-2 text-sm text-slate-600">
                                                            <input
                                                                type="checkbox"
                                                                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                                                checked={selectedMembers.has(m.uuid)}
                                                                onChange={() => toggleSelectedMember(m.uuid)}
                                                                disabled={loading || m.eligible !== true}
                                                            />
                                                            Select
                                                        </label>

                                                        <div className="flex flex-wrap gap-2">
                                                            {STATUS_OPTIONS.map((opt) => {
                                                                const active = m.status === opt.value;
                                                                const base = `h-9 rounded-lg px-3 text-xs font-semibold ring-1 transition ${STATUS_STYLES[opt.value]}`;
                                                                const cls = active ? `${base} ring-2 ring-indigo-400` : base;

                                                                return (
                                                                    <button
                                                                        key={opt.value}
                                                                        type="button"
                                                                        className={cls}
                                                                        onClick={() => setMemberStatus(m.uuid, opt.value)}
                                                                        disabled={!canRecord || loading || m.eligible !== true}
                                                                    >
                                                                        {opt.label}
                                                                    </button>
                                                                );
                                                            })}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
