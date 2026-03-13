<?php

namespace App\Http\Controllers\Clergy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\IndexInstitutionsRequest;
use App\Http\Requests\Institution\StoreInstitutionRequest;
use App\Http\Requests\Institution\UpdateInstitutionRequest;
use App\Models\Clergy\Institution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function lookup(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $safe = addcslashes($q, '%_\\');

        $rows = Institution::query()
            ->select(['uuid', 'name', 'type'])
            ->where('is_active', true)
            ->when($safe !== '', fn (Builder $qb) => $qb->where('name', 'like', $safe.'%'))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Institution $i) => [
                'uuid' => $i->uuid,
                'name' => $i->name,
                'type' => $i->type,
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }

    public function index(IndexInstitutionsRequest $request): Response
    {
        $validated = $request->validated();
        $q = $validated['q'] ?? '';
        $perPage = (int) ($validated['per_page'] ?? 10);

        $safe = addcslashes($q, '%_\\');

        $query = Institution::query()
            ->select(['id', 'uuid', 'name', 'type', 'location', 'country', 'contact', 'is_active', 'created_at'])
            ->when($q !== '', fn (Builder $qb) => $qb->where('name', 'like', $safe.'%'))
            ->orderBy('name')
            ->orderBy('id');

        $paginated = $query->paginate($perPage)->withQueryString();

        $rows = $paginated->getCollection()->map(fn (Institution $i) => [
            'uuid' => $i->uuid,
            'name' => $i->name,
            'type' => $i->type,
            'location' => $i->location,
            'country' => $i->country,
            'contact' => $i->contact,
            'is_active' => (bool) $i->is_active,
        ])->values();

        return Inertia::render('Institutions/Index', [
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
            'institutions' => [
                'data' => $rows,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'from' => $paginated->firstItem(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'to' => $paginated->lastItem(),
                    'total' => $paginated->total(),
                    'links' => $paginated->linkCollection(),
                ],
            ],
        ]);
    }

    public function store(StoreInstitutionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            Institution::query()->create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['name'],
                'name_key' => $data['name_key'],
                'type' => $data['type'],
                'location' => $data['location'] ?? null,
                'country' => $data['country'] ?? null,
                'contact' => $data['contact'] ?? null,
                'is_active' => true,
            ]);

            return back()->with('success', 'Institution created.');
        } catch (\Throwable $e) {
            Log::error('Institution store failed', ['exception' => $e]);
            return back()->with('error', 'Unable to create institution. Please try again.');
        }
    }

    public function update(UpdateInstitutionRequest $request, Institution $institution): RedirectResponse
    {
        $data = $request->validated();

        try {
            $institution->update([
                'name' => $data['name'],
                'name_key' => $data['name_key'],
                'type' => $data['type'],
                'location' => $data['location'] ?? null,
                'country' => $data['country'] ?? null,
                'contact' => $data['contact'] ?? null,
                'is_active' => (bool) $data['is_active'],
            ]);

            return back()->with('success', 'Institution updated.');
        } catch (\Throwable $e) {
            Log::error('Institution update failed', ['exception' => $e, 'institution_uuid' => $institution->uuid]);
            return back()->with('error', 'Unable to update institution. Please try again.');
        }
    }

    public function destroy(Request $request, Institution $institution): RedirectResponse
    {
        if (! $request->user()?->can('institutions.delete')) {
            return back()->with('error', 'You do not have permission to delete institutions.');
        }

        try {
            $inUse = \App\Models\ParishStaffAssignment::query()
                ->where('institution_id', $institution->id)
                ->exists();

            if ($inUse) {
                return back()->with('error', 'Unable to delete. This institution has assignment history.');
            }

            $institution->delete();
            return back()->with('success', 'Institution deleted.');
        } catch (\Throwable $e) {
            Log::error('Institution delete failed', ['exception' => $e, 'institution_uuid' => $institution->uuid]);
            return back()->with('error', 'Unable to delete institution. Please try again.');
        }
    }
}
