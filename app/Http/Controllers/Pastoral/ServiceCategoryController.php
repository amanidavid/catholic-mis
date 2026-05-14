<?php

namespace App\Http\Controllers\Pastoral;

use App\Http\Controllers\Concerns\ResolvesSingleParishContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pastoral\IndexServiceCategoriesRequest;
use App\Http\Requests\Pastoral\StoreServiceCategoryRequest;
use App\Http\Requests\Pastoral\UpdateServiceCategoryRequest;
use App\Http\Resources\Pastoral\PastoralServiceCategoryResource;
use App\Models\Pastoral\PastoralServiceCategory;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    use ResolvesSingleParishContext;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(IndexServiceCategoriesRequest $request): Response
    {
        $this->authorize('viewAny', PastoralServiceCategory::class);

        $validated = $request->validated();
        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $status = is_string($validated['status'] ?? null) ? trim((string) $validated['status']) : 'all';
        $perPage = (int) ($validated['per_page'] ?? 15);
        $parishId = $this->resolveCurrentParishId($request->user());

        $normalizedPrefix = $this->normalizedPrefixLike($q);

        $categories = PastoralServiceCategory::query()
            ->where('parish_id', $parishId)
            ->when($normalizedPrefix, fn ($qb) => $qb->where('name_normalized', 'like', $normalizedPrefix))
            ->when($status === 'active', fn ($qb) => $qb->where('is_active', true))
            ->when($status === 'inactive', fn ($qb) => $qb->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name_normalized')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Pastoral/ServiceCategories/Index', [
            'filters' => [
                'q' => $q !== '' ? $q : null,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'categories' => PastoralServiceCategoryResource::collection($categories),
            'can' => [
                'create' => $request->user()?->can('service-categories.create') ?? false,
                'update' => $request->user()?->can('service-categories.update') ?? false,
                'delete' => $request->user()?->can('service-categories.delete') ?? false,
            ],
        ]);
    }

    public function store(StoreServiceCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', PastoralServiceCategory::class);

        $validated = $request->validated();
        $parishId = $this->resolveCurrentParishId($request->user());
        $code = mb_strtoupper(trim((string) $validated['code']), 'UTF-8');

        $exists = PastoralServiceCategory::query()
            ->where('parish_id', $parishId)
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Service category code already exists in this parish.');
        }

        try {
            PastoralServiceCategory::query()->create([
                'parish_id' => $parishId,
                'name' => trim((string) $validated['name']),
                'code' => $code,
                'description' => $validated['description'] ?? null,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            ]);

            return back()->with('success', 'Service category saved successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to store service category', ['exception' => $e]);

            return back()->with('error', 'Unable to save service category. Please try again.');
        }
    }

    public function update(UpdateServiceCategoryRequest $request, PastoralServiceCategory $serviceCategory): RedirectResponse
    {
        $category = PastoralServiceCategory::query()
            ->where('uuid', $serviceCategory->uuid)
            ->firstOrFail();

        abort_unless((int) $category->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('update', $category);

        $validated = $request->validated();
        $code = mb_strtoupper(trim((string) $validated['code']), 'UTF-8');

        $exists = PastoralServiceCategory::query()
            ->where('parish_id', (int) $category->parish_id)
            ->where('code', $code)
            ->where('id', '!=', (int) $category->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Service category code already exists in this parish.');
        }

        try {
            $category->update([
                'name' => trim((string) $validated['name']),
                'code' => $code,
                'description' => $validated['description'] ?? null,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) $validated['is_active'],
            ]);

            return back()->with('success', 'Service category updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to update service category', ['exception' => $e, 'uuid' => $category->uuid]);

            return back()->with('error', 'Unable to update service category. Please try again.');
        }
    }

    public function destroy(
        \Illuminate\Http\Request $request,
        PastoralServiceCategory $serviceCategory
    ): RedirectResponse {
        $category = PastoralServiceCategory::query()
            ->where('uuid', $serviceCategory->uuid)
            ->firstOrFail();

        abort_unless((int) $category->parish_id === $this->resolveCurrentParishId($request->user()), 404);
        $this->authorize('delete', $category);

        if ($category->requestItems()->exists()) {
            return back()->with('error', 'Cannot delete category that is used by service request items.');
        }

        try {
            $category->delete();

            return back()->with('success', 'Service category deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to delete service category', ['exception' => $e, 'uuid' => $category->uuid]);

            return back()->with('error', 'Unable to delete service category. Please try again.');
        }
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
}
