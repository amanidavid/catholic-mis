<?php

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Resources\People\FamilyRelationshipResource;
use App\Models\People\FamilyRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FamilyRelationshipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FamilyRelationship::class);

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $relationshipsQuery = FamilyRelationship::query()
            ->when($q !== '', function (Builder $qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%');
                });
            })
            ->orderBy('name');

        $relationships = $relationshipsQuery->paginate(10)->withQueryString();

        return Inertia::render('FamilyRelationships/Index', [
            'filters' => [
                'q' => $q,
            ],
            'relationships' => FamilyRelationshipResource::collection($relationships),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FamilyRelationship::class);

        $q = $request->query('q');
        $q = is_string($q) ? trim($q) : '';

        $safe = addcslashes($q, '%_\\');

        $relationships = FamilyRelationship::query()
            ->select(['uuid', 'name'])
            ->where('is_active', true)
            ->when($q !== '', function (Builder $qb) use ($safe) {
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (FamilyRelationship $r) => ['uuid' => $r->uuid, 'name' => $r->name])
            ->values();

        return response()->json(['data' => $relationships]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', FamilyRelationship::class);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\pL\s\'-]+$/u',
                Rule::unique('family_relationships', 'name'),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        try {
            FamilyRelationship::query()->create([
                'name' => trim($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            ]);

            return back()->with('success', 'Family Relationship saved.');
        } catch (\Throwable $e) {
            Log::error('FamilyRelationship store failed', ['exception' => $e]);

            return back()->with('error', 'Unable to save family relationship. Please try again.');
        }
    }

    public function update(Request $request, FamilyRelationship $familyRelationship): RedirectResponse
    {
        $this->authorize('update', $familyRelationship);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\pL\s\'-]+$/u',
                Rule::unique('family_relationships', 'name')->ignore($familyRelationship->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (! ((bool) $validated['is_active']) && $familyRelationship->members()->exists()) {
            return back()->with('error', 'Unable to deactivate. This relationship is used by members.');
        }

        try {
            $familyRelationship->update([
                'name' => trim($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) $validated['is_active'],
            ]);

            return back()->with('success', 'Family Relationship updated.');
        } catch (\Throwable $e) {
            Log::error('FamilyRelationship update failed', ['exception' => $e, 'uuid' => $familyRelationship->uuid]);

            return back()->with('error', 'Unable to update family relationship. Please try again.');
        }
    }

    public function destroy(Request $request, FamilyRelationship $familyRelationship): RedirectResponse
    {
        $this->authorize('delete', $familyRelationship);

        if ($familyRelationship->members()->exists()) {
            return back()->with('error', 'Unable to delete. This relationship is used by members. Remove it from members first.');
        }

        try {
            $familyRelationship->delete();

            return back()->with('success', 'Family Relationship deleted.');
        } catch (\Throwable $e) {
            Log::error('FamilyRelationship delete failed', ['exception' => $e, 'uuid' => $familyRelationship->uuid]);

            return back()->with('error', 'Unable to delete family relationship. Please try again.');
        }
    }
}
