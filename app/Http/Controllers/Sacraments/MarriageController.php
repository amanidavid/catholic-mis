<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sacraments\MarriageResource;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\People\Member;
use App\Models\Sacraments\Marriage;
use App\Models\Sacraments\MarriageParent;
use App\Models\Sacraments\MarriageSponsor;
use App\Models\Sacraments\SacramentSchedule;
use App\Models\Sacraments\SacramentScheduleChange;
use App\Models\Structure\Jumuiya;
use App\Support\PhoneNormalizer;
use App\Traits\NormalizesNames;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MarriageController extends Controller
{
    private const NAME_REGEX = "/^[\pL\pM]+(?:\s+[\pL\pM]+)*$/u";
    private const SAFE_TEXT_REGEX = "/^[^<>]*$/";

    private static ?bool $marriagesHasSearchKey = null;
    private static ?bool $marriagesHasExternalBrideNameKey = null;
    private static ?bool $membersHaveNameKeyColumns = null;

    private function marriagesHasSearchKey(): bool
    {
        if (self::$marriagesHasSearchKey !== null) {
            return self::$marriagesHasSearchKey;
        }

        return self::$marriagesHasSearchKey = Schema::hasColumn('marriages', 'search_key');
    }

    private function marriagesHasExternalBrideNameKey(): bool
    {
        if (self::$marriagesHasExternalBrideNameKey !== null) {
            return self::$marriagesHasExternalBrideNameKey;
        }

        return self::$marriagesHasExternalBrideNameKey = Schema::hasColumn('marriages', 'bride_external_full_name_key');
    }

    private function membersHaveNameKeyColumns(): bool
    {
        if (self::$membersHaveNameKeyColumns !== null) {
            return self::$membersHaveNameKeyColumns;
        }

        $has = Schema::hasColumn('members', 'first_name_key')
            && Schema::hasColumn('members', 'middle_name_key')
            && Schema::hasColumn('members', 'last_name_key')
            && Schema::hasColumn('members', 'full_name_key');

        return self::$membersHaveNameKeyColumns = $has;
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

    private function canViewMarriage(Request $request, Marriage $marriage): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $canGlobalView = $user->can('users.manage')
            || $user->can('permissions.manage')
            || $user->can('marriages.cross_parish.search');

        if ($canGlobalView) {
            return true;
        }

        $parishId = (int) ($user->parish_id ?? 0);
        if ($parishId && (int) $marriage->parish_id === $parishId && $user->can('marriages.view')) {
            return true;
        }

        $memberId = (int) ($user->member_id ?? $user->member?->id ?? 0);
        $leaderJumuiyaIds = $this->activeLeadershipJumuiyaIds($memberId);

        return (int) $marriage->origin_jumuiya_id > 0
            && in_array((int) $marriage->origin_jumuiya_id, $leaderJumuiyaIds, true);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);
        $memberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);

        $leaderJumuiyaIds = $this->activeLeadershipJumuiyaIds($memberId);

        $canGlobalView = (bool) ($user?->can('users.manage')
            || $user?->can('permissions.manage')
            || $user?->can('marriages.cross_parish.search'));

        $canParishView = (bool) ($user?->can('marriages.view'));

        $hasAnyScope = $canGlobalView || ($canParishView && $parishId > 0) || ! empty($leaderJumuiyaIds);

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';
        $qKey = $q !== '' ? mb_strtolower($q, 'UTF-8') : '';
        $qKey = preg_replace('/\s+/', ' ', $qKey ?? '') ?? '';

        $from = $request->query('from');
        $from = is_string($from) ? trim($from) : '';
        $to = $request->query('to');
        $to = is_string($to) ? trim($to) : '';

        $hasSearchKey = $this->marriagesHasSearchKey();
        $hasExternalBrideNameKey = $this->marriagesHasExternalBrideNameKey();
        $membersHaveNameKeys = $this->membersHaveNameKeyColumns();

        $fromDt = null;
        if ($from !== '') {
            try {
                $fromDt = Carbon::parse($from)->startOfDay();
            } catch (\Throwable) {
                $fromDt = null;
            }
        }

        $toDt = null;
        if ($to !== '') {
            try {
                $toDt = Carbon::parse($to)->endOfDay();
            } catch (\Throwable) {
                $toDt = null;
            }
        }

        $idRows = Marriage::query()
            ->select('id')
            ->when(! $hasAnyScope, fn ($qb) => $qb->whereRaw('1=0'))
            ->when(! $canGlobalView, function ($qb) use ($parishId, $leaderJumuiyaIds, $canParishView) {
                $qb->where(function ($qq) use ($parishId, $leaderJumuiyaIds, $canParishView) {
                    if ($parishId && $canParishView) {
                        $qq->where('parish_id', $parishId);
                    }

                    if (! empty($leaderJumuiyaIds)) {
                        $qq->orWhereIn('origin_jumuiya_id', $leaderJumuiyaIds);
                    }
                });
            })
            ->when($qKey !== '', function ($qb) use ($qKey, $q, $hasSearchKey, $hasExternalBrideNameKey, $membersHaveNameKeys) {
                $safe = addcslashes($qKey, '%_\\');
                $safeLike = $safe.'%';

                $qb->where(function ($sq) use ($safeLike, $qKey, $hasSearchKey, $hasExternalBrideNameKey, $membersHaveNameKeys) {
                    $sq->where(function ($qq) use ($safeLike, $qKey, $hasSearchKey) {
                        if ($hasSearchKey) {
                            $qq->where('search_key', 'like', $safeLike);
                        } else {
                            $qq->where('couple_key', 'like', $safeLike);
                        }

                        $qq->orWhere('certificate_no_key', 'like', $safeLike);
                    })
                        ->when(! $membersHaveNameKeys, fn ($qq) => $qq->orWhereHas('groom', function ($mq) use ($safeLike) {
                            $mq->where('first_name', 'like', $safeLike)
                                ->orWhere('middle_name', 'like', $safeLike)
                                ->orWhere('last_name', 'like', $safeLike);
                        }))
                        ->when($membersHaveNameKeys, fn ($qq) => $qq->orWhereHas('groom', function ($mq) use ($safeLike) {
                            $mq->where('first_name_key', 'like', $safeLike)
                                ->orWhere('middle_name_key', 'like', $safeLike)
                                ->orWhere('last_name_key', 'like', $safeLike)
                                ->orWhere('full_name_key', 'like', $safeLike);
                        }))
                        ->when(! $membersHaveNameKeys, fn ($qq) => $qq->orWhereHas('bride', function ($mq) use ($safeLike) {
                            $mq->where('first_name', 'like', $safeLike)
                                ->orWhere('middle_name', 'like', $safeLike)
                                ->orWhere('last_name', 'like', $safeLike);
                        }))
                        ->when($membersHaveNameKeys, fn ($qq) => $qq->orWhereHas('bride', function ($mq) use ($safeLike) {
                            $mq->where('first_name_key', 'like', $safeLike)
                                ->orWhere('middle_name_key', 'like', $safeLike)
                                ->orWhere('last_name_key', 'like', $safeLike)
                                ->orWhere('full_name_key', 'like', $safeLike);
                        }))
                        ->when($hasExternalBrideNameKey, fn ($qq) => $qq->orWhere('bride_external_full_name_key', 'like', $safeLike))
                        ->when(! $hasExternalBrideNameKey, fn ($qq) => $qq->orWhere('bride_external_full_name', 'like', $safeLike));
                });
            })
            ->when($fromDt, fn ($qb) => $qb->where('created_at', '>=', $fromDt))
            ->when($toDt, fn ($qb) => $qb->where('created_at', '<=', $toDt))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $ids = $idRows->getCollection()->map(fn ($r) => (int) $r->id)->filter()->values()->all();

        $models = empty($ids)
            ? collect()
            : Marriage::query()
                ->whereIn('id', $ids)
                ->with([
                    'groom:id,uuid,first_name,middle_name,last_name,family_id,jumuiya_id',
                    'bride:id,uuid,first_name,middle_name,last_name,family_id,jumuiya_id',
                    'groomJumuiya:id,uuid,zone_id,name',
                    'brideJumuiya:id,uuid,zone_id,name',
                ])
                ->orderByRaw('FIELD(id,'.implode(',', $ids).')')
                ->get();

        $marriages = $models
            ->map(fn ($m) => (new MarriageResource($m))->toArray($request))
            ->values()
            ->all();

        return Inertia::render('Sacraments/Marriages/Index', [
            'filters' => [
                'q' => $q,
                'from' => $from,
                'to' => $to,
            ],
            'marriages' => $marriages,
            'pagination' => [
                'current_page' => $idRows->currentPage(),
                'last_page' => $idRows->lastPage(),
                'per_page' => $idRows->perPage(),
                'total' => $idRows->total(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Sacraments/Marriages/Create');
    }

    public function show(Request $request, Marriage $marriage): Response
    {
        if (! $this->canViewMarriage($request, $marriage)) {
            abort(404);
        }

        $marriage->load([
            'groom:id,uuid,first_name,middle_name,last_name,family_id,jumuiya_id,phone,email',
            'bride:id,uuid,first_name,middle_name,last_name,family_id,jumuiya_id,phone,email',
            'groomFamily:id,uuid,family_name,jumuiya_id',
            'brideFamily:id,uuid,family_name,jumuiya_id',
            'groomJumuiya:id,uuid,zone_id,name',
            'groomJumuiya.zone:id,uuid,parish_id,name',
            'groomJumuiya.zone.parish:id,uuid,name,code',
            'brideJumuiya:id,uuid,zone_id,name',
            'brideJumuiya.zone:id,uuid,parish_id,name',
            'brideJumuiya.zone.parish:id,uuid,name,code',
            'attachments',
            'parents.fatherMember:id,uuid,first_name,middle_name,last_name,phone,email',
            'parents.motherMember:id,uuid,first_name,middle_name,last_name,phone,email',
            'sponsors',
        ]);

        $schedule = SacramentSchedule::query()
            ->where('entity_type', 'marriage')
            ->where('entity_id', (int) $marriage->id)
            ->with(['locationParish:id,uuid,name', 'createdBy:id,name'])
            ->orderByDesc('id')
            ->first();

        $scheduleChanges = $schedule
            ? SacramentScheduleChange::query()
                ->where('sacrament_schedule_id', (int) $schedule->id)
                ->with(['changedBy:id,name'])
                ->orderByDesc('id')
                ->get()
            : collect();

        return Inertia::render('Sacraments/Marriages/Show', [
            'marriage' => new MarriageResource($marriage),
            'schedule' => $schedule
                ? [
                    'id' => (int) $schedule->id,
                    'scheduled_for' => $schedule->scheduled_for?->format('Y-m-d H:i'),
                    'status' => (string) ($schedule->status ?? ''),
                    'location_parish' => $schedule->locationParish
                        ? [
                            'uuid' => (string) $schedule->locationParish->uuid,
                            'name' => (string) $schedule->locationParish->name,
                        ]
                        : null,
                    'location_text' => $schedule->location_text,
                    'created_by' => $schedule->createdBy
                        ? [
                            'id' => (int) $schedule->createdBy->id,
                            'name' => (string) $schedule->createdBy->name,
                        ]
                        : null,
                    'created_at' => $schedule->created_at?->format('Y-m-d H:i'),
                ]
                : null,
            'scheduleChanges' => $scheduleChanges->map(fn ($c) => [
                'id' => (int) $c->id,
                'old_scheduled_for' => $c->old_scheduled_for?->format('Y-m-d H:i'),
                'new_scheduled_for' => $c->new_scheduled_for?->format('Y-m-d H:i'),
                'reason' => $c->reason,
                'changed_by' => $c->changedBy
                    ? [
                        'id' => (int) $c->changedBy->id,
                        'name' => (string) $c->changedBy->name,
                    ]
                    : null,
                'created_at' => $c->created_at?->format('Y-m-d H:i'),
            ])->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $incomingBridePhone = $request->input('bride_external_phone');
        if (is_string($incomingBridePhone)) {
            $request->merge([
                'bride_external_phone' => PhoneNormalizer::normalize($incomingBridePhone),
            ]);
        }

        $validated = $request->validate([
            'groom_member_uuid' => ['required', 'string', 'max:190'],
            'bride_member_uuid' => ['nullable', 'string', 'max:190'],

            'bride_external_full_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'bride_external_address' => ['nullable', 'string', 'max:190', 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_home_parish_name' => ['nullable', 'string', 'max:190', 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_zone_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_jumuiya_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
        ]);

        $groom = Member::query()->where('uuid', $validated['groom_member_uuid'])->firstOrFail();
        $bride = null;
        if (! empty($validated['bride_member_uuid'])) {
            $bride = Member::query()->where('uuid', $validated['bride_member_uuid'])->firstOrFail();

            if ((int) $groom->id === (int) $bride->id) {
                return back()->with('error', 'Groom and bride cannot be the same member.');
            }
        }

        if (! $groom->family_id || ! $groom->jumuiya_id) {
            return back()->with('error', 'Groom must belong to a family and Christian Community.');
        }

        if ($bride) {
            if (! $bride->family_id || ! $bride->jumuiya_id) {
                return back()->with('error', 'Bride must belong to a family and Christian Community.');
            }
        } else {
            $externalName = NormalizesNames::normalize($validated['bride_external_full_name'] ?? null, true);
            if ($externalName === '') {
                return back()->with('error', 'Please select a registered bride or provide external bride full name.');
            }
        }

        $groomParishId = (int) DB::table('jumuiyas')
            ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
            ->where('jumuiyas.id', (int) $groom->jumuiya_id)
            ->value('zones.parish_id');

        if (! $groomParishId) {
            return back()->with('error', 'Unable to determine groom parish.');
        }

        $brideParishId = null;
        if ($bride && $bride->jumuiya_id) {
            $brideParishId = (int) DB::table('jumuiyas')
                ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                ->where('jumuiyas.id', (int) $bride->jumuiya_id)
                ->value('zones.parish_id');

            $brideParishId = $brideParishId ?: null;
        }

        $user = $request->user();
        $userId = (int) ($user?->id ?? 0);
        $userMemberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);

        if ($brideParishId && $groomParishId && $brideParishId !== $groomParishId) {
            if (! $user || ! $user->can('marriages.cross_parish.create')) {
                return back()->with('error', 'Cross-parish marriage requests can only be created by authorized parish staff.');
            }
        }

        $this->ensureActiveJumuiyaLeaderForMember($userId, $userMemberId, (int) $groom->jumuiya_id);

        $u1 = (string) $groom->uuid;
        $u2 = $bride ? (string) $bride->uuid : mb_strtolower(trim((string) ($validated['bride_external_full_name'] ?? '')), 'UTF-8');
        $sorted = [mb_strtolower($u1, 'UTF-8'), $u2];
        sort($sorted);
        $coupleKey = $sorted[0].':'.$sorted[1];

        $existing = Marriage::query()
            ->where('parish_id', $groomParishId)
            ->where('couple_key', $coupleKey)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return redirect()->route('marriages.show', $existing)->with('info', 'A marriage request already exists for this couple.');
        }

        try {
            $marriage = Marriage::create([
                'uuid' => (string) Str::uuid(),
                'parish_id' => $groomParishId,
                'origin_jumuiya_id' => (int) $groom->jumuiya_id,
                'groom_member_id' => (int) $groom->id,
                'groom_family_id' => (int) $groom->family_id,
                'groom_jumuiya_id' => (int) $groom->jumuiya_id,
                'groom_parish_id' => $groomParishId,
                'bride_member_id' => $bride ? (int) $bride->id : null,
                'bride_external_full_name' => $bride ? null : NormalizesNames::normalize($validated['bride_external_full_name'] ?? null, true),
                'bride_external_phone' => $bride ? null : (is_string($validated['bride_external_phone'] ?? null) && trim($validated['bride_external_phone']) !== '' ? PhoneNormalizer::normalize((string) $validated['bride_external_phone']) : null),
                'bride_external_address' => $bride ? null : (is_string($validated['bride_external_address'] ?? null) && trim($validated['bride_external_address']) !== '' ? trim((string) $validated['bride_external_address']) : null),
                'bride_external_home_parish_name' => $bride ? null : (is_string($validated['bride_external_home_parish_name'] ?? null) && trim($validated['bride_external_home_parish_name']) !== '' ? trim((string) $validated['bride_external_home_parish_name']) : null),
                'bride_external_zone_name' => $bride ? null : NormalizesNames::normalize($validated['bride_external_zone_name'] ?? null, true),
                'bride_external_jumuiya_name' => $bride ? null : NormalizesNames::normalize($validated['bride_external_jumuiya_name'] ?? null, true),
                'bride_family_id' => $bride ? (int) $bride->family_id : null,
                'bride_jumuiya_id' => $bride ? (int) $bride->jumuiya_id : null,
                'bride_parish_id' => $bride ? ($brideParishId ?: null) : null,
                'couple_key' => $coupleKey,
                'status' => Marriage::STATUS_DRAFT,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $existing = Marriage::query()
                ->where('parish_id', $groomParishId)
                ->where('couple_key', $coupleKey)
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return redirect()->route('marriages.show', $existing)->with('info', 'A marriage request already exists for this couple.');
            }

            throw $e;
        }

        return redirect()->route('marriages.show', $marriage)->with('success', 'Marriage request saved.');
    }

    public function saveDraft(Request $request, Marriage $marriage): RedirectResponse
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $marriage->parish_id !== $parishId) {
            abort(404);
        }

        $this->ensureActiveJumuiyaLeaderForMember((int) ($user?->id ?? 0), (int) ($user?->member_id ?? $user?->member?->id ?? 0), (int) $marriage->origin_jumuiya_id);

        if (! in_array($marriage->status, [Marriage::STATUS_DRAFT, Marriage::STATUS_SUBMITTED, Marriage::STATUS_REJECTED], true)) {
            return back()->with('error', 'This request can no longer be updated.');
        }

        $incomingBridePhone = $request->input('bride_external_phone');
        if (is_string($incomingBridePhone)) {
            $request->merge([
                'bride_external_phone' => PhoneNormalizer::normalize($incomingBridePhone),
            ]);
        }

        $incomingMalePhone = $request->input('male_witness_phone');
        if (is_string($incomingMalePhone)) {
            $request->merge([
                'male_witness_phone' => PhoneNormalizer::normalize($incomingMalePhone),
            ]);
        }

        $incomingFemalePhone = $request->input('female_witness_phone');
        if (is_string($incomingFemalePhone)) {
            $request->merge([
                'female_witness_phone' => PhoneNormalizer::normalize($incomingFemalePhone),
            ]);
        }

        $incomingSponsors = $request->input('sponsors');
        if (is_array($incomingSponsors)) {
            $normalizedSponsors = [];
            foreach ($incomingSponsors as $s) {
                if (! is_array($s)) continue;
                $phone = $s['phone'] ?? null;
                if (is_string($phone)) {
                    $s['phone'] = PhoneNormalizer::normalize($phone);
                }
                $normalizedSponsors[] = $s;
            }
            $request->merge(['sponsors' => $normalizedSponsors]);
        }

        $incomingGroomParents = $request->input('groom_parents');
        if (is_array($incomingGroomParents)) {
            $fatherPhone = $incomingGroomParents['father_phone'] ?? null;
            $motherPhone = $incomingGroomParents['mother_phone'] ?? null;
            if (is_string($fatherPhone)) {
                $incomingGroomParents['father_phone'] = PhoneNormalizer::normalize($fatherPhone);
            }
            if (is_string($motherPhone)) {
                $incomingGroomParents['mother_phone'] = PhoneNormalizer::normalize($motherPhone);
            }
            $request->merge(['groom_parents' => $incomingGroomParents]);
        }

        $incomingBrideParents = $request->input('bride_parents');
        if (is_array($incomingBrideParents)) {
            $fatherPhone = $incomingBrideParents['father_phone'] ?? null;
            $motherPhone = $incomingBrideParents['mother_phone'] ?? null;
            if (is_string($fatherPhone)) {
                $incomingBrideParents['father_phone'] = PhoneNormalizer::normalize($fatherPhone);
            }
            if (is_string($motherPhone)) {
                $incomingBrideParents['mother_phone'] = PhoneNormalizer::normalize($motherPhone);
            }
            $request->merge(['bride_parents' => $incomingBrideParents]);
        }

        $validated = $request->validate([
            'marriage_date' => ['nullable', 'date', 'after_or_equal:today'],
            'marriage_time' => ['nullable', 'date_format:H:i'],
            'marriage_parish_uuid' => ['nullable', 'string', 'max:190'],
            'wedding_type' => ['nullable', 'string', 'max:50'],

            'bride_external_full_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'bride_external_address' => ['nullable', 'string', 'max:190', 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_home_parish_name' => ['nullable', 'string', 'max:190', 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_zone_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
            'bride_external_jumuiya_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],

            'male_witness_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
            'male_witness_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'male_witness_address' => ['nullable', 'string', 'max:190', 'regex:'.self::SAFE_TEXT_REGEX],
            'male_witness_relationship' => ['nullable', 'string', 'max:100', 'regex:'.self::SAFE_TEXT_REGEX],

            'female_witness_name' => ['nullable', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
            'female_witness_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'female_witness_address' => ['nullable', 'string', 'max:190', 'regex:'.self::SAFE_TEXT_REGEX],
            'female_witness_relationship' => ['nullable', 'string', 'max:100', 'regex:'.self::SAFE_TEXT_REGEX],

            'groom_parents' => ['nullable', 'array'],
            'groom_parents.father_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'groom_parents.mother_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'bride_parents' => ['nullable', 'array'],
            'bride_parents.father_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'bride_parents.mother_phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],

            'sponsors' => ['nullable', 'array'],
            'sponsors.*.role' => ['required_with:sponsors', 'string', 'max:50'],
            'sponsors.*.full_name' => ['required_with:sponsors', 'string', 'max:190', 'regex:'.self::NAME_REGEX, 'regex:'.self::SAFE_TEXT_REGEX],
            'sponsors.*.phone' => ['nullable', 'string', 'max:20', 'regex:'.PhoneNormalizer::TZ_REGEX],
            'sponsors.*.address' => ['nullable', 'string', 'max:190', 'regex:'.self::SAFE_TEXT_REGEX],
            'sponsors.*.relationship' => ['nullable', 'string', 'max:100', 'regex:'.self::SAFE_TEXT_REGEX],
            'sponsors.*.notes' => ['nullable', 'string', 'max:5000', 'regex:'.self::SAFE_TEXT_REGEX],
        ]);

        $marriageParishId = null;
        if (! empty($validated['marriage_parish_uuid'])) {
            $marriageParishId = (int) DB::table('parishes')->where('uuid', $validated['marriage_parish_uuid'])->value('id');
        }

        $groomParents = is_array($validated['groom_parents'] ?? null) ? $validated['groom_parents'] : [];
        $brideParents = is_array($validated['bride_parents'] ?? null) ? $validated['bride_parents'] : [];
        $sponsors = is_array($validated['sponsors'] ?? null) ? $validated['sponsors'] : [];

        $normName = fn ($v) => NormalizesNames::normalize(is_string($v) ? $v : null, true);
        $normText = fn ($v) => (is_string($v) && trim($v) !== '') ? trim((string) $v) : null;
        $normPhone = fn ($v) => PhoneNormalizer::normalize(is_string($v) ? $v : null);

        try {
            DB::transaction(function () use ($marriage, $validated, $marriageParishId, $groomParents, $brideParents, $sponsors, $normName, $normText, $normPhone): void {
                if (in_array($marriage->status, [Marriage::STATUS_SUBMITTED, Marriage::STATUS_REJECTED], true)) {
                    $marriage->forceFill([
                        'status' => Marriage::STATUS_DRAFT,
                        'rejected_at' => null,
                        'rejected_by_user_id' => null,
                        'rejection_reason' => null,
                        'submitted_at' => null,
                        'submitted_by_user_id' => null,
                    ])->save();
                }

                $marriage->forceFill([
                    'marriage_date' => $validated['marriage_date'] ?? $marriage->marriage_date,
                    'marriage_time' => $validated['marriage_time'] ?? $marriage->marriage_time,
                    'marriage_parish_id' => $marriageParishId,
                    'wedding_type' => $validated['wedding_type'] ?? $marriage->wedding_type,

                    'bride_external_full_name' => array_key_exists('bride_external_full_name', $validated) ? $normName($validated['bride_external_full_name']) : $marriage->bride_external_full_name,
                    'bride_external_phone' => array_key_exists('bride_external_phone', $validated) ? $normPhone($validated['bride_external_phone']) : $marriage->bride_external_phone,
                    'bride_external_address' => array_key_exists('bride_external_address', $validated) ? $normText($validated['bride_external_address']) : $marriage->bride_external_address,
                    'bride_external_home_parish_name' => array_key_exists('bride_external_home_parish_name', $validated) ? $normText($validated['bride_external_home_parish_name']) : $marriage->bride_external_home_parish_name,
                    'bride_external_zone_name' => array_key_exists('bride_external_zone_name', $validated) ? $normName($validated['bride_external_zone_name']) : $marriage->bride_external_zone_name,
                    'bride_external_jumuiya_name' => array_key_exists('bride_external_jumuiya_name', $validated) ? $normName($validated['bride_external_jumuiya_name']) : $marriage->bride_external_jumuiya_name,

                    'male_witness_name' => array_key_exists('male_witness_name', $validated) ? $normName($validated['male_witness_name']) : $marriage->male_witness_name,
                    'male_witness_phone' => array_key_exists('male_witness_phone', $validated) ? $normPhone($validated['male_witness_phone']) : $marriage->male_witness_phone,
                    'male_witness_address' => array_key_exists('male_witness_address', $validated) ? $normText($validated['male_witness_address']) : $marriage->male_witness_address,
                    'male_witness_relationship' => array_key_exists('male_witness_relationship', $validated) ? $normText($validated['male_witness_relationship']) : $marriage->male_witness_relationship,

                    'female_witness_name' => array_key_exists('female_witness_name', $validated) ? $normName($validated['female_witness_name']) : $marriage->female_witness_name,
                    'female_witness_phone' => array_key_exists('female_witness_phone', $validated) ? $normPhone($validated['female_witness_phone']) : $marriage->female_witness_phone,
                    'female_witness_address' => array_key_exists('female_witness_address', $validated) ? $normText($validated['female_witness_address']) : $marriage->female_witness_address,
                    'female_witness_relationship' => array_key_exists('female_witness_relationship', $validated) ? $normText($validated['female_witness_relationship']) : $marriage->female_witness_relationship,
                ])->save();

                $upsertParents = function (string $party, array $data) use ($marriage, $normName, $normPhone): void {
                    $fatherName = $normName($data['father_name'] ?? null);
                    $motherName = $normName($data['mother_name'] ?? null);

                    $fatherPhone = $normPhone($data['father_phone'] ?? null);
                    $motherPhone = $normPhone($data['mother_phone'] ?? null);

                    $fatherIsAlive = array_key_exists('father_is_alive', $data) ? (is_null($data['father_is_alive']) ? null : (bool) $data['father_is_alive']) : null;
                    $motherIsAlive = array_key_exists('mother_is_alive', $data) ? (is_null($data['mother_is_alive']) ? null : (bool) $data['mother_is_alive']) : null;

                    MarriageParent::query()->updateOrCreate(
                        ['marriage_id' => (int) $marriage->id, 'party' => $party],
                        [
                            'father_member_id' => null,
                            'father_name' => $fatherName,
                            'father_phone' => $fatherPhone,
                            'father_religion' => $data['father_religion'] ?? null,
                            'father_is_alive' => $fatherIsAlive,
                            'mother_member_id' => null,
                            'mother_name' => $motherName,
                            'mother_phone' => $motherPhone,
                            'mother_religion' => $data['mother_religion'] ?? null,
                            'mother_is_alive' => $motherIsAlive,
                        ]
                    );
                };

                if (! empty($groomParents)) {
                    $upsertParents('groom', $groomParents);
                }

                if (! empty($brideParents)) {
                    $upsertParents('bride', $brideParents);
                }

                if (is_array($sponsors)) {
                    MarriageSponsor::query()->where('marriage_id', (int) $marriage->id)->delete();

                    $now = now();
                    $bulk = [];
                    foreach ($sponsors as $s) {
                        if (! is_array($s)) {
                            continue;
                        }

                        $role = trim((string) ($s['role'] ?? ''));
                        $fullName = $normName($s['full_name'] ?? null);
                        if ($role === '' || $fullName === '') {
                            continue;
                        }

                        $bulk[] = [
                            'marriage_id' => (int) $marriage->id,
                            'role' => $role,
                            'full_name' => $fullName,
                            'phone' => $normPhone($s['phone'] ?? null),
                            'address' => $normText($s['address'] ?? null),
                            'relationship' => $normText($s['relationship'] ?? null),
                            'notes' => $normText($s['notes'] ?? null),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if (! empty($bulk)) {
                        MarriageSponsor::query()->insert($bulk);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('Marriage draft save failed', ['exception' => $e, 'marriage_uuid' => $marriage->uuid]);
            return back()->withErrors(['draft' => 'Unable to save draft. Please try again.'])->withInput();
        }

        return back()->with('success', 'Draft saved.');
    }

    public function submit(Request $request, Marriage $marriage): RedirectResponse
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $marriage->parish_id !== $parishId) {
            abort(404);
        }

        $this->ensureActiveJumuiyaLeaderForMember((int) ($user?->id ?? 0), (int) ($user?->member_id ?? $user?->member?->id ?? 0), (int) $marriage->origin_jumuiya_id);

        if ($marriage->status !== Marriage::STATUS_DRAFT) {
            return back()->with('error', 'Only draft requests can be submitted.');
        }

        $marriage->load(['groom:id,uuid,family_id,jumuiya_id', 'bride:id,uuid,family_id,jumuiya_id', 'parents', 'attachments']);

        if (! $marriage->groom) {
            return back()->with('error', 'Invalid groom.');
        }

        if (! $marriage->groom->family_id || ! $marriage->groom->jumuiya_id) {
            return back()->with('error', 'Groom must belong to a family and Christian Community.');
        }
        if ($marriage->bride) {
            if (! $marriage->bride->family_id || ! $marriage->bride->jumuiya_id) {
                return back()->with('error', 'Bride must belong to a family and Christian Community.');
            }
        } else {
            $externalName = NormalizesNames::normalize($marriage->bride_external_full_name ?? null, true);
            if ($externalName === '') {
                return back()->with('error', 'External bride full name is required.');
            }

            $externalPhone = PhoneNormalizer::normalize(is_string($marriage->bride_external_phone ?? null) ? (string) $marriage->bride_external_phone : null);
            if (! is_string($externalPhone) || trim($externalPhone) === '') {
                return back()->with('error', 'External bride phone is required.');
            }

            if (! preg_match(PhoneNormalizer::TZ_REGEX, $externalPhone)) {
                return back()->with('error', 'External bride phone format is invalid.');
            }

            $externalParish = is_string($marriage->bride_external_home_parish_name ?? null) ? trim((string) $marriage->bride_external_home_parish_name) : '';
            if ($externalParish === '') {
                return back()->with('error', 'External bride home parish name is required.');
            }
        }

        $missing = [];

        $parents = $marriage->parents ?? collect();
        $groomParents = $parents->firstWhere('party', 'groom');
        $brideParents = $parents->firstWhere('party', 'bride');

        $hasGroomFather = $groomParents && ((string) ($groomParents->father_name ?? '') !== '' || (int) ($groomParents->father_member_id ?? 0) > 0);
        $hasGroomMother = $groomParents && ((string) ($groomParents->mother_name ?? '') !== '' || (int) ($groomParents->mother_member_id ?? 0) > 0);
        $hasBrideFather = $brideParents && ((string) ($brideParents->father_name ?? '') !== '' || (int) ($brideParents->father_member_id ?? 0) > 0);
        $hasBrideMother = $brideParents && ((string) ($brideParents->mother_name ?? '') !== '' || (int) ($brideParents->mother_member_id ?? 0) > 0);

        if (! $hasGroomFather) $missing[] = 'Groom father details';
        if (! $hasGroomMother) $missing[] = 'Groom mother details';
        if (! $hasBrideFather) $missing[] = 'Bride father details';
        if (! $hasBrideMother) $missing[] = 'Bride mother details';

        $hasGroomBaptism = $marriage->attachments->where('type', 'groom_baptism_certificate')->count() > 0;
        $hasBrideBaptism = $marriage->attachments->where('type', 'bride_baptism_certificate')->count() > 0;
        if (! $hasGroomBaptism) $missing[] = 'Groom baptism certificate (PDF)';
        if (! $hasBrideBaptism) $missing[] = 'Bride baptism certificate (PDF)';

        $marriageDate = $marriage->marriage_date ? Carbon::parse($marriage->marriage_date)->startOfDay() : null;
        if (! $marriageDate) {
            $missing[] = 'Marriage date';
        } else {
            $min = now()->startOfDay();
            if ($marriageDate->lessThan($min)) {
                $missing[] = 'Marriage date must be today or in the future';
            }
        }

        $maleWitnessName = NormalizesNames::normalize($marriage->male_witness_name ?? null, true);
        $femaleWitnessName = NormalizesNames::normalize($marriage->female_witness_name ?? null, true);
        if ($maleWitnessName === '') $missing[] = 'Best man full name';
        if ($femaleWitnessName === '') $missing[] = 'Maid of honor full name';

        $maleWitnessPhone = PhoneNormalizer::normalize(is_string($marriage->male_witness_phone ?? null) ? (string) $marriage->male_witness_phone : null);
        $femaleWitnessPhone = PhoneNormalizer::normalize(is_string($marriage->female_witness_phone ?? null) ? (string) $marriage->female_witness_phone : null);
        if (! is_string($maleWitnessPhone) || trim($maleWitnessPhone) === '') $missing[] = 'Best man phone';
        if (! is_string($femaleWitnessPhone) || trim($femaleWitnessPhone) === '') $missing[] = 'Maid of honor phone';

        if (is_string($maleWitnessPhone) && trim($maleWitnessPhone) !== '' && ! preg_match(PhoneNormalizer::TZ_REGEX, $maleWitnessPhone)) {
            $missing[] = 'Best man phone format must be valid';
        }
        if (is_string($femaleWitnessPhone) && trim($femaleWitnessPhone) !== '' && ! preg_match(PhoneNormalizer::TZ_REGEX, $femaleWitnessPhone)) {
            $missing[] = 'Maid of honor phone format must be valid';
        }

        if (! $marriage->bride_member_id) {
            $hasBrideLetter = $marriage->attachments->where('type', 'bride_home_parish_letter')->count() > 0;
            if (! $hasBrideLetter) $missing[] = 'Bride home parish letter (external parish clearance)';
        } elseif ($marriage->bride_parish_id && (int) $marriage->bride_parish_id !== (int) $marriage->groom_parish_id) {
            $hasBrideLetter = $marriage->attachments->where('type', 'bride_home_parish_letter')->count() > 0;
            if (! $hasBrideLetter) $missing[] = 'Bride home parish letter (external parish clearance)';
        }

        if (! empty($missing)) {
            return back()->with('error', 'Cannot submit this marriage request yet. Missing: '.implode(', ', $missing).'.');
        }

        $marriage->forceFill([
            'status' => Marriage::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'submitted_by_user_id' => $user?->id,
            'submitted_origin_jumuiya_id' => (int) $marriage->origin_jumuiya_id,
            'submitted_by_member_id' => (int) ($user?->member_id ?? $user?->member?->id ?? 0) ?: null,
        ])->save();

        return back()->with('success', 'Marriage request submitted.');
    }

    public function approve(Request $request, Marriage $marriage): RedirectResponse
    {
        if ($marriage->status !== Marriage::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted requests can be approved.');
        }

        $user = $request->user();

        $marriage->forceFill([
            'status' => Marriage::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $user?->id,
            'rejected_at' => null,
            'rejected_by_user_id' => null,
            'rejection_reason' => null,
        ])->save();

        return back()->with('success', 'Marriage request approved.');
    }

    public function reject(Request $request, Marriage $marriage): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        if (! in_array($marriage->status, [Marriage::STATUS_SUBMITTED, Marriage::STATUS_APPROVED], true)) {
            return back()->with('error', 'Only submitted/approved requests can be rejected.');
        }

        $user = $request->user();

        $marriage->forceFill([
            'status' => Marriage::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by_user_id' => $user?->id,
            'rejection_reason' => (string) $validated['reason'],
        ])->save();

        return back()->with('success', 'Marriage request rejected.');
    }

    public function schedule(Request $request, Marriage $marriage): RedirectResponse
    {
        if ($marriage->status !== Marriage::STATUS_APPROVED) {
            return back()->with('error', 'Only approved marriages can be scheduled.');
        }

        $validated = $request->validate([
            'scheduled_for' => ['required', 'date'],
            'location_parish_uuid' => ['nullable', 'string', 'max:190'],
            'location_text' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        $newDate = Carbon::parse($validated['scheduled_for']);
        $minDate = now()->addDay()->startOfDay();
        if ($newDate->lessThan($minDate)) {
            return back()->withErrors(['scheduled_for' => 'Scheduled date must be at least tomorrow.']);
        }

        DB::transaction(function () use ($validated, $user, $marriage, $newDate): void {
            $locationParishId = null;
            if (! empty($validated['location_parish_uuid'])) {
                $locationParishId = (int) DB::table('parishes')->where('uuid', $validated['location_parish_uuid'])->value('id');
            }

            $schedule = SacramentSchedule::query()
                ->where('entity_type', 'marriage')
                ->where('entity_id', $marriage->getKey())
                ->orderByDesc('id')
                ->first();

            $status = ! empty($validated['status']) ? (string) $validated['status'] : ($schedule ? 'changed' : 'proposed');
            $oldDate = $schedule?->scheduled_for;

            $schedule = SacramentSchedule::create([
                'parish_id' => (int) $marriage->parish_id,
                'entity_type' => 'marriage',
                'entity_id' => $marriage->getKey(),
                'scheduled_for' => $newDate,
                'location_parish_id' => $locationParishId,
                'location_text' => $validated['location_text'] ?? null,
                'status' => $status,
                'created_by_user_id' => (int) $user->id,
            ]);

            SacramentScheduleChange::create([
                'sacrament_schedule_id' => $schedule->getKey(),
                'old_scheduled_for' => $oldDate,
                'new_scheduled_for' => $newDate,
                'changed_by_user_id' => (int) $user->id,
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        return back()->with('success', 'Schedule saved.');
    }

    public function complete(Request $request, Marriage $marriage): RedirectResponse
    {
        if ($marriage->status !== Marriage::STATUS_APPROVED) {
            return back()->with('error', 'Only approved marriages can be marked as completed.');
        }

        $schedule = SacramentSchedule::query()
            ->where('entity_type', 'marriage')
            ->where('entity_id', $marriage->getKey())
            ->orderByDesc('id')
            ->first();

        if (! $schedule) {
            return back()->with('error', 'Schedule is required before marking as completed.');
        }

        $marriage->forceFill([
            'status' => Marriage::STATUS_COMPLETED,
            'completed_at' => now(),
            'marriage_date' => $marriage->marriage_date ?: $schedule->scheduled_for,
        ])->save();

        return back()->with('success', 'Marriage marked as completed.');
    }

    public function issue(Request $request, Marriage $marriage): RedirectResponse
    {
        if ($marriage->status !== Marriage::STATUS_COMPLETED) {
            return back()->with('error', 'Only completed marriages can be issued.');
        }

        $user = $request->user();

        $marriage->forceFill([
            'status' => Marriage::STATUS_ISSUED,
            'issued_at' => now(),
            'issued_by_user_id' => $user?->id,
        ])->save();

        return back()->with('success', 'Marriage certificate marked as issued.');
    }

    protected function ensureActiveJumuiyaLeaderForMember(?int $userId, int $userMemberId, int $targetJumuiyaId): void
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
}
