<?php

namespace App\Http\Controllers\Pastoral;

use App\Http\Controllers\Concerns\ResolvesSingleParishContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pastoral\IndexServiceRequestsRequest;
use App\Http\Requests\Pastoral\StoreServiceRequestRequest;
use App\Http\Requests\Pastoral\UpdateServiceRequestRequest;
use App\Http\Resources\Pastoral\PastoralServiceCategoryResource;
use App\Http\Resources\Pastoral\PastoralServiceRequestResource;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\Pastoral\PastoralServiceCategory;
use App\Models\Pastoral\PastoralServiceRequest;
use App\Models\Pastoral\PastoralServiceRequestFamily;
use App\Models\Pastoral\PastoralServiceRequestItem;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Models\User;
use App\Services\Pastoral\PastoralServiceRequestEventService;
use App\Traits\NormalizesNames;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    use ResolvesSingleParishContext;

    public function __construct(private readonly PastoralServiceRequestEventService $eventService)
    {
        $this->middleware('auth');
    }

    public function index(IndexServiceRequestsRequest $request): Response
    {
        $this->authorize('viewAny', PastoralServiceRequest::class);

        $validated = $request->validated();

        $parishId = $this->resolveCurrentParishId($request->user());

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $status = is_string($validated['status'] ?? null) ? trim((string) $validated['status']) : 'all';
        $dateFilter = is_string($validated['date_filter'] ?? null) ? trim((string) $validated['date_filter']) : 'all';
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);

        $jumuiyaId = null;
        if (is_string($validated['jumuiya_uuid'] ?? null) && trim((string) $validated['jumuiya_uuid']) !== '') {
            $jumuiyaId = (int) Jumuiya::query()
                ->where('uuid', trim((string) $validated['jumuiya_uuid']))
                ->value('id');
        }

        $categoryId = null;
        if (is_string($validated['category_uuid'] ?? null) && trim((string) $validated['category_uuid']) !== '') {
            $categoryId = (int) PastoralServiceCategory::query()
                ->where('parish_id', $parishId)
                ->where('uuid', trim((string) $validated['category_uuid']))
                ->value('id');
        }

        $namePrefix = $this->normalizedPrefixLike($q);
        $userId = (int) ($request->user()?->id ?? 0);
        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        $canSelectAnyJumuiya = $this->canSelectAnyJumuiya($request);

        $requests = PastoralServiceRequest::query()
            ->select('pastoral_service_requests.*')
            ->where('parish_id', $parishId)
            ->when(! $canSelectAnyJumuiya && $scopedJumuiyaId > 0, fn (Builder $qb) => $qb->where('jumuiya_id', $scopedJumuiyaId))
            ->when($userId > 0, function (Builder $qb) use ($userId) {
                $qb->where(function (Builder $visibilityQ) use ($userId) {
                    $visibilityQ
                        ->where('status', '!=', PastoralServiceRequest::STATUS_DRAFT)
                        ->orWhere('created_by_user_id', $userId);
                });
            })
            ->with([
                'jumuiya:id,uuid,name',
                'requestedByMember:id,uuid,first_name,middle_name,last_name',
                'assignedToUser:id,uuid,name',
            ])
            ->withCount('families')
            ->selectSub(
                PastoralServiceRequestItem::query()
                    ->selectRaw('count(*)')
                    ->join('pastoral_service_request_families as psrf', 'psrf.id', '=', 'pastoral_service_request_items.pastoral_service_request_family_id')
                    ->whereColumn('psrf.pastoral_service_request_id', 'pastoral_service_requests.id'),
                'items_count'
            )
            ->when($status !== 'all', fn (Builder $qb) => $qb->where('status', $status))
            ->when($jumuiyaId > 0, fn (Builder $qb) => $qb->where('jumuiya_id', $jumuiyaId))
            ->when($categoryId > 0, function (Builder $qb) use ($categoryId) {
                $qb->whereHas('families.items', fn (Builder $itemQ) => $itemQ->where('pastoral_service_category_id', $categoryId));
            })
            ->when($namePrefix, function (Builder $qb) use ($namePrefix) {
                $qb->whereHas('families.family', fn (Builder $fq) => $fq->where('family_name', 'like', $namePrefix));
            });

        $this->applyDateFilter($requests, $dateFilter, $dateFrom, $dateTo);

        $requests = $requests
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $categories = PastoralServiceCategory::query()
            ->where('parish_id', $parishId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_normalized')
            ->get();

        $scheduleUsers = User::query()
            ->where('parish_id', $parishId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(200)
            ->get(['uuid', 'name']);

        return Inertia::render('Pastoral/ServiceRequests/Index', [
            'filters' => [
                'q' => $q !== '' ? $q : null,
                'status' => $status,
                'jumuiya_uuid' => $validated['jumuiya_uuid'] ?? null,
                'category_uuid' => $validated['category_uuid'] ?? null,
                'date_filter' => $dateFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
            'requests' => PastoralServiceRequestResource::collection($requests),
            'categories' => PastoralServiceCategoryResource::collection($categories),
            'schedule_users' => $scheduleUsers,
            'can' => [
                'create' => $request->user()?->can('service-requests.create') ?? false,
                'update' => $request->user()?->can('service-requests.update') ?? false,
                'delete' => $request->user()?->can('service-requests.delete') ?? false,
                'submit' => $request->user()?->can('service-requests.submit') ?? false,
                'schedule' => $request->user()?->can('service-requests.schedule') ?? false,
                'progress' => $request->user()?->can('service-requests.progress') ?? false,
                'complete' => $request->user()?->can('service-requests.complete') ?? false,
                'cancel' => $request->user()?->can('service-requests.cancel') ?? false,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', PastoralServiceRequest::class);

        $parishId = $this->resolveCurrentParishId($request->user());

        $categories = PastoralServiceCategory::query()
            ->where('parish_id', $parishId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_normalized')
            ->get();

        $defaultJumuiyaUuid = null;
        $defaultJumuiyaName = null;
        if (! $this->canSelectAnyJumuiya($request)) {
            $scopedJumuiyaId = $this->scopedJumuiyaId($request);

            if ($scopedJumuiyaId) {
                $scoped = Jumuiya::query()
                    ->select(['jumuiyas.uuid', 'jumuiyas.name'])
                    ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                    ->where('zones.parish_id', $parishId)
                    ->where('jumuiyas.id', $scopedJumuiyaId)
                    ->first();

                if ($scoped) {
                    $defaultJumuiyaUuid = $scoped->uuid;
                    $defaultJumuiyaName = $scoped->name;
                }
            }
        }

        return Inertia::render('Pastoral/ServiceRequests/Create', [
            'categories' => PastoralServiceCategoryResource::collection($categories),
            'defaults' => [
                'request_date' => now()->toDateString(),
                'urgency' => 'normal',
                'jumuiya_uuid' => $defaultJumuiyaUuid,
                'jumuiya_name' => $defaultJumuiyaName,
            ],
        ]);
    }

    public function show(Request $request, PastoralServiceRequest $serviceRequest): Response
    {
        $record = PastoralServiceRequest::query()
            ->where('uuid', $serviceRequest->uuid)
            ->with([
                'jumuiya:id,uuid,name',
                'requestedByMember:id,uuid,first_name,middle_name,last_name',
                'assignedToUser:id,uuid,name',
                'families.family:id,uuid,family_name',
                'families.items.category:id,uuid,name',
                'families.items.targetMember:id,uuid,first_name,middle_name,last_name',
                'events' => fn ($qb) => $qb->orderByDesc('performed_at')->limit(100),
                'events.performedByUser:id,name',
            ])
            ->firstOrFail();

        abort_unless((int) $record->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        abort_unless($this->canAccessRecord($request, $record), 404);
        $this->authorize('view', $record);

        return Inertia::render('Pastoral/ServiceRequests/Show', [
            'record' => (new PastoralServiceRequestResource($record))->resolve(),
            'events' => $record->events->map(fn ($event) => [
                'uuid' => $event->uuid,
                'action' => $event->action,
                'old_status' => $event->old_status,
                'new_status' => $event->new_status,
                'notes' => $event->notes,
                'performed_at' => optional($event->performed_at)?->format('Y-m-d H:i:s'),
                'performed_by' => $event->performedByUser?->name,
            ])->values(),
        ]);
    }

    public function store(StoreServiceRequestRequest $request): RedirectResponse
    {
        $this->authorize('create', PastoralServiceRequest::class);

        $validated = $request->validated();
        $parishId = $this->resolveCurrentParishId($request->user());

        $jumuiya = Jumuiya::query()
            ->where('uuid', $validated['jumuiya_uuid'])
            ->whereExists(function ($sub) use ($parishId) {
                $sub->selectRaw('1')
                    ->from('zones')
                    ->whereColumn('zones.id', 'jumuiyas.zone_id')
                    ->where('zones.parish_id', $parishId);
            })
            ->first();

        if (! $jumuiya) {
            return back()->with('error', 'Invalid Christian Community selected.');
        }

        if (! $this->canCreateForJumuiya($request, (int) $jumuiya->id)) {
            return back()->with('error', $this->creationScopeErrorMessage($request));
        }

        if ($this->hasDuplicateFamilyUuids($validated['families'] ?? [])) {
            return back()->with('error', 'Each family can appear only once per request.');
        }

        try {
            DB::transaction(function () use ($request, $validated, $parishId, $jumuiya): void {
                $record = PastoralServiceRequest::query()->create([
                    'parish_id' => $parishId,
                    'jumuiya_id' => (int) $jumuiya->id,
                    'requested_by_member_id' => $request->user()?->member_id,
                    'request_date' => $validated['request_date'],
                    'preferred_service_date' => $validated['preferred_service_date'] ?? null,
                    'urgency' => $validated['urgency'] ?? 'normal',
                    'notes' => $validated['notes'] ?? null,
                    'status' => PastoralServiceRequest::STATUS_DRAFT,
                    'created_by_user_id' => $request->user()?->id,
                    'updated_by_user_id' => $request->user()?->id,
                ]);

                $this->syncRequestFamiliesAndItems((int) $record->id, $validated['families'] ?? [], $parishId, (int) $jumuiya->id);

                $this->eventService->record(
                    $request,
                    $parishId,
                    (int) $record->id,
                    'created',
                    null,
                    PastoralServiceRequest::STATUS_DRAFT
                );
            });

            return redirect()->route('pastoral.service-requests.index')->with('success', 'Service request saved successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to store service request', ['exception' => $e]);

            return back()->with('error', 'Unable to save service request. Please try again.');
        }
    }

    public function update(UpdateServiceRequestRequest $request, PastoralServiceRequest $serviceRequest): RedirectResponse
    {
        $record = PastoralServiceRequest::query()
            ->where('uuid', $serviceRequest->uuid)
            ->firstOrFail();

        abort_unless((int) $record->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('update', $record);

        $validated = $request->validated();

        if ($this->hasDuplicateFamilyUuids($validated['families'] ?? [])) {
            return back()->with('error', 'Each family can appear only once per request.');
        }

        try {
            DB::transaction(function () use ($request, $record, $validated): void {
                $record->update([
                    'preferred_service_date' => $validated['preferred_service_date'] ?? null,
                    'urgency' => $validated['urgency'] ?? $record->urgency,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by_user_id' => $request->user()?->id,
                ]);

                $this->syncRequestFamiliesAndItems(
                    (int) $record->id,
                    $validated['families'] ?? [],
                    (int) $record->parish_id,
                    (int) $record->jumuiya_id,
                    true
                );

                $this->eventService->record(
                    $request,
                    (int) $record->parish_id,
                    (int) $record->id,
                    'updated',
                    $record->status,
                    $record->status
                );
            });

            return back()->with('success', 'Service request updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to update service request', ['exception' => $e, 'uuid' => $record->uuid]);

            return back()->with('error', 'Unable to update service request. Please try again.');
        }
    }

    public function submit(Request $request, PastoralServiceRequest $serviceRequest): RedirectResponse
    {
        return $this->transitionStatus($request, $serviceRequest, 'service-requests.submit', PastoralServiceRequest::STATUS_SUBMITTED, 'submitted');
    }

    public function progress(Request $request, PastoralServiceRequest $serviceRequest): RedirectResponse
    {
        return $this->transitionStatus($request, $serviceRequest, 'service-requests.progress', PastoralServiceRequest::STATUS_IN_PROGRESS, 'in_progress');
    }

    public function schedule(Request $request, PastoralServiceRequest $serviceRequest): RedirectResponse
    {
        $record = PastoralServiceRequest::query()
            ->where('uuid', $serviceRequest->uuid)
            ->firstOrFail();

        abort_unless((int) $record->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('schedule', $record);

        if (! in_array((string) $record->status, [
            PastoralServiceRequest::STATUS_SUBMITTED,
            PastoralServiceRequest::STATUS_IN_PROGRESS,
        ], true)) {
            return back()->with('error', 'Only submitted or in-progress requests can be scheduled.');
        }

        $validated = $request->validate([
            'scheduled_service_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'assigned_to_user_uuid' => ['required', 'uuid'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignedUserId = (int) User::query()
            ->where('uuid', $validated['assigned_to_user_uuid'])
            ->where('parish_id', (int) $record->parish_id)
            ->where('is_active', true)
            ->value('id');

        if ($assignedUserId <= 0) {
            return back()->with('error', 'Assigned user must be an active user in this parish.');
        }

        $scheduledDate = trim((string) $validated['scheduled_service_date']);
        $requestDate = optional($record->request_date)?->format('Y-m-d');
        if ($requestDate !== null && $scheduledDate < $requestDate) {
            return back()->with('error', 'Scheduled date cannot be earlier than request date.');
        }

        try {
            DB::transaction(function () use ($request, $record, $validated, $assignedUserId): void {
                $record->update([
                    'scheduled_service_date' => $validated['scheduled_service_date'],
                    'assigned_to_user_id' => $assignedUserId,
                    'updated_by_user_id' => $request->user()?->id,
                ]);

                $this->eventService->record(
                    $request,
                    (int) $record->parish_id,
                    (int) $record->id,
                    'scheduled',
                    $record->status,
                    $record->status,
                    $validated['notes'] ?? null
                );
            });

            return back()->with('success', 'Service request scheduled successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to schedule service request', [
                'exception' => $e,
                'uuid' => $record->uuid,
            ]);

            return back()->with('error', 'Unable to schedule service request. Please try again.');
        }
    }

    public function complete(Request $request, PastoralServiceRequest $serviceRequest): RedirectResponse
    {
        return $this->transitionStatus($request, $serviceRequest, 'service-requests.complete', PastoralServiceRequest::STATUS_COMPLETED, 'completed');
    }

    public function cancel(Request $request, PastoralServiceRequest $serviceRequest): RedirectResponse
    {
        $validated = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:2000'],
        ]);

        return $this->transitionStatus(
            $request,
            $serviceRequest,
            'service-requests.cancel',
            PastoralServiceRequest::STATUS_CANCELLED,
            'cancelled',
            $validated['cancel_reason']
        );
    }

    public function destroy(Request $request, PastoralServiceRequest $serviceRequest): RedirectResponse
    {
        if (! ($request->user()?->can('service-requests.delete') ?? false)) {
            return back()->with('error', 'You do not have permission to delete service requests.');
        }

        $record = PastoralServiceRequest::query()->where('uuid', $serviceRequest->uuid)->firstOrFail();
        abort_unless((int) $record->parish_id === $this->resolveCurrentParishId($request->user()), 404);

        try {
            $record->delete();

            return back()->with('success', 'Service request deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to delete service request', ['exception' => $e, 'uuid' => $record->uuid]);

            return back()->with('error', 'Unable to delete service request. Please try again.');
        }
    }

    private function canCreateForJumuiya(Request $request, int $jumuiyaId): bool
    {
        if ($this->canSelectAnyJumuiya($request)) {
            return true;
        }

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        return $scopedJumuiyaId > 0 && $scopedJumuiyaId === $jumuiyaId;
    }

    private function canSelectAnyJumuiya(Request $request): bool
    {
        $user = $request->user();

        return (bool) ($user?->can('jumuiyas.view') || $user?->can('service-requests.view-all'));
    }

    private function scopedJumuiyaId(Request $request): ?int
    {
        if ($this->canSelectAnyJumuiya($request)) {
            return null;
        }

        $userMemberId = (int) ($request->user()?->member_id ?? $request->user()?->member?->id ?? 0);
        if ($userMemberId > 0) {
            $today = now()->toDateString();

            $leaderJumuiyaId = JumuiyaLeadership::query()
                ->where('member_id', $userMemberId)
                ->where('is_active', true)
                ->whereDate('start_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                })
                ->orderByDesc('start_date')
                ->value('jumuiya_id');

            if ($leaderJumuiyaId) {
                return (int) $leaderJumuiyaId;
            }
        }

        return $request->user()?->member?->jumuiya_id
            ? (int) $request->user()->member->jumuiya_id
            : null;
    }

    private function creationScopeErrorMessage(Request $request): string
    {
        if ($this->scopedJumuiyaId($request)) {
            return 'You can only create service requests for your assigned Christian Community.';
        }

        return 'You do not have a Christian Community assignment for creating service requests.';
    }

    private function hasDuplicateFamilyUuids(array $familiesInput): bool
    {
        $uuids = collect($familiesInput)
            ->map(fn ($family) => is_array($family) ? trim((string) ($family['family_uuid'] ?? '')) : '')
            ->filter()
            ->values();

        return $uuids->count() !== $uuids->unique()->count();
    }

    private function syncRequestFamiliesAndItems(
        int $requestId,
        array $familiesInput,
        int $parishId,
        int $jumuiyaId,
        bool $replaceAll = false
    ): void {
        if ($replaceAll) {
            PastoralServiceRequestFamily::query()
                ->where('pastoral_service_request_id', $requestId)
                ->each(function (PastoralServiceRequestFamily $family): void {
                    $family->items()->delete();
                    $family->delete();
                });
        }

        foreach ($familiesInput as $familyInput) {
            $familyUuid = trim((string) ($familyInput['family_uuid'] ?? ''));
            if ($familyUuid === '') {
                continue;
            }

            $family = Family::query()
                ->where('uuid', $familyUuid)
                ->where('jumuiya_id', $jumuiyaId)
                ->first();

            if (! $family) {
                continue;
            }

            $requestFamily = PastoralServiceRequestFamily::query()->create([
                'pastoral_service_request_id' => $requestId,
                'family_id' => (int) $family->id,
                'family_notes' => $familyInput['family_notes'] ?? null,
            ]);

            foreach (($familyInput['items'] ?? []) as $itemInput) {
                $categoryUuid = trim((string) ($itemInput['service_category_uuid'] ?? ''));
                if ($categoryUuid === '') {
                    continue;
                }

                $category = PastoralServiceCategory::query()
                    ->where('parish_id', $parishId)
                    ->where('uuid', $categoryUuid)
                    ->where('is_active', true)
                    ->first();

                if (! $category) {
                    continue;
                }

                $targetMemberId = null;
                if (is_string($itemInput['target_member_uuid'] ?? null) && trim((string) $itemInput['target_member_uuid']) !== '') {
                    $targetMemberId = (int) Member::query()
                        ->where('uuid', trim((string) $itemInput['target_member_uuid']))
                        ->where('family_id', (int) $family->id)
                        ->value('id');
                }

                PastoralServiceRequestItem::query()->create([
                    'pastoral_service_request_family_id' => (int) $requestFamily->id,
                    'pastoral_service_category_id' => (int) $category->id,
                    'target_member_id' => $targetMemberId > 0 ? $targetMemberId : null,
                    'description' => $itemInput['description'] ?? null,
                    'requested_for_date' => $itemInput['requested_for_date'] ?? null,
                    'status' => $itemInput['status'] ?? 'pending',
                ]);
            }
        }
    }

    private function transitionStatus(
        Request $request,
        PastoralServiceRequest $serviceRequest,
        string $permission,
        string $toStatus,
        string $action,
        ?string $notes = null
    ): RedirectResponse {
        if (! ($request->user()?->can($permission) ?? false)) {
            return back()->with('error', 'You do not have permission for this action.');
        }

        $record = PastoralServiceRequest::query()
            ->where('uuid', $serviceRequest->uuid)
            ->firstOrFail();

        abort_unless((int) $record->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        abort_unless($this->canAccessRecord($request, $record), 404);

        $fromStatus = (string) $record->status;

        if ($fromStatus === $toStatus) {
            return back()->with('success', 'Service request already in target status.');
        }

        if ($toStatus === PastoralServiceRequest::STATUS_SUBMITTED && $fromStatus !== PastoralServiceRequest::STATUS_DRAFT) {
            return back()->with('error', 'Only draft requests can be submitted.');
        }

        if ($toStatus === PastoralServiceRequest::STATUS_IN_PROGRESS && $fromStatus !== PastoralServiceRequest::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted requests can be started.');
        }

        if ($toStatus === PastoralServiceRequest::STATUS_COMPLETED && $fromStatus !== PastoralServiceRequest::STATUS_IN_PROGRESS) {
            return back()->with('error', 'Only in-progress requests can be completed.');
        }

        $now = now();

        try {
            DB::transaction(function () use ($request, $record, $toStatus, $action, $fromStatus, $now, $notes): void {
                $payload = [
                    'status' => $toStatus,
                    'updated_by_user_id' => $request->user()?->id,
                ];

                if ($toStatus === PastoralServiceRequest::STATUS_SUBMITTED) {
                    $payload['submitted_at'] = $now;
                }
                if ($toStatus === PastoralServiceRequest::STATUS_IN_PROGRESS) {
                    $payload['in_progress_at'] = $now;
                }
                if ($toStatus === PastoralServiceRequest::STATUS_COMPLETED) {
                    $payload['completed_at'] = $now;
                }
                if ($toStatus === PastoralServiceRequest::STATUS_CANCELLED) {
                    $payload['cancelled_at'] = $now;
                    $payload['cancel_reason'] = $notes;
                }

                $record->update($payload);

                $this->eventService->record(
                    $request,
                    (int) $record->parish_id,
                    (int) $record->id,
                    $action,
                    $fromStatus,
                    $toStatus,
                    $notes
                );
            });

            return back()->with('success', 'Service request status updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to transition service request status', [
                'exception' => $e,
                'uuid' => $record->uuid,
                'from' => $fromStatus,
                'to' => $toStatus,
            ]);

            return back()->with('error', 'Unable to update status. Please try again.');
        }
    }

    private function canAccessRecord(Request $request, PastoralServiceRequest $record): bool
    {
        $userId = (int) ($request->user()?->id ?? 0);
        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        if ($scopedJumuiyaId > 0 && (int) $record->jumuiya_id !== $scopedJumuiyaId) {
            return false;
        }

        if ((string) $record->status === PastoralServiceRequest::STATUS_DRAFT) {
            return $userId > 0 && (int) $record->created_by_user_id === $userId;
        }

        return true;
    }

    private function normalizedPrefixLike(?string $value): ?string
    {
        $normalized = NormalizesNames::normalize(is_string($value) ? $value : null);
        $normalized = $normalized !== null ? mb_strtolower($normalized, 'UTF-8') : '';
        if ($normalized === '') {
            return null;
        }

        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $normalized).'%';
    }

    private function applyDateFilter(Builder $query, string $filter, mixed $dateFrom, mixed $dateTo): void
    {
        $today = now()->toDateString();

        if ($filter === 'today') {
            $query->where('request_date', '=', $today);
            return;
        }

        if ($filter === 'this_week') {
            $query->whereBetween('request_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
            return;
        }

        if ($filter === 'this_month') {
            $query->whereBetween('request_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
            return;
        }

        if ($filter === 'custom') {
            if (is_string($dateFrom) && trim($dateFrom) !== '') {
                $query->where('request_date', '>=', trim($dateFrom));
            }
            if (is_string($dateTo) && trim($dateTo) !== '') {
                $query->where('request_date', '<=', trim($dateTo));
            }
        }
    }
}
