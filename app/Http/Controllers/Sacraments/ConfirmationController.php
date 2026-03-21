<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sacraments\SacramentProgramRegistrationResource;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\People\Member;
use App\Models\Sacraments\SacramentProgramCycle;
use App\Models\Sacraments\SacramentProgramRegistration;
use App\Models\Structure\Jumuiya;
use App\Services\Sacraments\SacramentWorkflowEventService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmationController extends Controller
{
    private const TYPE_TRANSFER_APPROVAL_LETTER = 'transfer_approval_letter';

    protected SacramentWorkflowEventService $workflowEvents;

    public function __construct(SacramentWorkflowEventService $workflowEvents)
    {
        $this->workflowEvents = $workflowEvents;
    }

    private function activeLeadershipJumuiyaIds(int $memberId): array
    {
        if (! $memberId) {
            return [];
        }

        $today = now()->toDateString();

        return JumuiyaLeadership::query()
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->pluck('jumuiya_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function ensureActiveJumuiyaLeaderForMember(?int $userId, int $userMemberId, int $targetJumuiyaId): void
    {
        if (! $userId || ! $targetJumuiyaId) {
            abort(403, 'Invalid user context.');
        }

        if (! $userMemberId) {
            abort(403, 'Your account is not linked to a member record. Please contact the parish admin.');
        }

        $today = now()->toDateString();

        $jumuiyaName = Jumuiya::query()->where('id', $targetJumuiyaId)->value('name');
        $jumuiyaLabel = $jumuiyaName ? ($jumuiyaName.' (ID '.$targetJumuiyaId.')') : ('Jumuiya ID '.$targetJumuiyaId);

        $leadership = JumuiyaLeadership::query()
            ->where('member_id', $userMemberId)
            ->where('jumuiya_id', $targetJumuiyaId)
            ->orderByDesc('start_date')
            ->first();

        if (! $leadership) {
            abort(403, 'You are not assigned as Jumuiya leadership for '.$jumuiyaLabel.'.');
        }

        if (! (bool) $leadership->is_active) {
            abort(403, 'Your Jumuiya leadership assignment is inactive for '.$jumuiyaLabel.'.');
        }

        if ($leadership->start_date && $leadership->start_date->toDateString() > $today) {
            abort(403, 'Your Jumuiya leadership assignment starts on '.$leadership->start_date->toDateString().' for '.$jumuiyaLabel.'.');
        }

        if ($leadership->end_date && $leadership->end_date->toDateString() < $today) {
            abort(403, 'Your Jumuiya leadership assignment ended on '.$leadership->end_date->toDateString().' for '.$jumuiyaLabel.'.');
        }
    }

    private function completedCommunionForMember(int $memberId): ?SacramentProgramRegistration
    {
        if (! $memberId) {
            return null;
        }

        return SacramentProgramRegistration::query()
            ->where('program', SacramentProgramCycle::PROGRAM_FIRST_COMMUNION)
            ->where('member_id', $memberId)
            ->whereIn('status', [SacramentProgramRegistration::STATUS_COMPLETED, SacramentProgramRegistration::STATUS_ISSUED])
            ->orderByDesc('id')
            ->first();
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);
        $memberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);

        $leaderJumuiyaIds = $this->activeLeadershipJumuiyaIds($memberId);

        $canGlobalView = (bool) ($user?->can('users.manage')
            || $user?->can('permissions.manage')
            || $user?->can('sacraments.cross_parish.search'));

        $canParishView = (bool) ($user?->can('confirmations.parish.view'));
        $canLeaderView = (bool) ($user?->can('confirmations.view'));

        $hasAnyScope = $canGlobalView
            || ($canParishView && $parishId > 0)
            || ($canLeaderView && ! empty($leaderJumuiyaIds));

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $cycleUuid = $request->query('cycle_uuid');
        $cycleUuid = is_string($cycleUuid) ? trim($cycleUuid) : '';

        $from = $request->query('from');
        $from = is_string($from) ? trim($from) : '';
        $to = $request->query('to');
        $to = is_string($to) ? trim($to) : '';

        $fromDt = null;
        if ($from !== '') {
            try {
                $fromDt = Carbon::parse($from)->startOfDay();
            } catch (\Throwable) {
                $fromDt = null;
            }
        }

        $cycleOptionsQuery = SacramentProgramCycle::query()
            ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
            ->when($parishId > 0, fn ($q) => $q->where('parish_id', $parishId))
            ->whereIn('status', [SacramentProgramCycle::STATUS_OPEN, SacramentProgramCycle::STATUS_CLOSED])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('id');

        $defaultCycle = (clone $cycleOptionsQuery)->first(['id', 'uuid']);
        $defaultCycleUuid = $defaultCycle?->uuid ? (string) $defaultCycle->uuid : '';

        if ($cycleUuid === '' && $defaultCycleUuid !== '') {
            $cycleUuid = $defaultCycleUuid;
        }

        $selectedCycleId = 0;
        if ($cycleUuid !== '') {
            $selectedCycleId = (int) SacramentProgramCycle::query()
                ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
                ->when($parishId > 0, fn ($q) => $q->where('parish_id', $parishId))
                ->where('uuid', $cycleUuid)
                ->value('id');
        }

        $toDt = null;
        if ($to !== '') {
            try {
                $toDt = Carbon::parse($to)->endOfDay();
            } catch (\Throwable) {
                $toDt = null;
            }
        }

        $idQuery = SacramentProgramRegistration::query()
            ->select('sacrament_program_registrations.id')
            ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
            ->when($q !== '', function ($qb) {
                $qb->join('members', 'members.id', '=', 'sacrament_program_registrations.member_id');
            });

        $idQuery
            ->when(! $hasAnyScope, fn ($qb) => $qb->whereRaw('1=0'))
            ->when(! $canGlobalView, function ($qb) use ($parishId, $leaderJumuiyaIds, $canParishView, $canLeaderView) {
                $qb->where(function ($qq) use ($parishId, $leaderJumuiyaIds, $canParishView, $canLeaderView) {
                    if ($parishId && $canParishView) {
                        $qq->where('sacrament_program_registrations.parish_id', $parishId);
                    }

                    if ($canLeaderView && ! empty($leaderJumuiyaIds)) {
                        $qq->orWhereIn('sacrament_program_registrations.origin_jumuiya_id', $leaderJumuiyaIds);
                    }
                });
            })
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes(mb_strtolower($q, 'UTF-8'), '%_\\');
                $safeLike = $safe.'%';

                $qb->where(function ($w) use ($safeLike) {
                    $w->where('members.full_name_key', 'like', $safeLike)
                        ->orWhere('members.first_name_key', 'like', $safeLike)
                        ->orWhere('members.middle_name_key', 'like', $safeLike)
                        ->orWhere('members.last_name_key', 'like', $safeLike)
                        ->orWhere('members.phone', 'like', $safeLike);
                });
            })
            ->when($selectedCycleId > 0, fn ($qb) => $qb->where('sacrament_program_registrations.program_cycle_id', $selectedCycleId))
            ->when($fromDt, fn ($qb) => $qb->where('sacrament_program_registrations.created_at', '>=', $fromDt))
            ->when($toDt, fn ($qb) => $qb->where('sacrament_program_registrations.created_at', '<=', $toDt));

        $idRows = $idQuery
            ->orderByDesc('sacrament_program_registrations.id')
            ->paginate(20)
            ->withQueryString();

        $ids = $idRows->getCollection()->map(fn ($r) => (int) $r->id)->filter()->values()->all();

        $models = empty($ids)
            ? collect()
            : SacramentProgramRegistration::query()
                ->whereIn('id', $ids)
                ->with([
                    'cycle:id,uuid,program,parish_id,name,status,registration_opens_at,registration_closes_at,late_registration_closes_at,created_at',
                    'member:id,uuid,first_name,middle_name,last_name,jumuiya_id',
                    'originJumuiya:id,uuid,name',
                    'family:id,uuid,family_name',
                    'attachments',
                ])
                ->orderByRaw('FIELD(id,'.implode(',', $ids).')')
                ->get();

        $idRows->setCollection($models);

        return Inertia::render('Sacraments/Confirmations/Index', [
            'filters' => [
                'q' => $q,
                'cycle_uuid' => $cycleUuid,
                'from' => $from,
                'to' => $to,
            ],
            'registrations' => SacramentProgramRegistrationResource::collection($idRows),
            'cycles' => $cycleOptionsQuery
                ->limit(50)
                ->get(['id', 'uuid', 'name', 'status', 'registration_opens_at', 'registration_closes_at', 'late_registration_closes_at']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        $cyclesQuery = SacramentProgramCycle::query()
            ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
            ->when($parishId > 0, fn ($q) => $q->where('parish_id', $parishId))
            ->whereIn('status', [SacramentProgramCycle::STATUS_OPEN, SacramentProgramCycle::STATUS_CLOSED])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('id');

        $defaultCycleUuid = (string) ((clone $cyclesQuery)->value('uuid') ?? '');

        $cycles = $cyclesQuery
            ->limit(50)
            ->get(['id', 'uuid', 'name', 'status', 'registration_opens_at', 'registration_closes_at', 'late_registration_closes_at']);

        return Inertia::render('Sacraments/Confirmations/Create', [
            'cycles' => $cycles,
            'default_cycle_uuid' => $defaultCycleUuid,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cycle_uuid' => ['required', 'string', 'max:190'],
            'family_uuid' => ['required', 'string', 'max:190'],
            'member_uuid' => ['required', 'string', 'max:190'],
        ]);

        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);
        $userMemberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);

        if (! $user || ! $user->can('confirmations.register')) {
            abort(403);
        }

        $cycle = SacramentProgramCycle::query()
            ->where('uuid', $validated['cycle_uuid'])
            ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
            ->firstOrFail();

        $canGlobalOverride = (bool) ($user?->can('users.manage')
            || $user?->can('permissions.manage')
            || $user?->can('sacraments.cross_parish.search')
            || $user?->can('sacraments.cycle.override'));

        if (! $canGlobalOverride && ! $cycle->allowsRegistrationActions()) {
            return back()->with('error', 'Registration window is closed.')->withInput();
        }

        if ($parishId && (int) $cycle->parish_id !== $parishId) {
            abort(403);
        }

        $member = Member::query()->where('uuid', $validated['member_uuid'])->firstOrFail();
        $family = DB::table('families')->where('uuid', $validated['family_uuid'])->first(['id', 'jumuiya_id']);
        $familyId = (int) ($family?->id ?? 0);
        $familyJumuiyaId = (int) ($family?->jumuiya_id ?? 0);

        if (! $familyId || ! $familyJumuiyaId) {
            return back()->with('error', 'Invalid family.')->withInput();
        }

        if ((int) $member->family_id !== $familyId) {
            return back()->with('error', 'Candidate must belong to the selected family.')->withInput();
        }

        if ((int) $member->jumuiya_id !== $familyJumuiyaId) {
            return back()->with('error', 'Candidate does not belong to the same Christian Community as the family.')->withInput();
        }

        $this->ensureActiveJumuiyaLeaderForMember((int) $user->id, $userMemberId, $familyJumuiyaId);

        $completedCommunion = $this->completedCommunionForMember((int) $member->id);
        if (! $completedCommunion) {
            return back()->with('error', 'Candidate must have completed First Communion before registering for Confirmation.')->withInput();
        }

        try {
            $reg = SacramentProgramRegistration::query()->create([
                'uuid' => null,
                'program_cycle_id' => (int) $cycle->id,
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
                'parish_id' => (int) $cycle->parish_id,
                'origin_jumuiya_id' => $familyJumuiyaId,
                'family_id' => $familyId,
                'member_id' => (int) $member->id,
                'is_transfer' => false,
                'status' => SacramentProgramRegistration::STATUS_DRAFT,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $existing = SacramentProgramRegistration::query()
                ->where('program_cycle_id', (int) $cycle->id)
                ->where('member_id', (int) $member->id)
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return redirect()->route('confirmations.show', $existing)->with('info', 'A registration already exists for this candidate in the selected cycle.');
            }

            throw $e;
        }

        return redirect()->route('confirmations.show', $reg)->with('success', 'Confirmation registration created.');
    }

    public function show(Request $request, SacramentProgramRegistration $registration): Response
    {
        if (($registration->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);
        $memberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);

        $leaderJumuiyaIds = $this->activeLeadershipJumuiyaIds($memberId);

        $canGlobalView = (bool) ($user?->can('users.manage')
            || $user?->can('permissions.manage')
            || $user?->can('sacraments.cross_parish.search'));

        $canParishView = (bool) ($user?->can('confirmations.parish.view'));
        $canLeaderView = (bool) ($user?->can('confirmations.view'));

        $allowed = $canGlobalView
            || ($canParishView && $parishId > 0 && (int) $registration->parish_id === $parishId)
            || ($canLeaderView && in_array((int) $registration->origin_jumuiya_id, $leaderJumuiyaIds, true));

        if (! $allowed) {
            abort(403);
        }

        $registration->load([
            'cycle:id,uuid,program,parish_id,name,status,registration_opens_at,registration_closes_at,late_registration_closes_at,created_at',
            'member:id,uuid,first_name,middle_name,last_name,jumuiya_id',
            'originJumuiya:id,uuid,name',
            'family:id,uuid,family_name',
            'attachments',
        ]);

        $cycles = SacramentProgramCycle::query()
            ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
            ->when($parishId > 0, fn ($q) => $q->where('parish_id', $parishId))
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'uuid', 'name', 'status', 'registration_opens_at', 'registration_closes_at', 'late_registration_closes_at']);

        return Inertia::render('Sacraments/Confirmations/Show', [
            'registration' => (new SacramentProgramRegistrationResource($registration))->toArray($request),
            'cycles' => $cycles,
        ]);
    }

    public function saveDraft(Request $request, SacramentProgramRegistration $registration): RedirectResponse
    {
        if (($registration->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $user = $request->user();
        if (! $user || ! $user->can('confirmations.register')) {
            abort(403);
        }

        if (! in_array($registration->status, [SacramentProgramRegistration::STATUS_DRAFT, SacramentProgramRegistration::STATUS_REJECTED], true)) {
            return back()->with('error', 'This registration can no longer be edited.');
        }

        $validated = $request->validate([
            'cycle_uuid' => ['required', 'string', 'max:190'],
            'family_uuid' => ['required', 'string', 'max:190'],
            'member_uuid' => ['required', 'string', 'max:190'],
        ]);

        $parishId = (int) ($user?->parish_id ?? 0);
        $userMemberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);

        $cycle = SacramentProgramCycle::query()
            ->where('uuid', $validated['cycle_uuid'])
            ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
            ->firstOrFail();

        $canGlobalOverride = (bool) ($user?->can('users.manage')
            || $user?->can('permissions.manage')
            || $user?->can('sacraments.cross_parish.search')
            || $user?->can('sacraments.cycle.override'));

        if (! $canGlobalOverride && ! $cycle->allowsRegistrationActions()) {
            return back()->with('error', 'Registration window is closed.')->withInput();
        }

        if ($parishId && (int) $cycle->parish_id !== $parishId) {
            abort(403);
        }

        $member = Member::query()->where('uuid', $validated['member_uuid'])->firstOrFail();
        $family = DB::table('families')->where('uuid', $validated['family_uuid'])->first(['id', 'jumuiya_id']);
        $familyId = (int) ($family?->id ?? 0);
        $familyJumuiyaId = (int) ($family?->jumuiya_id ?? 0);

        if (! $familyId || ! $familyJumuiyaId) {
            return back()->with('error', 'Invalid family.')->withInput();
        }

        if ((int) $member->family_id !== $familyId) {
            return back()->with('error', 'Candidate must belong to the selected family.')->withInput();
        }

        if ((int) $member->jumuiya_id !== $familyJumuiyaId) {
            return back()->with('error', 'Candidate does not belong to the same Christian Community as the family.')->withInput();
        }

        $this->ensureActiveJumuiyaLeaderForMember((int) $user->id, $userMemberId, $familyJumuiyaId);

        $completedCommunion = $this->completedCommunionForMember((int) $member->id);
        if (! $completedCommunion) {
            return back()->with('error', 'Candidate must have completed First Communion before registering for Confirmation.')->withInput();
        }

        $hasAnyAttachment = DB::table('sacrament_attachments')
            ->where('entity_type', 'program_registration')
            ->where('entity_id', (int) $registration->id)
            ->exists();

        $nextCycleId = (int) $cycle->id;
        $nextFamilyId = (int) $familyId;
        $nextMemberId = (int) $member->id;

        if ($hasAnyAttachment
            && ($nextCycleId !== (int) $registration->program_cycle_id
                || $nextFamilyId !== (int) $registration->family_id
                || $nextMemberId !== (int) $registration->member_id)) {
            return back()->with('error', 'Cannot change cycle, family, or candidate after attachments have been uploaded.');
        }

        $fromStatus = (string) ($registration->status ?? '');

        $registration->forceFill([
            'program_cycle_id' => $nextCycleId,
            'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            'parish_id' => (int) $cycle->parish_id,
            'origin_jumuiya_id' => $familyJumuiyaId,
            'family_id' => $nextFamilyId,
            'member_id' => $nextMemberId,
            'is_transfer' => false,
        ])->save();

        $this->workflowEvents->record(
            $request,
            (int) $registration->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
            (int) $registration->id,
            'draft_update',
            $fromStatus,
            (string) ($registration->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            ]
        );

        return back()->with('success', 'Draft updated.');
    }

    public function submit(Request $request, SacramentProgramRegistration $registration): RedirectResponse
    {
        if (($registration->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $user = $request->user();
        if (! $user || ! $user->can('confirmations.register')) {
            abort(403);
        }

        $userMemberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);
        $this->ensureActiveJumuiyaLeaderForMember((int) $user->id, $userMemberId, (int) $registration->origin_jumuiya_id);

        if (! in_array($registration->status, [SacramentProgramRegistration::STATUS_DRAFT, SacramentProgramRegistration::STATUS_REJECTED], true)) {
            return back()->with('error', 'This registration can no longer be submitted.');
        }

        $cycle = SacramentProgramCycle::query()->where('id', (int) $registration->program_cycle_id)->first();
        if (! $cycle) {
            return back()->with('error', 'Invalid cycle.');
        }

        $completedCommunion = $this->completedCommunionForMember((int) $registration->member_id);
        if (! $completedCommunion) {
            return back()->with('error', 'Candidate must have completed First Communion before submitting for Confirmation.');
        }

        if ((int) $completedCommunion->parish_id !== (int) $registration->parish_id) {
            $hasLetter = DB::table('sacrament_attachments')
                ->where('entity_type', 'program_registration')
                ->where('entity_id', (int) $registration->id)
                ->where('type', self::TYPE_TRANSFER_APPROVAL_LETTER)
                ->exists();

            if (! $hasLetter) {
                return back()->with('error', 'Transfer approval letter is required before submitting because First Communion was completed in another parish.');
            }
        }

        $fromStatus = (string) ($registration->status ?? '');

        $registration->forceFill([
            'status' => SacramentProgramRegistration::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by_user_id' => (int) $user->id,
            'submitted_by_member_id' => $userMemberId ?: null,
            'rejected_at' => null,
            'rejected_by_user_id' => null,
            'rejection_reason' => null,
        ])->save();

        $this->workflowEvents->record(
            $request,
            (int) $registration->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
            (int) $registration->id,
            'submit',
            $fromStatus,
            (string) ($registration->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            ]
        );

        return back()->with('success', 'Registration submitted.');
    }

    public function approve(Request $request, SacramentProgramRegistration $registration): RedirectResponse
    {
        if (($registration->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $user = $request->user();
        if (! $user || ! $user->can('confirmations.approve')) {
            abort(403);
        }

        if ($registration->status !== SacramentProgramRegistration::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted registrations can be approved.');
        }

        $fromStatus = (string) ($registration->status ?? '');

        $registration->forceFill([
            'status' => SacramentProgramRegistration::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => (int) $user->id,
        ])->save();

        $this->workflowEvents->record(
            $request,
            (int) $registration->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
            (int) $registration->id,
            'approve',
            $fromStatus,
            (string) ($registration->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            ]
        );

        return back()->with('success', 'Registration approved.');
    }

    public function reject(Request $request, SacramentProgramRegistration $registration): RedirectResponse
    {
        if (($registration->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $user = $request->user();
        if (! $user || ! $user->can('confirmations.reject')) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        if ($registration->status !== SacramentProgramRegistration::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted registrations can be rejected.');
        }

        $fromStatus = (string) ($registration->status ?? '');

        $registration->forceFill([
            'status' => SacramentProgramRegistration::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by_user_id' => (int) $user->id,
            'rejection_reason' => trim((string) $validated['reason']),
        ])->save();

        $this->workflowEvents->record(
            $request,
            (int) $registration->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
            (int) $registration->id,
            'reject',
            $fromStatus,
            (string) ($registration->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
                'reason' => trim((string) $validated['reason']),
            ]
        );

        return back()->with('success', 'Registration rejected.');
    }

    public function complete(Request $request, SacramentProgramRegistration $registration): RedirectResponse
    {
        if (($registration->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $user = $request->user();
        if (! $user || ! $user->can('confirmations.complete')) {
            abort(403);
        }

        if ($registration->status !== SacramentProgramRegistration::STATUS_APPROVED) {
            return back()->with('error', 'Only approved registrations can be completed.');
        }

        $fromStatus = (string) ($registration->status ?? '');

        $registration->forceFill([
            'status' => SacramentProgramRegistration::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $this->workflowEvents->record(
            $request,
            (int) $registration->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
            (int) $registration->id,
            'complete',
            $fromStatus,
            (string) ($registration->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            ]
        );

        return back()->with('success', 'Marked as completed.');
    }

    public function issue(Request $request, SacramentProgramRegistration $registration): RedirectResponse
    {
        if (($registration->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $user = $request->user();
        if (! $user || ! $user->can('confirmations.issue')) {
            abort(403);
        }

        if ($registration->status !== SacramentProgramRegistration::STATUS_COMPLETED) {
            return back()->with('error', 'Only completed registrations can be issued.');
        }

        $fromStatus = (string) ($registration->status ?? '');

        $registration->forceFill([
            'status' => SacramentProgramRegistration::STATUS_ISSUED,
            'issued_at' => now(),
            'issued_by_user_id' => (int) $user->id,
        ])->save();

        $this->workflowEvents->record(
            $request,
            (int) $registration->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
            (int) $registration->id,
            'issue',
            $fromStatus,
            (string) ($registration->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            ]
        );

        return back()->with('success', 'Certificate marked as issued.');
    }
}
