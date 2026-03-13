<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\BulkMarkWeeklyAttendanceRequest;
use App\Http\Requests\Attendance\MarkWeeklyAttendanceRequest;
use App\Http\Requests\Attendance\OpenWeeklyMeetingRequest;
use App\Models\Attendance\JumuiyaWeeklyAttendance;
use App\Models\Attendance\JumuiyaWeeklyAttendanceAudit;
use App\Models\Attendance\JumuiyaWeeklyMeeting;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WeeklyAttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', JumuiyaWeeklyMeeting::class);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $scopedJumuiya = null;
        if ($scopedJumuiyaId) {
            $scopedJumuiya = Jumuiya::query()->where('id', $scopedJumuiyaId)->first(['uuid', 'name']);
        }

        return Inertia::render('WeeklyAttendance/Index', [
            'scoped_jumuiya' => $scopedJumuiya ? [
                'uuid' => $scopedJumuiya->uuid,
                'name' => $scopedJumuiya->name,
            ] : null,
            'can_select_jumuiya' => (bool) $request->user()?->can('jumuiyas.view'),
        ]);
    }

    protected function scopedJumuiyaId(Request $request): ?int
    {
        if ($request->user()?->can('jumuiyas.view')) {
            return null;
        }

        return $request->user()?->member?->jumuiya_id;
    }

    protected function assertNotLocked(Request $request, JumuiyaWeeklyMeeting $meeting): void
    {
        if ($meeting->locked_at) {
            if (! $request->user()?->can('weekly-attendance.override-lock')) {
                throw new \RuntimeException('This weekly attendance session is locked.');
            }

            return;
        }

        $lockAt = Carbon::parse($meeting->meeting_date)->addHours(24);
        if (now()->greaterThan($lockAt) && ! $request->user()?->can('weekly-attendance.override-lock')) {
            throw new \RuntimeException('This weekly attendance session is locked.');
        }
    }

    public function open(OpenWeeklyMeetingRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', JumuiyaWeeklyMeeting::class);

        $validated = $request->validated();

        $meetingDate = Carbon::parse($validated['meeting_date'])->toDateString();
        if (! Carbon::parse($meetingDate)->isSaturday() && ! $request->user()?->can('weekly-attendance.override-lock')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Weekly attendance can only be recorded on Saturday.'], 422);
            }

            return back()->with('error', 'Weekly attendance can only be recorded on Saturday.');
        }

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        $targetJumuiyaId = null;
        if ($scopedJumuiyaId) {
            $targetJumuiyaId = (int) $scopedJumuiyaId;
        } else {
            $targetJumuiyaId = ! empty($validated['jumuiya_uuid'])
                ? (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id')
                : 0;
        }

        if (! $targetJumuiyaId) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Christian Community is required.'], 422);
            }

            return back()->with('error', 'Christian Community is required.');
        }

        try {
            $meeting = DB::transaction(function () use ($request, $meetingDate, $targetJumuiyaId) {
                $existing = JumuiyaWeeklyMeeting::query()
                    ->where('jumuiya_id', $targetJumuiyaId)
                    ->whereDate('meeting_date', $meetingDate)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                return JumuiyaWeeklyMeeting::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'jumuiya_id' => $targetJumuiyaId,
                    'meeting_date' => $meetingDate,
                    'opened_by_user_id' => (int) $request->user()->id,
                    'closed_at' => null,
                    'locked_at' => null,
                ]);
            });

            if (! $meeting->closed_at) {
                DB::transaction(function () use ($request, $meeting): void {
                    $meetingDateCarbon = Carbon::parse($meeting->meeting_date);

                    $familyIds = Family::query()
                        ->where('jumuiya_id', $meeting->jumuiya_id)
                        ->pluck('id')
                        ->all();

                    if (count($familyIds) === 0) {
                        return;
                    }

                    $members = Member::query()
                        ->whereIn('family_id', $familyIds)
                        ->where('is_active', true)
                        ->get(['id', 'uuid', 'jumuiya_id']);

                    $eligibleMembers = $members->filter(function (Member $m) use ($meetingDateCarbon, $meeting) {
                        return $m->effectiveJumuiyaIdAsOf($meetingDateCarbon) === (int) $meeting->jumuiya_id;
                    });

                    if ($eligibleMembers->count() === 0) {
                        return;
                    }

                    $alreadyMarkedMemberIds = JumuiyaWeeklyAttendance::query()
                        ->where('jumuiya_weekly_meeting_id', $meeting->id)
                        ->pluck('member_id')
                        ->all();

                    $alreadyMarkedSet = array_fill_keys($alreadyMarkedMemberIds, true);

                    $toAutoAbsent = $eligibleMembers->filter(function (Member $m) use ($alreadyMarkedSet) {
                        return ! isset($alreadyMarkedSet[$m->id]);
                    });

                    foreach ($toAutoAbsent as $member) {
                        $row = JumuiyaWeeklyAttendance::query()->create([
                            'uuid' => (string) Str::uuid(),
                            'jumuiya_weekly_meeting_id' => $meeting->id,
                            'member_id' => $member->id,
                            'status' => 'absent',
                            'marked_by_user_id' => (int) $request->user()->id,
                            'marked_at' => now(),
                        ]);

                        JumuiyaWeeklyAttendanceAudit::query()->create([
                            'uuid' => (string) Str::uuid(),
                            'jumuiya_weekly_meeting_id' => $meeting->id,
                            'member_id' => $member->id,
                            'jumuiya_weekly_attendance_id' => $row->id,
                            'action' => 'created',
                            'old_status' => null,
                            'new_status' => 'absent',
                            'performed_by_user_id' => (int) $request->user()->id,
                            'performed_at' => now(),
                            'ip_address' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                            'notes' => 'Auto-marked absent on open',
                        ]);
                    }
                });
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'meeting_uuid' => $meeting->uuid,
                    'meeting_date' => $meetingDate,
                ]);
            }

            return back()->with('success', 'Weekly attendance session is ready.')->with('weekly_meeting_uuid', $meeting->uuid);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Unable to open weekly attendance session. Please try again.'], 500);
            }

            return back()->with('error', 'Unable to open weekly attendance session. Please try again.');
        }
    }

    public function mark(MarkWeeklyAttendanceRequest $request, JumuiyaWeeklyMeeting $meeting): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validated();

        if (! Carbon::parse($meeting->meeting_date)->isSaturday() && ! $request->user()?->can('weekly-attendance.override-lock')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid weekly meeting date.'], 422);
            }

            return back()->with('error', 'Invalid weekly meeting date.');
        }

        try {
            $this->assertNotLocked($request, $meeting);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 423);
            }

            return back()->with('error', $e->getMessage());
        }

        $member = Member::query()->where('uuid', $validated['member_uuid'])->first();
        if (! $member) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid member.'], 422);
            }

            return back()->with('error', 'Invalid member.');
        }

        $effectiveJumuiyaId = $member->effectiveJumuiyaIdAsOf(Carbon::parse($meeting->meeting_date));
        if ($effectiveJumuiyaId !== (int) $meeting->jumuiya_id) {
            Log::warning('Weekly attendance mark blocked: member not in meeting jumuiya as-of date', [
                'meeting_uuid' => $meeting->uuid,
                'meeting_id' => $meeting->id,
                'meeting_date' => (string) $meeting->meeting_date,
                'meeting_jumuiya_id' => (int) $meeting->jumuiya_id,
                'member_uuid' => $member->uuid,
                'member_id' => $member->id,
                'effective_jumuiya_id_as_of_meeting_date' => (int) $effectiveJumuiyaId,
                'user_id' => (int) $request->user()->id,
                'ip' => $request->ip(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid member selection for this Christian Community.'], 422);
            }

            return back()->with('error', 'Invalid member selection for this Christian Community.');
        }

        try {
            DB::transaction(function () use ($request, $meeting, $member, $validated): void {
                $row = JumuiyaWeeklyAttendance::query()
                    ->where('jumuiya_weekly_meeting_id', $meeting->id)
                    ->where('member_id', $member->id)
                    ->lockForUpdate()
                    ->first();

                $oldStatus = $row?->status;

                if (! $row) {
                    $row = JumuiyaWeeklyAttendance::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'jumuiya_weekly_meeting_id' => $meeting->id,
                        'member_id' => $member->id,
                        'status' => $validated['status'],
                        'marked_by_user_id' => (int) $request->user()->id,
                        'marked_at' => now(),
                    ]);

                    $action = 'created';
                } else {
                    $row->update([
                        'status' => $validated['status'],
                        'marked_by_user_id' => (int) $request->user()->id,
                        'marked_at' => now(),
                    ]);

                    $action = 'updated';
                }

                JumuiyaWeeklyAttendanceAudit::query()->create([
                    'uuid' => (string) Str::uuid(),
                    'jumuiya_weekly_meeting_id' => $meeting->id,
                    'member_id' => $member->id,
                    'jumuiya_weekly_attendance_id' => $row->id,
                    'action' => $action,
                    'old_status' => $oldStatus,
                    'new_status' => $validated['status'],
                    'performed_by_user_id' => (int) $request->user()->id,
                    'performed_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'notes' => $validated['notes'] ?? null,
                ]);
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'member_uuid' => $member->uuid,
                    'status' => $validated['status'],
                ]);
            }

            return back()->with('success', 'Attendance saved.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Unable to save attendance. Please try again.'], 500);
            }

            return back()->with('error', 'Unable to save attendance. Please try again.');
        }
    }

    public function familyReport(Request $request, JumuiyaWeeklyMeeting $meeting): JsonResponse
    {
        $this->authorize('view', $meeting);

        $meetingDate = Carbon::parse($meeting->meeting_date)->toDateString();
        $meetingDateCarbon = Carbon::parse($meeting->meeting_date);

        $perPage = (int) $request->query('per_page', 20);
        if ($perPage < 1) {
            $perPage = 10;
        }
        if ($perPage > 50) {
            $perPage = 50;
        }

        $familiesPage = Family::query()
            ->where('jumuiya_id', $meeting->jumuiya_id)
            ->with([
                'members:id,uuid,family_id,jumuiya_id,first_name,middle_name,last_name,is_active',
            ])
            ->orderBy('family_name')
            ->paginate($perPage, ['id', 'uuid', 'family_name', 'jumuiya_id']);

        $attendanceRows = JumuiyaWeeklyAttendance::query()
            ->where('jumuiya_weekly_meeting_id', $meeting->id)
            ->get(['member_id', 'status'])
            ->keyBy('member_id');

        $data = $familiesPage->getCollection()->map(function (Family $family) use ($attendanceRows, $meetingDate, $meetingDateCarbon, $meeting) {
            $members = $family->members
                ->map(function (Member $m) use ($attendanceRows, $meetingDate, $meetingDateCarbon, $meeting) {
                    $status = $attendanceRows->get($m->id)?->status;

                    $eligible = $m->effectiveJumuiyaIdAsOf($meetingDateCarbon) === (int) $meeting->jumuiya_id;

                    return [
                        'uuid' => $m->uuid,
                        'first_name' => $m->first_name,
                        'middle_name' => $m->middle_name,
                        'last_name' => $m->last_name,
                        'is_active' => (bool) $m->is_active,
                        'eligible' => (bool) $eligible,
                        'status' => $status,
                    ];
                })
                ->values();

            return [
                'uuid' => $family->uuid,
                'family_name' => $family->family_name,
                'meeting_date' => $meetingDate,
                'members' => $members,
            ];
        })->values();

        return response()->json([
            'meeting_uuid' => $meeting->uuid,
            'meeting_date' => $meetingDate,
            'jumuiya_id' => (int) $meeting->jumuiya_id,
            'families' => $data,
            'pagination' => [
                'current_page' => (int) $familiesPage->currentPage(),
                'last_page' => (int) $familiesPage->lastPage(),
                'per_page' => (int) $familiesPage->perPage(),
                'total' => (int) $familiesPage->total(),
                'from' => $familiesPage->firstItem() ? (int) $familiesPage->firstItem() : null,
                'to' => $familiesPage->lastItem() ? (int) $familiesPage->lastItem() : null,
            ],
        ]);
    }

    public function bulkMark(BulkMarkWeeklyAttendanceRequest $request, JumuiyaWeeklyMeeting $meeting): JsonResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validated();

        if (! Carbon::parse($meeting->meeting_date)->isSaturday() && ! $request->user()?->can('weekly-attendance.override-lock')) {
            return response()->json(['message' => 'Invalid weekly meeting date.'], 422);
        }

        try {
            $this->assertNotLocked($request, $meeting);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 423);
        }

        $memberUuids = array_values(array_unique($validated['member_uuids'] ?? []));
        if (count($memberUuids) === 0) {
            return response()->json(['message' => 'No members selected.'], 422);
        }

        $status = (string) $validated['status'];
        $notes = $validated['notes'] ?? null;

        $members = Member::query()
            ->whereIn('uuid', $memberUuids)
            ->get(['id', 'uuid', 'jumuiya_id']);

        if ($members->count() !== count($memberUuids)) {
            return response()->json(['message' => 'One or more selected members are invalid.'], 422);
        }

        $invalidScope = [];
        foreach ($members as $m) {
            $effectiveJumuiyaId = $m->effectiveJumuiyaIdAsOf(Carbon::parse($meeting->meeting_date));
            if ($effectiveJumuiyaId !== (int) $meeting->jumuiya_id) {
                $invalidScope[] = $m->uuid;
            }
        }

        if (count($invalidScope) > 0) {
            Log::warning('Weekly attendance bulk mark blocked: some members not in meeting jumuiya as-of date', [
                'meeting_uuid' => $meeting->uuid,
                'meeting_id' => $meeting->id,
                'meeting_date' => (string) $meeting->meeting_date,
                'meeting_jumuiya_id' => (int) $meeting->jumuiya_id,
                'invalid_member_uuids' => $invalidScope,
                'user_id' => (int) $request->user()->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Invalid member selection for this Christian Community.',
                'invalid_member_uuids' => $invalidScope,
            ], 422);
        }

        try {
            $t0 = microtime(true);
            Log::info('Weekly attendance bulk mark start', [
                'meeting_uuid' => $meeting->uuid,
                'meeting_id' => (int) $meeting->id,
                'meeting_date' => (string) $meeting->meeting_date,
                'meeting_jumuiya_id' => (int) $meeting->jumuiya_id,
                'member_count' => (int) $members->count(),
                'status' => $status,
                'user_id' => (int) $request->user()->id,
                'ip' => $request->ip(),
            ]);

            $updated = [];

            DB::transaction(function () use ($request, $meeting, $members, $status, $notes, &$updated): void {
                foreach ($members as $member) {
                    $row = JumuiyaWeeklyAttendance::query()
                        ->where('jumuiya_weekly_meeting_id', $meeting->id)
                        ->where('member_id', $member->id)
                        ->lockForUpdate()
                        ->first();

                    $oldStatus = $row?->status;

                    if (! $row) {
                        $row = JumuiyaWeeklyAttendance::query()->create([
                            'uuid' => (string) Str::uuid(),
                            'jumuiya_weekly_meeting_id' => $meeting->id,
                            'member_id' => $member->id,
                            'status' => $status,
                            'marked_by_user_id' => (int) $request->user()->id,
                            'marked_at' => now(),
                        ]);

                        $action = 'created';
                    } else {
                        $row->update([
                            'status' => $status,
                            'marked_by_user_id' => (int) $request->user()->id,
                            'marked_at' => now(),
                        ]);

                        $action = 'updated';
                    }

                    JumuiyaWeeklyAttendanceAudit::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'jumuiya_weekly_meeting_id' => $meeting->id,
                        'member_id' => $member->id,
                        'jumuiya_weekly_attendance_id' => $row->id,
                        'action' => $action,
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                        'performed_by_user_id' => (int) $request->user()->id,
                        'performed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'notes' => $notes,
                    ]);

                    $updated[] = [
                        'member_uuid' => $member->uuid,
                        'status' => $status,
                    ];
                }
            });

            Log::info('Weekly attendance bulk mark completed', [
                'meeting_uuid' => $meeting->uuid,
                'meeting_id' => (int) $meeting->id,
                'updated_count' => (int) count($updated),
                'duration_ms' => (int) round((microtime(true) - $t0) * 1000),
                'user_id' => (int) $request->user()->id,
            ]);

            return response()->json([
                'status' => $status,
                'updated' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('Weekly attendance bulk mark failed', [
                'meeting_uuid' => $meeting->uuid,
                'meeting_id' => (int) $meeting->id,
                'meeting_date' => (string) $meeting->meeting_date,
                'meeting_jumuiya_id' => (int) $meeting->jumuiya_id,
                'member_count' => (int) ($members->count() ?? 0),
                'status' => $status,
                'user_id' => (int) $request->user()->id,
                'ip' => $request->ip(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to save attendance. Please try again.'], 500);
        }
    }

    public function close(Request $request, JumuiyaWeeklyMeeting $meeting): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $meeting);

        if (! Carbon::parse($meeting->meeting_date)->isSaturday() && ! $request->user()?->can('weekly-attendance.override-lock')) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid weekly meeting date.'], 422);
            }

            return back()->with('error', 'Invalid weekly meeting date.');
        }

        try {
            $this->assertNotLocked($request, $meeting);
        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 423);
            }

            return back()->with('error', $e->getMessage());
        }

        try {
            $result = DB::transaction(function () use ($request, $meeting): array {
                $lockedMeeting = JumuiyaWeeklyMeeting::query()
                    ->where('id', $meeting->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedMeeting->closed_at) {
                    return [
                        'already_closed' => true,
                        'marked_absent_count' => 0,
                        'closed_at' => $lockedMeeting->closed_at,
                        'locked_at' => $lockedMeeting->locked_at,
                    ];
                }

                $meetingDateCarbon = Carbon::parse($lockedMeeting->meeting_date);

                $familyIds = Family::query()
                    ->where('jumuiya_id', $lockedMeeting->jumuiya_id)
                    ->pluck('id')
                    ->all();

                if (count($familyIds) === 0) {
                    $lockedMeeting->forceFill([
                        'closed_at' => now(),
                        'locked_at' => now(),
                    ])->save();

                    return [
                        'already_closed' => false,
                        'marked_absent_count' => 0,
                        'closed_at' => $lockedMeeting->closed_at,
                        'locked_at' => $lockedMeeting->locked_at,
                    ];
                }

                $members = Member::query()
                    ->whereIn('family_id', $familyIds)
                    ->where('is_active', true)
                    ->get(['id', 'uuid', 'jumuiya_id']);

                $eligibleMembers = $members->filter(function (Member $m) use ($meetingDateCarbon, $lockedMeeting) {
                    return $m->effectiveJumuiyaIdAsOf($meetingDateCarbon) === (int) $lockedMeeting->jumuiya_id;
                });

                $alreadyMarkedMemberIds = JumuiyaWeeklyAttendance::query()
                    ->where('jumuiya_weekly_meeting_id', $lockedMeeting->id)
                    ->pluck('member_id')
                    ->all();

                $alreadyMarkedSet = array_fill_keys($alreadyMarkedMemberIds, true);

                $toMarkAbsent = $eligibleMembers->filter(function (Member $m) use ($alreadyMarkedSet) {
                    return ! isset($alreadyMarkedSet[$m->id]);
                });

                $count = 0;
                foreach ($toMarkAbsent as $member) {
                    $row = JumuiyaWeeklyAttendance::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'jumuiya_weekly_meeting_id' => $lockedMeeting->id,
                        'member_id' => $member->id,
                        'status' => 'absent',
                        'marked_by_user_id' => (int) $request->user()->id,
                        'marked_at' => now(),
                    ]);

                    JumuiyaWeeklyAttendanceAudit::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'jumuiya_weekly_meeting_id' => $lockedMeeting->id,
                        'member_id' => $member->id,
                        'jumuiya_weekly_attendance_id' => $row->id,
                        'action' => 'created',
                        'old_status' => null,
                        'new_status' => 'absent',
                        'performed_by_user_id' => (int) $request->user()->id,
                        'performed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'notes' => 'Auto-marked absent on close',
                    ]);

                    $count++;
                }

                $lockedMeeting->forceFill([
                    'closed_at' => now(),
                    'locked_at' => now(),
                ])->save();

                return [
                    'already_closed' => false,
                    'marked_absent_count' => $count,
                    'closed_at' => $lockedMeeting->closed_at,
                    'locked_at' => $lockedMeeting->locked_at,
                ];
            });

            $message = ! empty($result['already_closed'])
                ? 'This weekly attendance session is already closed.'
                : 'Session closed. Remaining unmarked members were marked absent.';

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $message,
                    'meeting_uuid' => $meeting->uuid,
                    'marked_absent_count' => (int) ($result['marked_absent_count'] ?? 0),
                    'closed_at' => $result['closed_at'] ?? null,
                    'locked_at' => $result['locked_at'] ?? null,
                ]);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Close weekly attendance session failed', [
                'exception' => $e,
                'meeting_uuid' => $meeting->uuid,
                'user_id' => (int) $request->user()?->id,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Unable to close session. Please try again.'], 500);
            }

            return back()->with('error', 'Unable to close session. Please try again.');
        }
    }
}
