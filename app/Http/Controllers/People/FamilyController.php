<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\IndexFamiliesRequest;
use App\Http\Requests\Family\StoreFamilyRequest;
use App\Http\Requests\Family\UpdateFamilyRequest;
use App\Http\Resources\People\FamilyResource;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\People\Family;
use App\Models\Structure\Jumuiya;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Family::class);

        $q = $request->query('q');
        $jumuiyaUuid = $request->query('jumuiya_uuid');

        $q = is_string($q) ? trim($q) : '';

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        $selectedJumuiyaId = null;
        if (is_string($jumuiyaUuid) && $jumuiyaUuid !== '') {
            $selectedJumuiyaId = Jumuiya::query()->where('uuid', $jumuiyaUuid)->value('id');
        }

        if ($scopedJumuiyaId && $selectedJumuiyaId && $selectedJumuiyaId !== $scopedJumuiyaId) {
            $selectedJumuiyaId = null;
        }

        $safe = addcslashes($q, '%_\\');

        $families = Family::query()
            ->select(['uuid', 'family_name', 'family_code'])
            ->when($scopedJumuiyaId, fn (Builder $qb) => $qb->where('jumuiya_id', $scopedJumuiyaId))
            ->when($selectedJumuiyaId, fn (Builder $qb) => $qb->where('jumuiya_id', $selectedJumuiyaId))
            ->when($q !== '', function (Builder $qb) use ($safe) {
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('family_name', 'like', $safe.'%')
                        ->orWhere('family_code', 'like', $safe.'%');
                });
            })
            ->orderBy('family_name')
            ->limit(20)
            ->get()
            ->map(function (Family $f) {
                return [
                    'uuid' => $f->uuid,
                    'name' => $f->family_name,
                    'code' => $f->family_code,
                ];
            })
            ->values();

        return response()->json(['data' => $families]);
    }

    protected function scopedJumuiyaId(Request $request): ?int
    {
        if ($request->user()?->can('jumuiyas.view')) {
            return null;
        }

        return $request->user()?->member?->jumuiya_id;
    }

    public function index(IndexFamiliesRequest $request): Response
    {
        $this->authorize('viewAny', Family::class);

        $validated = $request->validated();
        $q = $validated['q'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);
        $jumuiyaUuid = $validated['jumuiya_uuid'] ?? null;

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);

        $jumuiyas = Jumuiya::query()
            ->when($scopedJumuiyaId, fn (Builder $qb) => $qb->where('id', $scopedJumuiyaId))
            ->orderBy('name')
            ->get(['uuid', 'name']);

        $selectedJumuiya = null;
        if (is_string($jumuiyaUuid) && $jumuiyaUuid !== '') {
            $selectedJumuiya = Jumuiya::query()->where('uuid', $jumuiyaUuid)->first();
        }

        if ($scopedJumuiyaId && $selectedJumuiya && $selectedJumuiya->id !== $scopedJumuiyaId) {
            $selectedJumuiya = null;
        }

        $familiesQuery = Family::query()
            ->with([
                'jumuiya:id,uuid,name,zone_id',
                'jumuiya.zone:id,uuid,name',
                'headOfFamily:id,uuid,first_name,middle_name,last_name',
            ])
            ->when($scopedJumuiyaId, fn (Builder $qb) => $qb->where('jumuiya_id', $scopedJumuiyaId))
            ->when($selectedJumuiya, fn (Builder $qb) => $qb->where('jumuiya_id', $selectedJumuiya->id))
            ->when(is_string($q) && $q !== '', function (Builder $qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('family_name', 'like', $safe.'%')
                        ->orWhere('family_code', 'like', $safe.'%');
                });
            })
            ->orderBy('family_name');

        $families = $familiesQuery->paginate($perPage)->withQueryString();

        return Inertia::render('Families/Index', [
            'filters' => [
                'q' => $q,
                'jumuiya_uuid' => $jumuiyaUuid,
                'per_page' => $perPage,
            ],
            'jumuiyas' => $jumuiyas,
            'families' => FamilyResource::collection($families),
        ]);
    }

    public function store(StoreFamilyRequest $request): RedirectResponse
    {
        $this->authorize('create', Family::class);

        $validated = $request->validated();

        $jumuiya = Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->firstOrFail();

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && $jumuiya->id !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid Christian Community.');
        }

        try {
            Family::query()->create([
                'jumuiya_id' => $jumuiya->id,
                'family_name' => trim($validated['family_name']),
                'family_code' => $validated['family_code'] ?? null,
                'house_number' => $validated['house_number'] ?? null,
                'street' => $validated['street'] ?? null,
                'head_of_family_member_id' => null,
                'is_active' => true,
            ]);

            return back()->with('success', 'Family saved.');
        } catch (\Throwable $e) {
            Log::error('Family store failed', ['exception' => $e]);

            return back()->with('error', 'Unable to save family. Please try again.');
        }
    }

    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $this->authorize('update', $family);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && $family->jumuiya_id !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid family.');
        }

        $validated = $request->validated();

        $targetJumuiyaId = $family->jumuiya_id;
        if (! empty($validated['jumuiya_uuid'])) {
            $targetJumuiyaId = (int) Jumuiya::query()->where('uuid', $validated['jumuiya_uuid'])->value('id');
            if (! $targetJumuiyaId) {
                return back()->with('error', 'Invalid Christian Community.');
            }
        }

        if ($scopedJumuiyaId && $targetJumuiyaId !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid Christian Community.');
        }

        try {
            $jumuiyaChanged = $targetJumuiyaId !== (int) $family->jumuiya_id;

            $family->update([
                'jumuiya_id' => $targetJumuiyaId,
                'family_name' => trim($validated['family_name']),
                'family_code' => $validated['family_code'] ?? null,
                'house_number' => $validated['house_number'] ?? null,
                'street' => $validated['street'] ?? null,
                'head_of_family_member_id' => $jumuiyaChanged ? null : $family->head_of_family_member_id,
                'is_active' => (bool) $validated['is_active'],
            ]);

            return back()->with('success', 'Family updated.');
        } catch (\Throwable $e) {
            Log::error('Family update failed', ['exception' => $e, 'family_uuid' => $family->uuid]);

            return back()->with('error', 'Unable to update family. Please try again.');
        }
    }

    public function destroy(Request $request, Family $family): RedirectResponse
    {
        $this->authorize('delete', $family);

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId && $family->jumuiya_id !== $scopedJumuiyaId) {
            return back()->with('error', 'Invalid family.');
        }

        if ($family->members()->exists()) {
            return back()->with('error', 'Unable to delete. This family has members.');
        }

        try {
            $family->delete();

            return back()->with('success', 'Family deleted.');
        } catch (\Throwable $e) {
            Log::error('Family delete failed', ['exception' => $e, 'family_uuid' => $family->uuid]);

            return back()->with('error', 'Unable to delete family. Please try again.');
        }
    }
}
