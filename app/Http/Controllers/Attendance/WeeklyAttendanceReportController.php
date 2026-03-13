<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Attendance\JumuiyaWeeklyAttendance;
use App\Models\Attendance\JumuiyaWeeklyAttendanceAudit;
use App\Models\Attendance\JumuiyaWeeklyMeeting;
use App\Models\Structure\Jumuiya;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyAttendanceReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function scopedJumuiyaId(Request $request): ?int
    {
        if ($request->user()?->can('jumuiyas.view')) {
            return null;
        }

        return $request->user()?->member?->jumuiya_id;
    }

    public function communityIndex(Request $request): Response
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $scopedJumuiya = null;
        if ($scopedJumuiyaId) {
            $scopedJumuiya = Jumuiya::query()->where('id', $scopedJumuiyaId)->first(['uuid', 'name']);
        }

        return Inertia::render('WeeklyAttendance/Reports/CommunitySummary', [
            'scoped_jumuiya' => $scopedJumuiya ? [
                'uuid' => $scopedJumuiya->uuid,
                'name' => $scopedJumuiya->name,
            ] : null,
            'can_select_jumuiya' => (bool) $request->user()?->can('jumuiyas.view'),
        ]);
    }

    public function communityData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'jumuiya_uuid' => ['nullable', 'string'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();

        if ($from->greaterThan($to)) {
            return response()->json(['message' => 'Invalid date range.'], 422);
        }

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $jumuiyaId = null;

        if ($scopedJumuiyaId) {
            $jumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $jumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $jumuiyaId) {
            return response()->json(['message' => 'Christian Community is required.'], 422);
        }

        $members = Member::query()
            ->where('is_active', true)
            ->whereHas('family', function ($q) use ($jumuiyaId) {
                $q->where('jumuiya_id', $jumuiyaId);
            })
            ->with(['jumuiyaHistories' => function ($q) use ($to) {
                $q->where('effective_date', '<=', $to->toDateString())
                    ->orderByDesc('effective_date')
                    ->orderByDesc('id');
            }])
            ->get(['id', 'jumuiya_id']);

        $meetingIds = JumuiyaWeeklyMeeting::query()
            ->where('jumuiya_id', $jumuiyaId)
            ->whereBetween('meeting_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('meeting_date')
            ->pluck('id')
            ->all();

        if (count($meetingIds) === 0) {
            return response()->json([
                'jumuiya_id' => $jumuiyaId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'rows' => [],
                'totals' => [
                    'eligible' => 0,
                    'present' => 0,
                    'absent' => 0,
                    'sick' => 0,
                    'travel' => 0,
                    'other' => 0,
                    'attendance_percent' => 0,
                ],
            ]);
        }

        $statusAgg = DB::table('jumuiya_weekly_attendances')
            ->select([
                'jumuiya_weekly_meeting_id as meeting_id',
                'status',
                DB::raw('COUNT(*) as c'),
            ])
            ->whereIn('jumuiya_weekly_meeting_id', $meetingIds)
            ->groupBy('jumuiya_weekly_meeting_id', 'status')
            ->get();

        $map = [];
        foreach ($statusAgg as $r) {
            $mid = (int) $r->meeting_id;
            $st = (string) $r->status;
            $map[$mid] ??= [];
            $map[$mid][$st] = (int) $r->c;
        }

        $meetingsWithAnyAttendance = array_fill_keys(array_keys($map), true);

        $meetings = JumuiyaWeeklyMeeting::query()
            ->whereIn('id', $meetingIds)
            ->orderBy('meeting_date')
            ->get(['id', 'meeting_date', 'closed_at', 'locked_at']);

        $rows = [];
        $totEligible = 0;
        $totPresent = 0;
        $totAbsent = 0;
        $totSick = 0;
        $totTravel = 0;
        $totOther = 0;
        $eligibleCache = [];

        foreach ($meetings as $m) {
            $mid = (int) $m->id;
            $counts = $map[$mid] ?? [];

            $present = (int) ($counts['present'] ?? 0);
            $absent = (int) ($counts['absent'] ?? 0);
            $sick = (int) ($counts['sick'] ?? 0);
            $travel = (int) ($counts['travel'] ?? 0);
            $other = (int) ($counts['other'] ?? 0);

            $eligible = $present + $absent + $sick + $travel + $other;

            if ($eligible === 0 && ! isset($meetingsWithAnyAttendance[$mid])) {
                $key = Carbon::parse($m->meeting_date)->toDateString();
                if (! array_key_exists($key, $eligibleCache)) {
                    $eligibleCache[$key] = $this->eligibleMemberCountAsOf($members, Carbon::parse($m->meeting_date)->endOfDay(), (int) $jumuiyaId);
                }

                $eligible = (int) $eligibleCache[$key];
                $absent = $eligible;
            }

            $attendancePercent = $eligible > 0 ? round(($present / $eligible) * 100, 1) : 0;

            $rows[] = [
                'meeting_date' => Carbon::parse($m->meeting_date)->toDateString(),
                'eligible' => $eligible,
                'present' => $present,
                'absent' => $absent,
                'sick' => $sick,
                'travel' => $travel,
                'other' => $other,
                'attendance_percent' => $attendancePercent,
                'closed_at' => $m->closed_at ? (string) $m->closed_at : null,
                'locked_at' => $m->locked_at ? (string) $m->locked_at : null,
            ];

            $totEligible += $eligible;
            $totPresent += $present;
            $totAbsent += $absent;
            $totSick += $sick;
            $totTravel += $travel;
            $totOther += $other;
        }

        $totAttendancePercent = $totEligible > 0 ? round(($totPresent / $totEligible) * 100, 1) : 0;

        return response()->json([
            'jumuiya_id' => $jumuiyaId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
            'totals' => [
                'eligible' => $totEligible,
                'present' => $totPresent,
                'absent' => $totAbsent,
                'sick' => $totSick,
                'travel' => $totTravel,
                'other' => $totOther,
                'attendance_percent' => $totAttendancePercent,
            ],
        ]);
    }

    public function communityExport(Request $request)
    {
        if (! class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return response()->json([
                'message' => 'Excel export is not installed. Please run: composer require maatwebsite/excel',
            ], 501);
        }

        $dataRes = $this->communityData($request);
        $payload = $dataRes->getData(true);

        if (! empty($payload['message'])) {
            return $dataRes;
        }

        $rows = $payload['rows'] ?? [];
        $from = (string) ($payload['from'] ?? '');
        $to = (string) ($payload['to'] ?? '');

        $exportRows = array_map(function ($r) {
            return [
                $r['meeting_date'] ?? '',
                $r['eligible'] ?? 0,
                $r['present'] ?? 0,
                $r['absent'] ?? 0,
                $r['sick'] ?? 0,
                $r['travel'] ?? 0,
                $r['other'] ?? 0,
                $r['attendance_percent'] ?? 0,
            ];
        }, $rows);

        $export = new \App\Exports\WeeklyAttendanceCommunitySummaryExport($exportRows);

        $filename = "weekly-attendance-community-summary_{$from}_to_{$to}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function auditLogsIndex(Request $request): Response
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $scopedJumuiya = null;
        if ($scopedJumuiyaId) {
            $scopedJumuiya = Jumuiya::query()->where('id', $scopedJumuiyaId)->first(['uuid', 'name']);
        }

        return Inertia::render('WeeklyAttendance/Reports/AuditLogs', [
            'scoped_jumuiya' => $scopedJumuiya ? [
                'uuid' => $scopedJumuiya->uuid,
                'name' => $scopedJumuiya->name,
            ] : null,
            'can_select_jumuiya' => (bool) $request->user()?->can('jumuiyas.view'),
        ]);
    }

    public function auditLogsData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'jumuiya_uuid' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();
        if ($from->greaterThan($to)) {
            return response()->json(['message' => 'Invalid date range.'], 422);
        }

        $perPage = (int) ($validated['per_page'] ?? 20);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $jumuiyaId = null;

        if ($scopedJumuiyaId) {
            $jumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $jumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $jumuiyaId) {
            return response()->json(['message' => 'Christian Community is required.'], 422);
        }

        $qStr = isset($validated['q']) && is_string($validated['q']) ? trim($validated['q']) : '';
        $action = isset($validated['action']) && is_string($validated['action']) ? trim($validated['action']) : '';

        $query = DB::table('jumuiya_weekly_meetings as jwm')
            ->join('jumuiya_weekly_attendance_audits as jwaa', 'jwaa.jumuiya_weekly_meeting_id', '=', 'jwm.id')
            ->join('members', 'members.id', '=', 'jwaa.member_id')
            ->leftJoin('users', 'users.id', '=', 'jwaa.performed_by_user_id')
            ->where('jwm.jumuiya_id', $jumuiyaId)
            ->whereBetween('jwm.meeting_date', [$from->toDateString(), $to->toDateString()])
            ->select([
                'jwaa.uuid as uuid',
                'jwm.meeting_date as meeting_date',
                'jwaa.performed_at as performed_at',
                'jwaa.action as action',
                'jwaa.old_status as old_status',
                'jwaa.new_status as new_status',
                'jwaa.notes as notes',
                'users.email as performed_by_email',
                'members.first_name as member_first_name',
                'members.middle_name as member_middle_name',
                'members.last_name as member_last_name',
            ])
            ->orderByDesc('jwaa.performed_at')
            ->orderByDesc('jwaa.id');

        if ($action !== '') {
            $query->where('jwaa.action', $action);
        }

        if ($qStr !== '') {
            $safe = addcslashes($qStr, '%_\\');
            $like = $safe.'%';

            $query->where(function ($w) use ($like) {
                $w->where('members.first_name', 'like', $like)
                    ->orWhere('members.middle_name', 'like', $like)
                    ->orWhere('members.last_name', 'like', $like)
                    ->orWhere('users.email', 'like', $like);
            });
        }

        $paginated = $query->paginate($perPage);

        $rows = collect($paginated->items())->map(function ($r) {
            $memberName = trim(implode(' ', array_filter([
                (string) ($r->member_first_name ?? ''),
                (string) ($r->member_middle_name ?? ''),
                (string) ($r->member_last_name ?? ''),
            ])));

            return [
                'uuid' => (string) ($r->uuid ?? ''),
                'meeting_date' => $r->meeting_date ? Carbon::parse($r->meeting_date)->toDateString() : null,
                'performed_at' => $r->performed_at ? Carbon::parse($r->performed_at)->toDateTimeString() : null,
                'member_name' => $memberName,
                'action' => (string) ($r->action ?? ''),
                'old_status' => $r->old_status,
                'new_status' => $r->new_status,
                'performed_by' => $r->performed_by_email,
                'notes' => $r->notes,
            ];
        })->values();

        return response()->json([
            'jumuiya_id' => $jumuiyaId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
            'pagination' => [
                'current_page' => (int) $paginated->currentPage(),
                'last_page' => (int) $paginated->lastPage(),
                'per_page' => (int) $paginated->perPage(),
                'total' => (int) $paginated->total(),
                'from' => $paginated->firstItem() ? (int) $paginated->firstItem() : null,
                'to' => $paginated->lastItem() ? (int) $paginated->lastItem() : null,
            ],
        ]);
    }

    protected function eligibleMemberCountAsOf($members, Carbon $asOf, int $jumuiyaId): int
    {
        if (! $members || count($members) === 0) {
            return 0;
        }

        $asOfStr = $asOf->toDateString();

        $count = 0;
        foreach ($members as $m) {
            $effective = (int) $m->jumuiya_id;

            $histories = $m->getRelation('jumuiyaHistories');
            foreach ($histories as $h) {
                if ((string) $h->effective_date <= $asOfStr) {
                    $effective = (int) $h->to_jumuiya_id;
                    break;
                }
            }

            if ($effective === $jumuiyaId) {
                $count++;
            }
        }

        return $count;
    }

    public function actionListIndex(Request $request): Response
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $scopedJumuiya = null;
        if ($scopedJumuiyaId) {
            $scopedJumuiya = Jumuiya::query()->where('id', $scopedJumuiyaId)->first(['uuid', 'name']);
        }

        return Inertia::render('WeeklyAttendance/Reports/ActionList', [
            'scoped_jumuiya' => $scopedJumuiya ? [
                'uuid' => $scopedJumuiya->uuid,
                'name' => $scopedJumuiya->name,
            ] : null,
            'can_select_jumuiya' => (bool) $request->user()?->can('jumuiyas.view'),
        ]);
    }

    public function actionListData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $validated = $request->validate([
            'as_of' => ['required', 'date'],
            'weeks' => ['nullable', 'integer', 'min:1', 'max:52'],
            'min_consecutive_absences' => ['nullable', 'integer', 'min:1', 'max:52'],
            'jumuiya_uuid' => ['nullable', 'string'],
        ]);

        $asOf = Carbon::parse($validated['as_of'])->endOfDay();
        $weeks = (int) ($validated['weeks'] ?? 12);
        $minConsecutive = (int) ($validated['min_consecutive_absences'] ?? 3);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $jumuiyaId = null;

        if ($scopedJumuiyaId) {
            $jumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $jumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $jumuiyaId) {
            return response()->json(['message' => 'Christian Community is required.'], 422);
        }

        $from = (clone $asOf)->startOfDay()->subWeeks($weeks - 1);

        $meetingIds = JumuiyaWeeklyMeeting::query()
            ->where('jumuiya_id', $jumuiyaId)
            ->whereBetween('meeting_date', [$from->toDateString(), $asOf->toDateString()])
            ->orderBy('meeting_date')
            ->pluck('id')
            ->all();

        if (count($meetingIds) === 0) {
            return response()->json([
                'from' => $from->toDateString(),
                'to' => $asOf->toDateString(),
                'weeks' => $weeks,
                'min_consecutive_absences' => $minConsecutive,
                'rows' => [],
            ]);
        }

        $members = Member::query()
            ->where('is_active', true)
            ->whereHas('family', function ($q) use ($jumuiyaId) {
                $q->where('jumuiya_id', $jumuiyaId);
            })
            ->with(['family:id,uuid,family_name'])
            ->with(['jumuiyaHistories' => function ($q) use ($asOf) {
                $q->where('effective_date', '<=', $asOf->toDateString())
                    ->orderByDesc('effective_date')
                    ->orderByDesc('id');
            }])
            ->get(['id', 'uuid', 'first_name', 'middle_name', 'last_name', 'family_id', 'jumuiya_id']);

        $asOfStr = $asOf->toDateString();
        $eligibleMembers = $members->filter(function (Member $m) use ($asOfStr, $jumuiyaId) {
            $effective = (int) $m->jumuiya_id;

            $histories = $m->getRelation('jumuiyaHistories');
            foreach ($histories as $h) {
                if ((string) $h->effective_date <= $asOfStr) {
                    $effective = (int) $h->to_jumuiya_id;
                    break;
                }
            }

            return $effective === (int) $jumuiyaId;
        })->values();

        if ($eligibleMembers->count() === 0) {
            return response()->json([
                'from' => $from->toDateString(),
                'to' => $asOf->toDateString(),
                'weeks' => $weeks,
                'min_consecutive_absences' => $minConsecutive,
                'rows' => [],
            ]);
        }

        $attendance = JumuiyaWeeklyAttendance::query()
            ->whereIn('jumuiya_weekly_meeting_id', $meetingIds)
            ->whereIn('member_id', $eligibleMembers->pluck('id')->all())
            ->get(['member_id', 'jumuiya_weekly_meeting_id', 'status'])
            ->groupBy('member_id');

        $rows = [];
        foreach ($eligibleMembers as $m) {
            $memberRecords = $attendance->get($m->id, collect());

            $byMeeting = [];
            foreach ($memberRecords as $rec) {
                $mid = (int) $rec->jumuiya_weekly_meeting_id;
                $byMeeting[$mid] = (string) $rec->status;
            }

            $consecutiveAbsent = 0;
            for ($i = count($meetingIds) - 1; $i >= 0; $i--) {
                $mid = (int) $meetingIds[$i];
                $st = $byMeeting[$mid] ?? null;
                if ($st === 'absent') {
                    $consecutiveAbsent++;
                    continue;
                }

                break;
            }

            if ($consecutiveAbsent < $minConsecutive) {
                continue;
            }

            $fullName = trim(implode(' ', array_filter([
                $m->first_name,
                $m->middle_name,
                $m->last_name,
            ])));

            $rows[] = [
                'member_uuid' => $m->uuid,
                'member_name' => $fullName,
                'family_name' => $m->family?->family_name,
                'consecutive_absences' => $consecutiveAbsent,
            ];
        }

        usort($rows, function ($a, $b) {
            return ($b['consecutive_absences'] <=> $a['consecutive_absences'])
                ?: strcmp((string) $a['member_name'], (string) $b['member_name']);
        });

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $asOf->toDateString(),
            'weeks' => $weeks,
            'min_consecutive_absences' => $minConsecutive,
            'rows' => $rows,
        ]);
    }

    public function actionListExport(Request $request)
    {
        if (! class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return response()->json([
                'message' => 'Excel export is not installed. Please run: composer require maatwebsite/excel',
            ], 501);
        }

        $dataRes = $this->actionListData($request);
        $payload = $dataRes->getData(true);

        if (! empty($payload['message'])) {
            return $dataRes;
        }

        $rows = $payload['rows'] ?? [];
        $from = (string) ($payload['from'] ?? '');
        $to = (string) ($payload['to'] ?? '');

        $exportRows = array_map(function ($r) {
            return [
                $r['member_name'] ?? '',
                $r['family_name'] ?? '',
                $r['consecutive_absences'] ?? 0,
            ];
        }, $rows);

        $export = new \App\Exports\WeeklyAttendanceActionListExport($exportRows);
        $filename = "weekly-attendance-action-list_{$from}_to_{$to}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function familyIndex(Request $request): Response
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $scopedJumuiya = null;
        if ($scopedJumuiyaId) {
            $scopedJumuiya = Jumuiya::query()->where('id', $scopedJumuiyaId)->first(['uuid', 'name']);
        }

        return Inertia::render('WeeklyAttendance/Reports/Families', [
            'scoped_jumuiya' => $scopedJumuiya ? [
                'uuid' => $scopedJumuiya->uuid,
                'name' => $scopedJumuiya->name,
            ] : null,
            'can_select_jumuiya' => (bool) $request->user()?->can('jumuiyas.view'),
        ]);
    }

    public function familyData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'jumuiya_uuid' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();

        if ($from->greaterThan($to)) {
            return response()->json(['message' => 'Invalid date range.'], 422);
        }

        $perPage = (int) ($validated['per_page'] ?? 20);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $jumuiyaId = null;

        if ($scopedJumuiyaId) {
            $jumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $jumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $jumuiyaId) {
            return response()->json(['message' => 'Christian Community is required.'], 422);
        }

        $q = DB::table('jumuiya_weekly_meetings as jwm')
            ->select([
                'families.uuid as family_uuid',
                'families.family_name as family_name',
                DB::raw("SUM(CASE WHEN jwa.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN jwa.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN jwa.status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN jwa.status = 'travel' THEN 1 ELSE 0 END) as travel"),
                DB::raw("SUM(CASE WHEN jwa.status = 'other' THEN 1 ELSE 0 END) as other"),
                DB::raw('COUNT(jwa.id) as eligible'),
            ])
            ->join('jumuiya_weekly_attendances as jwa', 'jwa.jumuiya_weekly_meeting_id', '=', 'jwm.id')
            ->join('members', 'members.id', '=', 'jwa.member_id')
            ->join('families', 'families.id', '=', 'members.family_id')
            ->where('jwm.jumuiya_id', $jumuiyaId)
            ->whereBetween('jwm.meeting_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('families.uuid', 'families.family_name')
            ->orderBy('families.family_name');

        $paginated = $q->paginate($perPage);

        $rows = collect($paginated->items())->map(function ($r) {
            $eligible = (int) ($r->eligible ?? 0);
            $present = (int) ($r->present ?? 0);
            $absent = (int) ($r->absent ?? 0);
            $sick = (int) ($r->sick ?? 0);
            $travel = (int) ($r->travel ?? 0);
            $other = (int) ($r->other ?? 0);
            $attendancePercent = $eligible > 0 ? round(($present / $eligible) * 100, 1) : 0;

            return [
                'family_uuid' => (string) $r->family_uuid,
                'family_name' => (string) $r->family_name,
                'eligible' => $eligible,
                'present' => $present,
                'absent' => $absent,
                'sick' => $sick,
                'travel' => $travel,
                'other' => $other,
                'attendance_percent' => $attendancePercent,
            ];
        })->values();

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function familyExport(Request $request)
    {
        if (! class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return response()->json([
                'message' => 'Excel export is not installed. Please run: composer require maatwebsite/excel',
            ], 501);
        }

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'jumuiya_uuid' => ['nullable', 'string'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $jumuiyaId = null;

        if ($scopedJumuiyaId) {
            $jumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $jumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $jumuiyaId) {
            return response()->json(['message' => 'Christian Community is required.'], 422);
        }

        $rows = DB::table('jumuiya_weekly_meetings as jwm')
            ->select([
                'families.family_name as family_name',
                DB::raw("SUM(CASE WHEN jwa.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN jwa.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN jwa.status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN jwa.status = 'travel' THEN 1 ELSE 0 END) as travel"),
                DB::raw("SUM(CASE WHEN jwa.status = 'other' THEN 1 ELSE 0 END) as other"),
                DB::raw('COUNT(jwa.id) as eligible'),
            ])
            ->join('jumuiya_weekly_attendances as jwa', 'jwa.jumuiya_weekly_meeting_id', '=', 'jwm.id')
            ->join('members', 'members.id', '=', 'jwa.member_id')
            ->join('families', 'families.id', '=', 'members.family_id')
            ->where('jwm.jumuiya_id', $jumuiyaId)
            ->whereBetween('jwm.meeting_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('families.family_name')
            ->orderBy('families.family_name')
            ->get();

        $exportRows = $rows->map(function ($r) {
            $eligible = (int) ($r->eligible ?? 0);
            $present = (int) ($r->present ?? 0);
            $absent = (int) ($r->absent ?? 0);
            $sick = (int) ($r->sick ?? 0);
            $travel = (int) ($r->travel ?? 0);
            $other = (int) ($r->other ?? 0);
            $attendancePercent = $eligible > 0 ? round(($present / $eligible) * 100, 1) : 0;

            return [
                (string) ($r->family_name ?? ''),
                $eligible,
                $present,
                $absent,
                $sick,
                $travel,
                $other,
                $attendancePercent,
            ];
        })->all();

        $export = new \App\Exports\WeeklyAttendanceFamilyReportExport($exportRows);
        $filename = 'weekly-attendance-families_' . $from->toDateString() . '_to_' . $to->toDateString() . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }

    public function memberIndex(Request $request): Response
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $scopedJumuiya = null;
        if ($scopedJumuiyaId) {
            $scopedJumuiya = Jumuiya::query()->where('id', $scopedJumuiyaId)->first(['uuid', 'name']);
        }

        return Inertia::render('WeeklyAttendance/Reports/Members', [
            'scoped_jumuiya' => $scopedJumuiya ? [
                'uuid' => $scopedJumuiya->uuid,
                'name' => $scopedJumuiya->name,
            ] : null,
            'can_select_jumuiya' => (bool) $request->user()?->can('jumuiyas.view'),
        ]);
    }

    public function memberData(Request $request): JsonResponse
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'jumuiya_uuid' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();

        if ($from->greaterThan($to)) {
            return response()->json(['message' => 'Invalid date range.'], 422);
        }

        $perPage = (int) ($validated['per_page'] ?? 20);
        $qStr = trim((string) ($validated['q'] ?? ''));

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $jumuiyaId = null;

        if ($scopedJumuiyaId) {
            $jumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $jumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $jumuiyaId) {
            return response()->json(['message' => 'Christian Community is required.'], 422);
        }

        $query = DB::table('jumuiya_weekly_meetings as jwm')
            ->select([
                'members.uuid as member_uuid',
                'members.first_name',
                'members.middle_name',
                'members.last_name',
                'families.family_name as family_name',
                DB::raw("SUM(CASE WHEN jwa.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN jwa.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN jwa.status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN jwa.status = 'travel' THEN 1 ELSE 0 END) as travel"),
                DB::raw("SUM(CASE WHEN jwa.status = 'other' THEN 1 ELSE 0 END) as other"),
                DB::raw('COUNT(jwa.id) as eligible'),
            ])
            ->join('jumuiya_weekly_attendances as jwa', 'jwa.jumuiya_weekly_meeting_id', '=', 'jwm.id')
            ->join('members', 'members.id', '=', 'jwa.member_id')
            ->join('families', 'families.id', '=', 'members.family_id')
            ->where('jwm.jumuiya_id', $jumuiyaId)
            ->whereBetween('jwm.meeting_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('members.uuid', 'members.first_name', 'members.middle_name', 'members.last_name', 'families.family_name')
            ->orderBy('members.last_name')
            ->orderBy('members.first_name');

        if ($qStr !== '') {
            $like = str_replace(['%', '_'], ['\\%', '\\_'], $qStr) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('members.first_name', 'like', $like)
                    ->orWhere('members.middle_name', 'like', $like)
                    ->orWhere('members.last_name', 'like', $like)
                    ->orWhere('families.family_name', 'like', $like);
            });
        }

        $paginated = $query->paginate($perPage);

        $rows = collect($paginated->items())->map(function ($r) {
            $eligible = (int) ($r->eligible ?? 0);
            $present = (int) ($r->present ?? 0);
            $absent = (int) ($r->absent ?? 0);
            $sick = (int) ($r->sick ?? 0);
            $travel = (int) ($r->travel ?? 0);
            $other = (int) ($r->other ?? 0);
            $attendancePercent = $eligible > 0 ? round(($present / $eligible) * 100, 1) : 0;

            $fullName = trim(implode(' ', array_filter([
                (string) ($r->first_name ?? ''),
                (string) ($r->middle_name ?? ''),
                (string) ($r->last_name ?? ''),
            ])));

            return [
                'member_uuid' => (string) $r->member_uuid,
                'member_name' => $fullName,
                'family_name' => (string) ($r->family_name ?? ''),
                'eligible' => $eligible,
                'present' => $present,
                'absent' => $absent,
                'sick' => $sick,
                'travel' => $travel,
                'other' => $other,
                'attendance_percent' => $attendancePercent,
            ];
        })->values();

        return response()->json([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'q' => $qStr,
            'rows' => $rows,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function memberExport(Request $request)
    {
        if (! class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return response()->json([
                'message' => 'Excel export is not installed. Please run: composer require maatwebsite/excel',
            ], 501);
        }

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'jumuiya_uuid' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();
        $qStr = trim((string) ($validated['q'] ?? ''));

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $jumuiyaId = null;

        if ($scopedJumuiyaId) {
            $jumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $jumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $jumuiyaId) {
            return response()->json(['message' => 'Christian Community is required.'], 422);
        }

        $query = DB::table('jumuiya_weekly_meetings as jwm')
            ->select([
                'members.first_name',
                'members.middle_name',
                'members.last_name',
                'families.family_name as family_name',
                DB::raw("SUM(CASE WHEN jwa.status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN jwa.status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN jwa.status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN jwa.status = 'travel' THEN 1 ELSE 0 END) as travel"),
                DB::raw("SUM(CASE WHEN jwa.status = 'other' THEN 1 ELSE 0 END) as other"),
                DB::raw('COUNT(jwa.id) as eligible'),
            ])
            ->join('jumuiya_weekly_attendances as jwa', 'jwa.jumuiya_weekly_meeting_id', '=', 'jwm.id')
            ->join('members', 'members.id', '=', 'jwa.member_id')
            ->join('families', 'families.id', '=', 'members.family_id')
            ->where('jwm.jumuiya_id', $jumuiyaId)
            ->whereBetween('jwm.meeting_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('members.first_name', 'members.middle_name', 'members.last_name', 'families.family_name')
            ->orderBy('members.last_name')
            ->orderBy('members.first_name');

        if ($qStr !== '') {
            $like = str_replace(['%', '_'], ['\\%', '\\_'], $qStr) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('members.first_name', 'like', $like)
                    ->orWhere('members.middle_name', 'like', $like)
                    ->orWhere('members.last_name', 'like', $like)
                    ->orWhere('families.family_name', 'like', $like);
            });
        }

        $rows = $query->get();

        $exportRows = $rows->map(function ($r) {
            $eligible = (int) ($r->eligible ?? 0);
            $present = (int) ($r->present ?? 0);
            $absent = (int) ($r->absent ?? 0);
            $sick = (int) ($r->sick ?? 0);
            $travel = (int) ($r->travel ?? 0);
            $other = (int) ($r->other ?? 0);
            $attendancePercent = $eligible > 0 ? round(($present / $eligible) * 100, 1) : 0;

            $fullName = trim(implode(' ', array_filter([
                (string) ($r->first_name ?? ''),
                (string) ($r->middle_name ?? ''),
                (string) ($r->last_name ?? ''),
            ])));

            return [
                $fullName,
                (string) ($r->family_name ?? ''),
                $eligible,
                $present,
                $absent,
                $sick,
                $travel,
                $other,
                $attendancePercent,
            ];
        })->all();

        $export = new \App\Exports\WeeklyAttendanceMemberReportExport($exportRows);
        $filename = 'weekly-attendance-members_' . $from->toDateString() . '_to_' . $to->toDateString() . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
}
