<?php

namespace App\Http\Controllers\Finance\Contribution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Contribution\StoreContributionRuleRequest;
use App\Http\Resources\Finance\ContributionRuleResource;
use App\Models\Finance\ContributionCatalog;
use App\Models\Finance\ContributionRule;
 
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ContributionRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ContributionRule::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $isActive = $request->query('is_active');
        $catalogUuid = is_string($request->query('catalog_uuid')) ? trim((string) $request->query('catalog_uuid')) : '';
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = ContributionRule::query()
            ->with('catalog:id,uuid,name,code')
            ->select(['id', 'uuid', 'contribution_catalog_id', 'amount', 'currency_code', 'is_required', 'allow_partial_payment', 'waiver_allowed', 'effective_from', 'effective_to', 'sort_order', 'is_active', 'created_at']);

        if ($q !== '') {
            $safe = addcslashes($q, '%_\\');
            $query->whereHas('catalog', function ($w) use ($safe) {
                $w->where('name', 'like', $safe . '%')
                    ->orWhere('code', 'like', $safe . '%');
            });
        }

        if ($catalogUuid !== '') {
            $catalogId = ContributionCatalog::where('uuid', $catalogUuid)->value('id');
            if ($catalogId) {
                $query->where('contribution_catalog_id', $catalogId);
            }
        }

        

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date('date_from')->startOfDay(),
                $request->date('date_to')->endOfDay(),
            ]);
        }

        if ($isActive !== null && is_string($isActive)) {
            $query->where('is_active', $isActive === '1');
        }

        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $catalogs = ContributionCatalog::query()
            ->where('is_active', true)
            ->select(['id', 'uuid', 'name', 'code'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/Contribution/Rules/Index', [
            'items' => ContributionRuleResource::collection($items),
            'catalogs' => $catalogs,
            'filters' => [
                'q' => $q,
                'is_active' => $isActive,
                'catalog_uuid' => $catalogUuid,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function store(StoreContributionRuleRequest $request): RedirectResponse
    {
        $this->authorize('create', ContributionRule::class);

        $user = Auth::user();
        $catalog = ContributionCatalog::where('uuid', $request->validated('contribution_catalog_uuid'))->firstOrFail();

        ContributionRule::create([
            'contribution_catalog_id' => $catalog->id,
            'amount' => $request->validated('amount'),
            'currency_code' => $request->validated('currency_code', 'TZS'),
            'is_required' => $request->validated('is_required', true),
            'allow_partial_payment' => $request->validated('allow_partial_payment', false),
            'allow_override' => $request->validated('allow_override', false),
            'waiver_allowed' => $request->validated('waiver_allowed', false),
            'effective_from' => $request->validated('effective_from'),
            'effective_to' => $request->validated('effective_to'),
            'sort_order' => $request->validated('sort_order', 0),
            'is_active' => $request->validated('is_active', true),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return back()->with('success', 'Contribution rule created successfully.');
    }

    public function update(Request $request, string $uuid): RedirectResponse
    {
        $rule = ContributionRule::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $rule);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'allow_partial_payment' => ['nullable', 'boolean'],
            'waiver_allowed' => ['nullable', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule->update([
            'amount' => $validated['amount'],
            'is_required' => $validated['is_required'] ?? true,
            'allow_partial_payment' => $validated['allow_partial_payment'] ?? false,
            'waiver_allowed' => $validated['waiver_allowed'] ?? false,
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Contribution rule updated successfully.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $rule = ContributionRule::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $rule);

        $rule->delete();

        return back()->with('success', 'Contribution rule deleted successfully.');
    }
}
