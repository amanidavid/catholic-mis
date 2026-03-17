<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\People\FamilyRelationship;
use App\Models\People\Member;
use App\Models\Sacraments\Baptism;
use App\Models\Sacraments\BaptismSponsor;
use App\Models\Sacraments\SacramentAttachment;
use App\Models\Sacraments\SacramentSchedule;
use App\Models\Sacraments\SacramentScheduleChange;
use App\Models\Structure\Jumuiya;
use App\Http\Resources\Sacraments\BaptismResource;
use App\Services\Certificates\CertificateService;
use App\Support\PhoneNormalizer;
use App\Traits\NormalizesNames;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BaptismController extends Controller
{
    private static ?bool $membersHaveNameKeyColumns = null;
    private static ?array $parentRelationshipIds = null;

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

    private function parentRelationshipIds(): array
    {
        if (self::$parentRelationshipIds !== null) {
            return self::$parentRelationshipIds;
        }

        $fatherId = (int) (FamilyRelationship::query()->where('name', 'father')->value('id') ?? 0);
        if (! $fatherId) {
            $fatherId = (int) (FamilyRelationship::query()->whereRaw('lower(name) = ?', ['father'])->value('id') ?? 0);
        }

        $motherId = (int) (FamilyRelationship::query()->where('name', 'mother')->value('id') ?? 0);
        if (! $motherId) {
            $motherId = (int) (FamilyRelationship::query()->whereRaw('lower(name) = ?', ['mother'])->value('id') ?? 0);
        }

        return self::$parentRelationshipIds = ['father' => $fatherId, 'mother' => $motherId];
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

    private function canViewBaptism(Request $request, Baptism $baptism): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $canGlobalView = $user->can('users.manage')
            || $user->can('permissions.manage');

        if ($canGlobalView) {
            return true;
        }

        $parishId = (int) ($user->parish_id ?? 0);
        if ($parishId && (int) $baptism->parish_id === $parishId && $user->can('baptisms.parish.view')) {
            return true;
        }

        $memberId = (int) ($user->member_id ?? $user->member?->id ?? 0);
        $leaderJumuiyaIds = $this->activeLeadershipJumuiyaIds($memberId);

        return (int) $baptism->origin_jumuiya_id > 0
            && in_array((int) $baptism->origin_jumuiya_id, $leaderJumuiyaIds, true);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Sacraments/Baptisms/Create');
    }

    public function show(Request $request, Baptism $baptism): Response
    {
        if (! $this->canViewBaptism($request, $baptism)) {
            abort(404);
        }

        $baptism->load([
            'member:id,uuid,family_id,jumuiya_id,first_name,middle_name,last_name,marital_status,phone,email',
            'originJumuiya:id,uuid,zone_id,name',
            'originJumuiya.zone:id,uuid,parish_id,name',
            'originJumuiya.zone.parish:id,uuid,name',
            'family:id,uuid,family_name,jumuiya_id',
            'fatherMember:id,uuid,first_name,middle_name,last_name,marital_status,phone,email',
            'motherMember:id,uuid,first_name,middle_name,last_name,marital_status,phone,email',
            'attachments',
            'sponsors.member:id,uuid,first_name,middle_name,last_name,phone,email',
        ]);

        if (! $baptism->fatherMember || ! $baptism->motherMember) {
            $familyId = (int) ($baptism->family_id ?: ($baptism->member?->family_id ?? 0));
            if ($familyId) {
                $parentRel = $this->parentRelationshipIds();
                $fatherRelId = (int) ($parentRel['father'] ?? 0);
                $motherRelId = (int) ($parentRel['mother'] ?? 0);

                $relIds = array_values(array_filter([$fatherRelId, $motherRelId]));
                if (count($relIds) > 0) {
                    $members = Member::query()
                        ->select(['id', 'uuid', 'first_name', 'middle_name', 'last_name', 'marital_status', 'phone', 'email', 'family_relationship_id'])
                        ->where('family_id', $familyId)
                        ->whereIn('family_relationship_id', $relIds)
                        ->get()
                        ->keyBy('family_relationship_id');

                    if (! $baptism->fatherMember && $fatherRelId && $members->has($fatherRelId)) {
                        $baptism->setRelation('fatherMember', $members->get($fatherRelId));
                    }
                    if (! $baptism->motherMember && $motherRelId && $members->has($motherRelId)) {
                        $baptism->setRelation('motherMember', $members->get($motherRelId));
                    }
                }
            }
        }

        $schedule = SacramentSchedule::query()
            ->where('entity_type', 'baptism')
            ->where('entity_id', (int) $baptism->id)
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

        $marriageCert = $baptism->attachments
            ->where('type', 'parents_marriage_certificate')
            ->sortByDesc('id')
            ->first();

        return Inertia::render('Sacraments/Baptisms/Show', [
            'baptism' => new BaptismResource($baptism),
            'marriageCertificate' => $marriageCert,
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

    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $from = $request->query('from');
        $to = $request->query('to');
        $from = is_string($from) ? trim($from) : '';
        $to = is_string($to) ? trim($to) : '';

        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);
        $memberId = (int) ($user?->member_id ?? $user?->member?->id ?? 0);

        $leaderJumuiyaIds = $this->activeLeadershipJumuiyaIds($memberId);

        $canGlobalView = (bool) ($user?->can('users.manage')
            || $user?->can('permissions.manage'));

        $canParishView = (bool) ($user?->can('baptisms.parish.view'));

        $hasAnyScope = $canGlobalView || ($canParishView && $parishId > 0) || ! empty($leaderJumuiyaIds);

        $membersHaveKeys = $this->membersHaveNameKeyColumns();

        $idQuery = Baptism::query()
            ->select('baptisms.id')
            ->when($q !== '', function ($qb) {
                $qb->join('members', 'members.id', '=', 'baptisms.member_id');
            });

        $idQuery
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
            });

        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $tokens = preg_split('/\s+/u', trim($qLower), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $tokens = array_values(array_filter($tokens, fn ($t) => is_string($t) && $t !== ''));

            $phonePrefix = null;
            if (preg_match('/[0-9+]/', $q) === 1) {
                $phonePrefix = PhoneNormalizer::normalize($q);
            }

            $idQuery->where(function ($w) use ($qLower, $tokens, $phonePrefix, $membersHaveKeys) {
                $nameCols = $membersHaveKeys
                    ? ['members.first_name_key', 'members.middle_name_key', 'members.last_name_key', 'members.full_name_key']
                    : ['members.first_name', 'members.middle_name', 'members.last_name'];

                $applyToken = function ($builder, string $t) use ($nameCols): void {
                    $builder->where(function ($x) use ($t, $nameCols) {
                        foreach ($nameCols as $col) {
                            $x->orWhere($col, 'like', $t.'%');
                        }
                    });
                };

                if (count($tokens) > 0) {
                    foreach ($tokens as $t) {
                        $applyToken($w, $t);
                    }
                } else {
                    $applyToken($w, $qLower);
                }

                if (is_string($phonePrefix) && trim($phonePrefix) !== '') {
                    $w->orWhere('members.phone', 'like', trim($phonePrefix).'%');
                }
            });
        }

        if ($from !== '' && $to !== '') {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();
            if ($fromDate->greaterThan($toDate)) {
                $idRows = $idQuery
                    ->orderByDesc('baptisms.id')
                    ->paginate(15)
                    ->withQueryString();

                $ids = $idRows->getCollection()->map(fn ($r) => (int) $r->id)->filter()->values()->all();

                $models = empty($ids)
                    ? collect()
                    : Baptism::query()
                        ->whereIn('id', $ids)
                        ->with([
                            'member:id,uuid,first_name,middle_name,last_name,jumuiya_id',
                            'originJumuiya:id,uuid,zone_id,name',
                            'originJumuiya.zone:id,uuid,parish_id,name',
                            'originJumuiya.zone.parish:id,uuid,name',
                            'family:id,uuid,family_name',
                        ])
                        ->orderByRaw('FIELD(id,'.implode(',', $ids).')')
                        ->get();

                $idRows->setCollection($models);

                return Inertia::render('Sacraments/Baptisms/Index', [
                    'filters' => [
                        'q' => $q,
                        'from' => $from,
                        'to' => $to,
                    ],
                    'baptisms' => BaptismResource::collection($idRows),
                    'error' => 'Invalid date range.',
                ]);
            }

            $idQuery->whereBetween('baptisms.created_at', [$fromDate, $toDate]);
        }

        $idRows = $idQuery
            ->orderByDesc('baptisms.id')
            ->paginate(15)
            ->withQueryString();

        $ids = $idRows->getCollection()->map(fn ($r) => (int) $r->id)->filter()->values()->all();

        $models = empty($ids)
            ? collect()
            : Baptism::query()
                ->whereIn('id', $ids)
                ->with([
                    'member:id,uuid,first_name,middle_name,last_name,jumuiya_id',
                    'originJumuiya:id,uuid,zone_id,name',
                    'originJumuiya.zone:id,uuid,parish_id,name',
                    'originJumuiya.zone.parish:id,uuid,name',
                    'family:id,uuid,family_name',
                ])
                ->orderByRaw('FIELD(id,'.implode(',', $ids).')')
                ->get();

        $idRows->setCollection($models);

        $baptisms = $idRows;

        return Inertia::render('Sacraments/Baptisms/Index', [
            'filters' => [
                'q' => $q,
                'from' => $from,
                'to' => $to,
            ],
            'baptisms' => BaptismResource::collection($baptisms),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'family_uuid' => ['required', 'string', 'max:190'],
            'member_uuid' => ['required', 'string', 'max:190'],
        ]);

        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        $member = Member::query()->where('uuid', $validated['member_uuid'])->firstOrFail();
        $family = DB::table('families')->where('uuid', $validated['family_uuid'])->first(['id', 'jumuiya_id']);
        $familyId = (int) ($family?->id ?? 0);
        $familyJumuiyaId = (int) ($family?->jumuiya_id ?? 0);

        if (! $parishId && $familyJumuiyaId) {
            $parishId = (int) DB::table('jumuiyas')
                ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                ->where('jumuiyas.id', $familyJumuiyaId)
                ->value('zones.parish_id');
        }

        if (! $parishId) {
            return back()->with('error', 'Your user account is missing parish assignment. Please contact the admin to set your parish.');
        }

        if (! $familyId) {
            return back()->with('error', 'Invalid family.');
        }

        if (! $familyJumuiyaId) {
            return back()->with('error', 'Invalid family Christian Community.');
        }

        if ((int) $member->family_id !== $familyId) {
            return back()->with('error', 'Child must belong to the selected family.');
        }

        if ((int) $member->jumuiya_id !== $familyJumuiyaId) {
            return back()->with('error', 'Selected child does not belong to the same Christian Community as the selected family.');
        }

        $this->ensureActiveJumuiyaLeaderForMember($user?->id, (int) ($user?->member_id ?? $user?->member?->id), $familyJumuiyaId);

        $existing = Baptism::query()
            ->where('parish_id', $parishId)
            ->where('member_id', (int) $member->id)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return redirect()->route('baptisms.show', $existing)->with('info', 'A baptism request already exists for this member.');
        }

        try {
            $baptism = Baptism::create([
                'parish_id' => $parishId,
                'origin_jumuiya_id' => $familyJumuiyaId,
                'member_id' => (int) $member->id,
                'family_id' => $familyId,
                'status' => Baptism::STATUS_DRAFT,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $existing = Baptism::query()
                ->where('parish_id', $parishId)
                ->where('member_id', (int) $member->id)
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                return redirect()->route('baptisms.show', $existing)->with('info', 'A baptism request already exists for this member.');
            }

            throw $e;
        }

        return redirect()->route('baptisms.show', $baptism)->with('success', 'Baptism request saved.');
    }

    public function saveDraft(Request $request, Baptism $baptism): RedirectResponse
    {
        $user = $request->user();

        $this->ensureActiveJumuiyaLeaderForMember($user?->id, (int) ($user?->member_id ?? $user?->member?->id), (int) $baptism->origin_jumuiya_id);

        if (! in_array($baptism->status, [Baptism::STATUS_DRAFT, Baptism::STATUS_SUBMITTED, Baptism::STATUS_REJECTED], true)) {
            return back()->with('error', 'This request can no longer be updated.');
        }

        $incomingPhone = $request->input('sponsor_phone');
        if (is_string($incomingPhone)) {
            $request->merge([
                'sponsor_phone' => PhoneNormalizer::normalize($incomingPhone),
            ]);
        }

        $validated = $request->validate([
            'birth_date' => ['nullable', 'date'],
            'birth_town' => ['nullable', 'string', 'max:190'],
            'residence' => ['nullable', 'string', 'max:190'],

            'sponsor_role' => ['nullable', 'string', 'max:100'],
            'sponsor_member_uuid' => ['nullable', 'uuid'],
            'sponsor_full_name' => ['nullable', 'string', 'max:190', "regex:/^[\\pL\\pM\\s\\-\\.\'\"()]+$/u"],
            'sponsor_parish_name' => ['nullable', 'string', 'max:190', "regex:/^[\\pL\\pM\\d\\s\\-\\.\'\"()\\/,&]+$/u"],
            'sponsor_phone' => ['nullable', 'string', 'max:50'],
            'sponsor_email' => ['nullable', 'email', 'max:190'],

            'parents_marriage_certificate' => ['nullable', 'file', 'max:3072', 'mimetypes:application/pdf'],
            'sponsor_confirmation_certificate' => ['nullable', 'file', 'max:3072', 'mimetypes:application/pdf'],
            'birth_certificate' => ['nullable', 'file', 'max:3072', 'mimetypes:application/pdf'],
        ]);

        if (is_string($validated['sponsor_full_name'] ?? null)) {
            $validated['sponsor_full_name'] = trim((string) $validated['sponsor_full_name']);
        }
        if (is_string($validated['sponsor_parish_name'] ?? null)) {
            $validated['sponsor_parish_name'] = trim((string) $validated['sponsor_parish_name']);
        }
        if (is_string($validated['birth_town'] ?? null)) {
            $validated['birth_town'] = trim((string) $validated['birth_town']);
        }
        if (is_string($validated['residence'] ?? null)) {
            $validated['residence'] = trim((string) $validated['residence']);
        }
        if (is_string($validated['sponsor_role'] ?? null)) {
            $validated['sponsor_role'] = trim((string) $validated['sponsor_role']);
        }

        $hasSponsorAlready = BaptismSponsor::query()->where('baptism_id', (int) $baptism->id)->exists();

        $hasSponsorMember = is_string(($validated['sponsor_member_uuid'] ?? null)) && trim((string) $validated['sponsor_member_uuid']) !== '';
        $hasSponsorName = is_string(($validated['sponsor_full_name'] ?? null)) && trim((string) $validated['sponsor_full_name']) !== '';
        $hasSponsorInput = $hasSponsorMember || $hasSponsorName;

        $storedPaths = [];

        try {
            foreach (['parents_marriage_certificate', 'sponsor_confirmation_certificate', 'birth_certificate'] as $type) {
                $file = $request->file($type);
                if (! $file) {
                    continue;
                }

                $attachmentUuid = (string) Str::uuid();
                $ext = $file->getClientOriginalExtension();
                $ext = is_string($ext) && $ext !== '' ? strtolower($ext) : 'bin';
                $relativePath = 'sacraments/baptisms/'.$baptism->uuid.'/'.$type.'/'.$attachmentUuid.'.'.$ext;

                $stored = Storage::disk('local')->putFileAs(
                    dirname($relativePath),
                    $file,
                    basename($relativePath)
                );

                if (! $stored) {
                    throw new \RuntimeException('Failed to upload file.');
                }

                $storedPaths[$type] = $relativePath;
            }

            DB::transaction(function () use ($validated, $baptism, $user, $hasSponsorAlready, $hasSponsorMember, $hasSponsorInput, $storedPaths, $request): void {
                if (in_array($baptism->status, [Baptism::STATUS_SUBMITTED, Baptism::STATUS_REJECTED], true)) {
                    $baptism->forceFill([
                        'status' => Baptism::STATUS_DRAFT,
                        'rejected_at' => null,
                        'rejected_by_user_id' => null,
                        'rejection_reason' => null,
                        'submitted_at' => null,
                        'submitted_by_user_id' => null,
                    ])->save();
                }

                $baptism->forceFill([
                    'birth_date' => $validated['birth_date'] ?? $baptism->birth_date,
                    'birth_town' => $validated['birth_town'] ?? $baptism->birth_town,
                    'residence' => $validated['residence'] ?? $baptism->residence,
                ])->save();

                if (! $hasSponsorAlready && $hasSponsorInput) {
                    $memberId = null;
                    if ($hasSponsorMember) {
                        $memberId = (int) Member::query()->where('uuid', trim((string) ($validated['sponsor_member_uuid'] ?? '')))->value('id');
                        if (! $memberId) {
                            throw new \RuntimeException('Invalid sponsor member.');
                        }
                    }

                    BaptismSponsor::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'baptism_id' => (int) $baptism->id,
                        'role' => $validated['sponsor_role'] ?: null,
                        'member_id' => $memberId,
                        'full_name' => $hasSponsorMember ? null : NormalizesNames::normalize($validated['sponsor_full_name'] ?: null, true),
                        'parish_name' => NormalizesNames::normalize($validated['sponsor_parish_name'] ?: null, true),
                        'phone' => $validated['sponsor_phone'] ?: null,
                        'email' => $validated['sponsor_email'] ?? null,
                    ]);
                }

                $bulk = [];
                foreach ($storedPaths as $type => $relativePath) {
                    $file = $request->file($type);
                    if (! $file) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', Storage::disk('local')->path($relativePath));

                    $now = now();
                    $bulk[] = [
                        'uuid' => (string) Str::uuid(),
                        'parish_id' => (int) $baptism->parish_id,
                        'entity_type' => 'baptism',
                        'entity_id' => (int) $baptism->id,
                        'type' => $type,
                        'original_name' => (string) $file->getClientOriginalName(),
                        'mime_type' => 'application/pdf',
                        'size_bytes' => (int) $file->getSize(),
                        'storage_disk' => 'local',
                        'storage_path' => $relativePath,
                        'sha256' => $sha256,
                        'uploaded_by_user_id' => (int) $user->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($bulk)) {
                    SacramentAttachment::query()->insert($bulk);
                }
            });
        } catch (\RuntimeException $e) {
            foreach ($storedPaths as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('local')->delete($path);
                }
            }

            return back()->withErrors(['draft' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('local')->delete($path);
                }
            }

            Log::error('Baptism draft save failed', ['exception' => $e, 'baptism_uuid' => $baptism->uuid]);
            return back()->withErrors(['draft' => 'Unable to save draft. Please try again.'])->withInput();
        }

        return back()->with('success', 'Draft saved.');
    }

    public function changeSubject(Request $request, Baptism $baptism): RedirectResponse
    {
        $validated = $request->validate([
            'family_uuid' => ['required', 'string', 'max:190'],
            'member_uuid' => ['required', 'string', 'max:190'],
        ]);

        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $baptism->parish_id !== $parishId) {
            abort(404);
        }

        if (in_array($baptism->status, [Baptism::STATUS_APPROVED, Baptism::STATUS_COMPLETED, Baptism::STATUS_ISSUED], true)) {
            return back()->with('error', 'This request can no longer be updated.');
        }

        $member = Member::query()->where('uuid', $validated['member_uuid'])->firstOrFail();
        $family = DB::table('families')->where('uuid', $validated['family_uuid'])->first(['id', 'jumuiya_id']);
        $familyId = (int) ($family?->id ?? 0);
        $familyJumuiyaId = (int) ($family?->jumuiya_id ?? 0);

        if (! $familyId) {
            return back()->with('error', 'Invalid family.');
        }

        if (! $familyJumuiyaId) {
            return back()->with('error', 'Invalid family Christian Community.');
        }

        if ((int) $member->family_id !== $familyId) {
            return back()->with('error', 'Child must belong to the selected family.');
        }

        if ((int) $member->jumuiya_id !== $familyJumuiyaId) {
            return back()->with('error', 'Selected child does not belong to the same Christian Community as the selected family.');
        }

        $this->ensureActiveJumuiyaLeaderForMember($user?->id, (int) ($user?->member_id ?? $user?->member?->id), $familyJumuiyaId);

        if (! $parishId && $familyJumuiyaId) {
            $parishId = (int) DB::table('jumuiyas')
                ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                ->where('jumuiyas.id', $familyJumuiyaId)
                ->value('zones.parish_id');
        }

        if (! $parishId) {
            return back()->with('error', 'Your user account is missing parish assignment. Please contact the admin to set your parish.');
        }

        $attachmentFiles = SacramentAttachment::query()
            ->where('entity_type', 'baptism')
            ->where('entity_id', (int) $baptism->id)
            ->get(['storage_disk', 'storage_path']);

        try {
            DB::transaction(function () use ($baptism, $parishId, $familyId, $familyJumuiyaId, $member): void {
                if (in_array($baptism->status, [Baptism::STATUS_SUBMITTED, Baptism::STATUS_REJECTED], true)) {
                    $baptism->forceFill([
                        'status' => Baptism::STATUS_DRAFT,
                        'rejected_at' => null,
                        'rejected_by_user_id' => null,
                        'rejection_reason' => null,
                        'submitted_at' => null,
                        'submitted_by_user_id' => null,
                    ])->save();
                }

                BaptismSponsor::query()->where('baptism_id', (int) $baptism->id)->delete();
                SacramentAttachment::query()->where('entity_type', 'baptism')->where('entity_id', (int) $baptism->id)->delete();

                $baptism->forceFill([
                    'parish_id' => $parishId,
                    'origin_jumuiya_id' => $familyJumuiyaId,
                    'family_id' => $familyId,
                    'member_id' => (int) $member->id,
                    'father_member_id' => null,
                    'mother_member_id' => null,
                    'father_name' => null,
                    'mother_name' => null,
                ])->save();
            });
        } catch (\Throwable $e) {
            Log::error('Baptism change subject failed', ['exception' => $e, 'baptism_uuid' => $baptism->uuid]);
            return back()->with('error', 'Unable to update request. Please try again.');
        }

        foreach ($attachmentFiles as $f) {
            $diskName = is_string($f->storage_disk ?? null) && $f->storage_disk !== '' ? $f->storage_disk : 'local';
            $p = is_string($f->storage_path ?? null) ? $f->storage_path : '';
            if ($p === '') {
                continue;
            }

            try {
                $d = Storage::disk($diskName);
                if ($d->exists($p)) {
                    $d->delete($p);
                }
            } catch (\Throwable) {
            }
        }

        return back()->with('success', 'Family/child updated. Please re-add sponsors and documents.');
    }

    public function submit(Request $request, Baptism $baptism): RedirectResponse
    {
        $user = $request->user();

        $incomingPhone = $request->input('sponsor_phone');
        if (is_string($incomingPhone)) {
            $request->merge([
                'sponsor_phone' => PhoneNormalizer::normalize($incomingPhone),
            ]);
        }

        $validated = $request->validate([
            'sponsor_role' => ['nullable', 'string', 'max:100'],
            'sponsor_member_uuid' => ['nullable', 'uuid'],
            'sponsor_full_name' => ['nullable', 'string', 'max:190', "regex:/^[\\pL\\pM\\s\\-\\.\'\"()]+$/u"],
            'sponsor_parish_name' => ['nullable', 'string', 'max:190', "regex:/^[\\pL\\pM\\d\\s\\-\\.\'\"()\\/,&]+$/u"],
            'sponsor_phone' => ['nullable', 'string', 'max:50'],
            'sponsor_email' => ['nullable', 'email', 'max:190'],

            'parents_marriage_certificate' => ['nullable', 'file', 'max:3072', 'mimetypes:application/pdf'],
            'sponsor_confirmation_certificate' => ['nullable', 'file', 'max:3072', 'mimetypes:application/pdf'],
            'birth_certificate' => ['nullable', 'file', 'max:3072', 'mimetypes:application/pdf'],
        ]);

        $member = $baptism->member;
        $this->ensureActiveJumuiyaLeaderForMember($user?->id, (int) ($user?->member_id ?? $user?->member?->id), (int) $baptism->origin_jumuiya_id);

        if ($baptism->status !== Baptism::STATUS_DRAFT) {
            return back()->with('error', 'Only draft requests can be submitted.');
        }

        $child = $member;
        if (! $child) {
            return back()->with('error', 'Invalid member.');
        }

        $familyId = (int) ($baptism->family_id ?: $child->family_id);
        if (! $familyId) {
            return back()->with('error', 'Family is required.');
        }

        $parentRel = $this->parentRelationshipIds();
        $fatherRelId = (int) ($parentRel['father'] ?? 0);
        $motherRelId = (int) ($parentRel['mother'] ?? 0);

        $father = $fatherRelId
            ? Member::query()->where('family_id', $familyId)->where('family_relationship_id', $fatherRelId)->first()
            : null;

        $mother = $motherRelId
            ? Member::query()->where('family_id', $familyId)->where('family_relationship_id', $motherRelId)->first()
            : null;

        if (! $father || ! $mother) {
            return back()->with('error', 'Father and mother are required in the family before submitting.');
        }

        $fatherStatus = strtolower(trim((string) ($father->marital_status ?? '')));
        $motherStatus = strtolower(trim((string) ($mother->marital_status ?? '')));

        if ($fatherStatus !== 'married' || $motherStatus !== 'married') {
            $fatherName = [$father->first_name, $father->middle_name, $father->last_name];
            $fatherName = trim(implode(' ', array_filter($fatherName)));
            $motherName = [$mother->first_name, $mother->middle_name, $mother->last_name];
            $motherName = trim(implode(' ', array_filter($motherName)));

            return back()->with('error', 'Both parents must be married before submitting. Father: '.($fatherName ?: '—').' ('.($father->marital_status ?? '—').'), Mother: '.($motherName ?: '—').' ('.($mother->marital_status ?? '—').').');
        }

        $sponsorMemberUuid = $validated['sponsor_member_uuid'] ?? null;
        $sponsorFullName = $validated['sponsor_full_name'] ?? null;

        if (is_string($sponsorFullName)) {
            $validated['sponsor_full_name'] = trim($sponsorFullName);
        }

        $sponsorParishName = $validated['sponsor_parish_name'] ?? null;
        if (is_string($sponsorParishName)) {
            $validated['sponsor_parish_name'] = trim($sponsorParishName);
        }

        $sponsorRole = $validated['sponsor_role'] ?? null;
        if (is_string($sponsorRole)) {
            $validated['sponsor_role'] = trim($sponsorRole);
        }

        $hasSponsorMember = is_string($sponsorMemberUuid) && trim($sponsorMemberUuid) !== '';
        $hasSponsorName = is_string($sponsorFullName) && trim($sponsorFullName) !== '';
        $hasSponsorExisting = BaptismSponsor::query()->where('baptism_id', (int) $baptism->id)->exists();

        if (! $hasSponsorExisting && ! $hasSponsorMember && ! $hasSponsorName) {
            return back()->withErrors([
                'sponsor_member_uuid' => 'Select a sponsor member or enter sponsor full name.',
                'sponsor_full_name' => 'Select a sponsor member or enter sponsor full name.',
            ])->withInput();
        }

        $alreadyMarriageCert = SacramentAttachment::query()
            ->where('entity_type', 'baptism')
            ->where('entity_id', (int) $baptism->id)
            ->where('type', 'parents_marriage_certificate')
            ->exists();

        $alreadySponsorConfirm = SacramentAttachment::query()
            ->where('entity_type', 'baptism')
            ->where('entity_id', (int) $baptism->id)
            ->where('type', 'sponsor_confirmation_certificate')
            ->exists();

        if (! $alreadyMarriageCert && ! $request->file('parents_marriage_certificate')) {
            return back()->withErrors(['parents_marriage_certificate' => 'Parents marriage certificate is required before submitting.'])->withInput();
        }

        if (! $alreadySponsorConfirm && ! $request->file('sponsor_confirmation_certificate')) {
            return back()->withErrors(['sponsor_confirmation_certificate' => 'Sponsor confirmation certificate is required before submitting.'])->withInput();
        }

        $storedPaths = [];
        try {
            $filesToStore = [];
            if (! $alreadyMarriageCert) {
                $parentsMarriageFile = $request->file('parents_marriage_certificate');
                if ($parentsMarriageFile) {
                    $filesToStore['parents_marriage_certificate'] = $parentsMarriageFile;
                }
            }

            if (! $alreadySponsorConfirm) {
                $sponsorConfirmFile = $request->file('sponsor_confirmation_certificate');
                if ($sponsorConfirmFile) {
                    $filesToStore['sponsor_confirmation_certificate'] = $sponsorConfirmFile;
                }
            }

            $birthCertFile = $request->file('birth_certificate');
            if ($birthCertFile) {
                $filesToStore['birth_certificate'] = $birthCertFile;
            }

            foreach ($filesToStore as $type => $file) {
                $attachmentUuid = (string) Str::uuid();
                $ext = $file->getClientOriginalExtension();
                $ext = is_string($ext) && $ext !== '' ? strtolower($ext) : 'bin';
                $relativePath = 'sacraments/baptisms/'.$baptism->uuid.'/'.$type.'/'.$attachmentUuid.'.'.$ext;

                $stored = Storage::disk('local')->putFileAs(
                    dirname($relativePath),
                    $file,
                    basename($relativePath)
                );

                if (! $stored) {
                    throw new \RuntimeException('Failed to upload file.');
                }

                $storedPaths[$type] = $relativePath;
            }

            DB::transaction(function () use ($validated, $baptism, $familyId, $father, $mother, $user, $hasSponsorMember, $hasSponsorExisting, $storedPaths, $request): void {
                if (! $hasSponsorExisting) {
                    $memberId = null;
                    if ($hasSponsorMember) {
                        $memberId = (int) Member::query()->where('uuid', trim((string) ($validated['sponsor_member_uuid'] ?? '')))->value('id');
                        if (! $memberId) {
                            throw new \RuntimeException('Invalid sponsor member.');
                        }
                    }

                    BaptismSponsor::query()->create([
                        'uuid' => (string) Str::uuid(),
                        'baptism_id' => (int) $baptism->id,
                        'role' => $validated['sponsor_role'] ?: null,
                        'member_id' => $memberId,
                        'full_name' => $hasSponsorMember ? null : NormalizesNames::normalize($validated['sponsor_full_name'] ?: null, true),
                        'parish_name' => NormalizesNames::normalize($validated['sponsor_parish_name'] ?: null, true),
                        'phone' => $validated['sponsor_phone'] ?: null,
                        'email' => $validated['sponsor_email'] ?? null,
                    ]);
                }

                $bulk = [];

                foreach ($storedPaths as $type => $relativePath) {
                    $file = $request->file($type);
                    if (! $file) {
                        continue;
                    }

                    $sha256 = hash_file('sha256', Storage::disk('local')->path($relativePath));

                    $now = now();
                    $bulk[] = [
                        'uuid' => (string) Str::uuid(),
                        'parish_id' => (int) $baptism->parish_id,
                        'entity_type' => 'baptism',
                        'entity_id' => (int) $baptism->id,
                        'type' => $type,
                        'original_name' => (string) $file->getClientOriginalName(),
                        'mime_type' => 'application/pdf',
                        'size_bytes' => (int) $file->getSize(),
                        'storage_disk' => 'local',
                        'storage_path' => $relativePath,
                        'sha256' => $sha256,
                        'uploaded_by_user_id' => (int) $user->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($bulk)) {
                    SacramentAttachment::query()->insert($bulk);
                }

                $baptism->forceFill([
                    'family_id' => $familyId,
                    'father_member_id' => (int) $father->id,
                    'mother_member_id' => (int) $mother->id,
                    'status' => Baptism::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                    'submitted_by_user_id' => $user?->id,
                ])->save();
            });
        } catch (\RuntimeException $e) {
            foreach ($storedPaths as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('local')->delete($path);
                }
            }

            return back()->withErrors(['submit' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            foreach ($storedPaths as $path) {
                if (is_string($path) && $path !== '') {
                    Storage::disk('local')->delete($path);
                }
            }

            Log::error('Baptism submit failed', ['exception' => $e, 'baptism_uuid' => $baptism->uuid]);
            return back()->withErrors(['submit' => 'Unable to submit request. Please try again.'])->withInput();
        }

        return back()->with('success', 'Baptism request submitted.');
    }

    public function approve(Request $request, Baptism $baptism): RedirectResponse
    {
        if ($baptism->status !== Baptism::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted requests can be approved.');
        }

        $user = $request->user();

        $baptism->forceFill([
            'status' => Baptism::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by_user_id' => $user?->id,
            'rejected_at' => null,
            'rejected_by_user_id' => null,
            'rejection_reason' => null,
        ])->save();

        return back()->with('success', 'Baptism request approved.');
    }

    public function reject(Request $request, Baptism $baptism): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:5000'],
        ]);

        if (! in_array($baptism->status, [Baptism::STATUS_SUBMITTED, Baptism::STATUS_APPROVED], true)) {
            return back()->with('error', 'Only submitted/approved requests can be rejected.');
        }

        $user = $request->user();

        $baptism->forceFill([
            'status' => Baptism::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by_user_id' => $user?->id,
            'rejection_reason' => (string) $validated['reason'],
        ])->save();

        return back()->with('success', 'Baptism request rejected.');
    }

    public function schedule(Request $request, Baptism $baptism): RedirectResponse
    {
        if ($baptism->status !== Baptism::STATUS_APPROVED) {
            return back()->with('error', 'Only approved baptisms can be scheduled.');
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

        DB::transaction(function () use ($validated, $user, $baptism, $newDate): void {
            $locationParishId = null;
            if (! empty($validated['location_parish_uuid'])) {
                $locationParishId = (int) DB::table('parishes')->where('uuid', $validated['location_parish_uuid'])->value('id');
            }

            $schedule = SacramentSchedule::query()
                ->where('entity_type', 'baptism')
                ->where('entity_id', $baptism->getKey())
                ->orderByDesc('id')
                ->first();

            $status = ! empty($validated['status']) ? (string) $validated['status'] : ($schedule ? 'changed' : 'proposed');

            $oldDate = $schedule?->scheduled_for;

            $schedule = SacramentSchedule::create([
                'parish_id' => (int) $baptism->parish_id,
                'entity_type' => 'baptism',
                'entity_id' => $baptism->getKey(),
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

    public function complete(Request $request, Baptism $baptism): RedirectResponse
    {
        if ($baptism->status !== Baptism::STATUS_APPROVED) {
            return back()->with('error', 'Only approved baptisms can be marked as completed.');
        }

        $schedule = SacramentSchedule::query()
            ->where('entity_type', 'baptism')
            ->where('entity_id', $baptism->getKey())
            ->orderByDesc('id')
            ->first();

        if (! $schedule) {
            return back()->with('error', 'Schedule is required before marking as completed.');
        }

        $baptism->forceFill([
            'status' => Baptism::STATUS_COMPLETED,
            'completed_at' => now(),
            'baptism_date' => $baptism->baptism_date ?: $schedule->scheduled_for,
        ])->save();

        return back()->with('success', 'Baptism marked as completed.');
    }

    public function issue(Request $request, Baptism $baptism, CertificateService $certificates): RedirectResponse
    {
        if ($baptism->status !== Baptism::STATUS_COMPLETED) {
            return back()->with('error', 'Only completed baptisms can be issued.');
        }

        $issuance = $certificates->issueBaptismCertificate($baptism, $request->user());

        return back()->with('success', 'Certificate issued: '.$issuance->certificate_no);
    }

    public function certificate(Request $request, Baptism $baptism): Response|RedirectResponse
    {
        if ($baptism->status !== Baptism::STATUS_ISSUED || ! $baptism->certificate_no) {
            return back()->with('error', 'Certificate is not available for this baptism.');
        }

        $baptism->load([
            'parish',
            'originJumuiya.zone.parish',
            'family',
            'member:id,uuid,first_name,middle_name,last_name,phone,email',
            'fatherMember:id,uuid,first_name,middle_name,last_name,marital_status,phone,email',
            'motherMember:id,uuid,first_name,middle_name,last_name,marital_status,phone,email',
            'sponsors.member:id,uuid,first_name,middle_name,last_name,phone,email',
            'issuedBy',
        ]);

        $schedule = SacramentSchedule::query()
            ->where('entity_type', 'baptism')
            ->where('entity_id', $baptism->getKey())
            ->orderByDesc('id')
            ->first();

        return Inertia::render('Sacraments/Baptisms/Certificate', [
            'baptism' => new BaptismResource($baptism),
            'schedule' => $schedule ? [
                'scheduled_for' => $schedule->scheduled_for?->format('Y-m-d'),
                'location_text' => $schedule->location_text,
            ] : null,
        ]);
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
