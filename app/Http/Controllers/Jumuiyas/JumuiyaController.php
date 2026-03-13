<?php

namespace App\Http\Controllers\Jumuiyas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Jumuiya\IndexJumuiyasRequest;
use App\Http\Requests\Jumuiya\StoreJumuiyasRequest;
use App\Http\Requests\Jumuiya\UpdateJumuiyaRequest;
use App\Http\Resources\Structure\JumuiyaResource;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use App\Models\Structure\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class JumuiyaController extends Controller
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

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Jumuiya::class);

        $q = $request->query('q');
        $zoneUuid = $request->query('zone_uuid');

        $q = is_string($q) ? trim($q) : '';

        $parish = Parish::query()->orderBy('id')->first();
        if (! $parish) {
            return response()->json(['data' => []]);
        }

        $scopedJumuiyaId = $this->scopedJumuiyaId($request);
        if ($scopedJumuiyaId) {
            $jumuiya = Jumuiya::query()->where('id', $scopedJumuiyaId)->first();
            if (! $jumuiya) {
                return response()->json(['data' => []]);
            }

            return response()->json([
                'data' => [[
                    'uuid' => $jumuiya->uuid,
                    'name' => $jumuiya->name,
                ]],
            ]);
        }

        $zoneIds = Zone::query()->where('parish_id', $parish->id)->pluck('id');

        $selectedZoneId = null;
        if (is_string($zoneUuid) && $zoneUuid !== '') {
            $selectedZoneId = (int) Zone::query()
                ->where('parish_id', $parish->id)
                ->where('uuid', $zoneUuid)
                ->value('id');
        }

        $safe = addcslashes($q, '%_\\');

        $jumuiyas = Jumuiya::query()
            ->select(['uuid', 'name'])
            ->whereIn('zone_id', $zoneIds)
            ->when($selectedZoneId, fn (Builder $qb) => $qb->where('zone_id', $selectedZoneId))
            ->when($q !== '', function (Builder $qb) use ($safe) {
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Jumuiya $j) => ['uuid' => $j->uuid, 'name' => $j->name])
            ->values();

        return response()->json(['data' => $jumuiyas]);
    }

    public function index(IndexJumuiyasRequest $request): Response
    {
        $this->authorize('viewAny', Jumuiya::class);

        $validated = $request->validated();
        $q = $validated['q'] ?? null;
        $zoneUuid = $validated['zone_uuid'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $parish = Parish::query()->orderBy('id')->first();

        $zones = [];
        if ($parish) {
            $zones = Zone::query()
                ->where('parish_id', $parish->id)
                ->orderBy('name')
                ->get(['uuid', 'name']);
        }

        if (! $parish) {
            return Inertia::render('Jumuiyas/Index', [
                'filters' => [
                    'q' => $q,
                    'zone_uuid' => $zoneUuid,
                    'per_page' => $perPage,
                ],
                'zones' => $zones,
                'jumuiyas' => [
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

        $zoneIds = Zone::query()->where('parish_id', $parish->id)->pluck('id');

        $selectedZone = null;
        if (is_string($zoneUuid) && $zoneUuid !== '') {
            $selectedZone = Zone::query()
                ->where('parish_id', $parish->id)
                ->where('uuid', $zoneUuid)
                ->first();
        }

        $jumuiyasQuery = Jumuiya::query()
            ->with(['zone:id,uuid,name'])
            ->whereIn('zone_id', $zoneIds)
            ->when($selectedZone, fn (Builder $qb) => $qb->where('zone_id', $selectedZone->id))
            ->when(is_string($q) && $q !== '', function (Builder $qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%');
                });
            })
            ->orderBy('name');

        $jumuiyas = $jumuiyasQuery->paginate($perPage)->withQueryString();

        return Inertia::render('Jumuiyas/Index', [
            'filters' => [
                'q' => $q,
                'zone_uuid' => $zoneUuid,
                'per_page' => $perPage,
            ],
            'zones' => $zones,
            'jumuiyas' => JumuiyaResource::collection($jumuiyas),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Jumuiya::class);

        $parish = Parish::query()->orderBy('id')->first();

        $zones = [];
        if ($parish) {
            $zones = Zone::query()
                ->where('parish_id', $parish->id)
                ->orderBy('name')
                ->get(['uuid', 'name']);
        }

        return Inertia::render('Jumuiyas/Create', [
            'zones' => $zones,
        ]);
    }

    public function store(StoreJumuiyasRequest $request): RedirectResponse
    {
        $this->authorize('create', Jumuiya::class);

        $parish = Parish::query()->orderBy('id')->first();
        if (! $parish) {
            return back()->with('error', 'Parish is not configured yet. Please complete Setup first.');
        }

        try {
            $payload = $request->validated();
            $zoneUuid = $payload['zone_uuid'];
            $rows = $payload['jumuiyas'] ?? [];

            $zone = Zone::query()
                ->where('parish_id', $parish->id)
                ->where('uuid', $zoneUuid)
                ->first();

            if (! $zone) {
                return back()->with('error', 'Invalid zone.');
            }

            DB::transaction(function () use ($rows, $zone): void {
                foreach ($rows as $row) {
                    Jumuiya::query()->create([
                        'zone_id' => $zone->id,
                        'name' => $row['name'],
                        'meeting_day' => $row['meeting_day'] ?? null,
                        'established_year' => $row['established_year'] ?? null,
                        'is_active' => true,
                    ]);
                }
            });

            return redirect()->route('jumuiyas.index')->with('success', count($rows) === 1 ? 'Christian Community saved.' : 'Christian Communities saved.');
        } catch (\Throwable $e) {
            Log::error('Jumuiya bulk store failed', ['exception' => $e]);

            return back()->with('error', 'Unable to save Christian Communities. Please try again.');
        }
    }

    public function update(UpdateJumuiyaRequest $request, Jumuiya $jumuiya): RedirectResponse
    {
        $this->authorize('update', $jumuiya);

        $parish = Parish::query()->orderBy('id')->first();
        if (! $parish) {
            return back()->with('error', 'Invalid parish.');
        }

        $currentZone = Zone::query()->where('id', $jumuiya->zone_id)->first();
        if (! $currentZone || $currentZone->parish_id !== $parish->id) {
            return back()->with('error', 'Invalid Christian Community.');
        }

        try {
            $data = $request->validated();

            $targetZone = Zone::query()
                ->where('parish_id', $parish->id)
                ->where('uuid', $data['zone_uuid'])
                ->first();

            if (! $targetZone) {
                return back()->with('error', 'Invalid zone.');
            }

            $jumuiya->update([
                'zone_id' => $targetZone->id,
                'name' => $data['name'],
                'meeting_day' => $data['meeting_day'] ?? null,
                'established_year' => $data['established_year'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ]);

            return back()->with('success', 'Christian Community updated.');
        } catch (\Throwable $e) {
            Log::error('Jumuiya update failed', ['exception' => $e, 'jumuiya_uuid' => $jumuiya->uuid]);

            return back()->with('error', 'Unable to update Christian Community. Please try again.');
        }
    }

    public function destroy(Jumuiya $jumuiya): RedirectResponse
    {
        $this->authorize('delete', $jumuiya);

        $parish = Parish::query()->orderBy('id')->first();
        if (! $parish) {
            return back()->with('error', 'Invalid parish.');
        }

        $zone = Zone::query()->where('id', $jumuiya->zone_id)->first();
        if (! $zone || $zone->parish_id !== $parish->id) {
            return back()->with('error', 'Invalid Christian Community.');
        }

        if ($jumuiya->families()->exists() || $jumuiya->members()->exists() || $jumuiya->leaderships()->exists()) {
            return back()->with('error', 'Unable to delete. This Christian Community has related records.');
        }

        try {
            $jumuiya->delete();

            return back()->with('success', 'Christian Community deleted.');
        } catch (\Throwable $e) {
            Log::error('Jumuiya delete failed', ['exception' => $e, 'jumuiya_uuid' => $jumuiya->uuid]);

            return back()->with('error', 'Unable to delete Christian Community. Please try again.');
        }
    }
}
