<?php

namespace App\Http\Controllers\Zones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Zone\IndexZonesRequest;
use App\Http\Requests\Zone\StoreZonesRequest;
use App\Http\Requests\Zone\UpdateZoneRequest;
use App\Http\Resources\Structure\ZoneResource;
use App\Models\Structure\Parish;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ZoneController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function scopedZoneId(Request $request): ?int
    {
        if ($request->user()?->can('jumuiyas.view')) {
            return null;
        }

        $jumuiyaId = $request->user()?->member?->jumuiya_id;
        if (! $jumuiyaId) {
            return null;
        }

        return (int) Jumuiya::query()->where('id', $jumuiyaId)->value('zone_id') ?: null;
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Zone::class);

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $parish = Parish::query()->orderBy('id')->first();
        if (! $parish) {
            return response()->json(['data' => []]);
        }

        $scopedZoneId = $this->scopedZoneId($request);

        $safe = addcslashes($q, '%_\\');

        $zones = Zone::query()
            ->select(['uuid', 'name'])
            ->where('parish_id', $parish->id)
            ->when($scopedZoneId, fn (Builder $qb) => $qb->where('id', $scopedZoneId))
            ->when($q !== '', function (Builder $qb) use ($safe) {
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Zone $z) => ['uuid' => $z->uuid, 'name' => $z->name])
            ->values();

        return response()->json(['data' => $zones]);
    }

    public function index(IndexZonesRequest $request): Response
    {
        $this->authorize('viewAny', Zone::class);

        $validated = $request->validated();
        $q = $validated['q'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $parish = Parish::query()->orderBy('id')->first();

        if (! $parish) {
            return Inertia::render('Zones/Index', [
                'filters' => [
                    'q' => $q,
                    'per_page' => $perPage,
                ],
                'zones' => [
                    'data' => [],
                    'links' => [],
                    'meta' => [
                        'current_page' => 1,
                        'from' => null,
                        'last_page' => 1,
                        'path' => $request->url(),
                        'per_page' => $perPage,
                        'to' => null,
                        'total' => 0,
                    ],
                ],
            ]);
        }

        $zonesQuery = Zone::query()
            ->where('parish_id', $parish->id)
            ->when(is_string($q) && $q !== '', function (Builder $qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%');
                });
            })
            ->orderBy('name');

        $zones = $zonesQuery->paginate($perPage)->withQueryString();

        return Inertia::render('Zones/Index', [
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
            'zones' => ZoneResource::collection($zones),
        ]);
    }

    public function store(StoreZonesRequest $request): RedirectResponse
    {
        $this->authorize('create', Zone::class);

        $parish = Parish::query()->orderBy('id')->first();

        if (! $parish) {
            return back()->with('error', 'Parish is not configured yet. Please complete Setup first.');
        }

        try {
            $payload = $request->validated();
            $zones = $payload['zones'] ?? [];

            DB::transaction(function () use ($zones, $parish): void {
                foreach ($zones as $zoneInput) {
                    Zone::query()->create([
                        'parish_id' => $parish->id,
                        'name' => $zoneInput['name'],
                        'description' => $zoneInput['description'] ?? null,
                        'established_year' => $zoneInput['established_year'] ?? null,
                        'is_active' => true,
                    ]);
                }
            });

            return redirect()->route('zones.index')->with('success', count($zones) === 1 ? 'Zone saved.' : 'Zones saved.');
        } catch (\Throwable $e) {
            Log::error('Zone bulk store failed', ['exception' => $e]);

            return back()->with('error', 'Unable to save zones. Please try again.');
        }
    }

    public function update(UpdateZoneRequest $request, Zone $zone): RedirectResponse
    {
        $this->authorize('update', $zone);

        $parish = Parish::query()->orderBy('id')->first();
        if ($parish && $zone->parish_id !== $parish->id) {
            return back()->with('error', 'Invalid zone.');
        }

        try {
            $data = $request->validated();

            $zone->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'established_year' => $data['established_year'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ]);

            return back()->with('success', 'Zone updated.');
        } catch (\Throwable $e) {
            Log::error('Zone update failed', ['exception' => $e, 'zone_uuid' => $zone->uuid]);

            return back()->with('error', 'Unable to update zone. Please try again.');
        }
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        $this->authorize('delete', $zone);

        $parish = Parish::query()->orderBy('id')->first();
        if ($parish && $zone->parish_id !== $parish->id) {
            return back()->with('error', 'Invalid zone.');
        }

        if ($zone->jumuiyas()->exists()) {
            return back()->with('error', 'Unable to delete. This zone has Jumuiya records.');
        }

        try {
            $zone->delete();

            return back()->with('success', 'Zone deleted.');
        } catch (\Throwable $e) {
            Log::error('Zone delete failed', ['exception' => $e, 'zone_uuid' => $zone->uuid]);

            return back()->with('error', 'Unable to delete zone. Please try again.');
        }
    }
}
