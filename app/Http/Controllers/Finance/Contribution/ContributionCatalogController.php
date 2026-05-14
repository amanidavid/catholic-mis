<?php

namespace App\Http\Controllers\Finance\Contribution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Contribution\StoreContributionCatalogRequest;
use App\Http\Resources\Finance\ContributionCatalogResource;
use App\Models\Finance\ContributionCatalog;
use App\Models\Parish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContributionCatalogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContributionCatalog::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $isActive = $request->query('is_active');

        $query = ContributionCatalog::query()
            ->select(['id', 'uuid', 'name', 'code', 'description', 'is_active', 'created_at']);

        if ($q !== '') {
            $safe = addcslashes($q, '%_\\');
            $query->where(function ($w) use ($safe) {
                $w->where('name', 'like', $safe . '%')
                    ->orWhere('code', 'like', $safe . '%');
            });
        }

        if ($isActive !== null && is_string($isActive)) {
            $query->where('is_active', $isActive === '1');
        }

        $items = $query
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Finance/Contribution/Catalogs/Index', [
            'items' => ContributionCatalogResource::collection($items),
            'filters' => [
                'q' => $q,
                'is_active' => $isActive,
            ],
        ]);
    }

    public function store(StoreContributionCatalogRequest $request): RedirectResponse
    {
        $this->authorize('create', ContributionCatalog::class);

        $user = Auth::user();

        ContributionCatalog::create([
            'name' => $request->validated('name'),
            'code' => $request->validated('code'),
            'description' => $request->validated('description'),
            'is_active' => $request->validated('is_active', true),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Contribution catalog created successfully.');
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $catalog = ContributionCatalog::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $catalog);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:250'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $catalog->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Contribution catalog updated successfully.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $catalog = ContributionCatalog::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $catalog);

        $catalog->delete();

        return back()->with('success', 'Contribution catalog deleted successfully.');
    }
}
