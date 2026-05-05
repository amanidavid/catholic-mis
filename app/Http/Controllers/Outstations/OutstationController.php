<?php

namespace App\Http\Controllers\Outstations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Outstation\IndexOutstationsRequest;
use App\Http\Requests\Outstation\StoreOutstationsRequest;
use App\Http\Requests\Outstation\UpdateOutstationRequest;
use App\Http\Resources\Structure\OutstationResource;
use App\Models\Structure\Outstation;
use App\Models\Structure\Parish;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OutstationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Outstation::class);

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $parish = Parish::query()->orderBy('id')->first();
        if (! $parish) {
            return response()->json(['data' => []]);
        }

        $safe = addcslashes($q, '%_\\');

        $outstations = Outstation::query()
            ->select(['uuid', 'name'])
            ->where('parish_id', $parish->id)
            ->when($q !== '', fn (Builder $qb) => $qb->where('name', 'like', $safe.'%'))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Outstation $outstation) => ['uuid' => $outstation->uuid, 'name' => $outstation->name])
            ->values();

        return response()->json(['data' => $outstations]);
    }

    public function index(IndexOutstationsRequest $request): Response
    {
        $this->authorize('viewAny', Outstation::class);

        $validated = $request->validated();
        $q = $validated['q'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 10);

        $parish = Parish::query()->orderBy('id')->first();

        if (! $parish) {
            return Inertia::render('Outstations/Index', [
                'filters' => ['q' => $q, 'per_page' => $perPage],
                'outstations' => [
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

        $outstations = Outstation::query()
            ->where('parish_id', $parish->id)
            ->when(is_string($q) && $q !== '', function (Builder $qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where('name', 'like', $safe.'%');
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Outstations/Index', [
            'filters' => ['q' => $q, 'per_page' => $perPage],
            'outstations' => OutstationResource::collection($outstations),
        ]);
    }

    public function store(StoreOutstationsRequest $request): RedirectResponse
    {
        $this->authorize('create', Outstation::class);

        $parish = Parish::query()->orderBy('id')->first();
        if (! $parish) {
            return back()->with('error', 'Parish is not configured yet. Please complete Setup first.');
        }

        try {
            $rows = $request->validated('outstations') ?? [];

            DB::transaction(function () use ($rows, $parish): void {
                foreach ($rows as $row) {
                    Outstation::query()->create([
                        'parish_id' => $parish->id,
                        'name' => $row['name'],
                        'description' => $row['description'] ?? null,
                        'established_year' => $row['established_year'] ?? null,
                        'is_active' => true,
                    ]);
                }
            });

            return redirect()->route('outstations.index')->with('success', count($rows) === 1 ? 'Outstation saved.' : 'Outstations saved.');
        } catch (\Throwable $e) {
            Log::error('Outstation bulk store failed', ['exception' => $e]);

            return back()->with('error', 'Unable to save outstations. Please try again.');
        }
    }

    public function update(UpdateOutstationRequest $request, Outstation $outstation): RedirectResponse
    {
        $this->authorize('update', $outstation);

        $parish = Parish::query()->orderBy('id')->first();
        if ($parish && (int) $outstation->parish_id !== (int) $parish->id) {
            return back()->with('error', 'Invalid outstation.');
        }

        try {
            $data = $request->validated();

            $outstation->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'established_year' => $data['established_year'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ]);

            return back()->with('success', 'Outstation updated.');
        } catch (\Throwable $e) {
            Log::error('Outstation update failed', ['exception' => $e, 'outstation_uuid' => $outstation->uuid]);

            return back()->with('error', 'Unable to update outstation. Please try again.');
        }
    }

    public function destroy(Outstation $outstation): RedirectResponse
    {
        $this->authorize('delete', $outstation);

        $parish = Parish::query()->orderBy('id')->first();
        if ($parish && (int) $outstation->parish_id !== (int) $parish->id) {
            return back()->with('error', 'Invalid outstation.');
        }

        if ($outstation->zones()->exists()) {
            return back()->with('error', 'Unable to delete. This outstation has zones.');
        }

        try {
            $outstation->delete();

            return back()->with('success', 'Outstation deleted.');
        } catch (\Throwable $e) {
            Log::error('Outstation delete failed', ['exception' => $e, 'outstation_uuid' => $outstation->uuid]);

            return back()->with('error', 'Unable to delete outstation. Please try again.');
        }
    }
}
