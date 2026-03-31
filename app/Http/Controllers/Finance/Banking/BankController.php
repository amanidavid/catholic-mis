<?php

namespace App\Http\Controllers\Finance\Banking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Banking\BulkUpsertBanksRequest;
use App\Http\Resources\Finance\Banking\BankIndexResource;
use App\Models\Finance\Bank;
use App\Models\Finance\BankAccount;
use App\Traits\NormalizesNames;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BankController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Bank::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $perPage = (int) ($request->query('per_page') ?? 15);
        $perPage = max(5, min(100, $perPage));

        $qNormalized = $q !== '' ? mb_strtolower(NormalizesNames::normalize($q) ?? '', 'UTF-8') : '';

        $banks = Bank::query()
            ->select(['id', 'uuid', 'name', 'is_active', 'created_at'])
            ->when($qNormalized !== '', function ($qb) use ($qNormalized) {
                $safe = addcslashes($qNormalized, '%_\\');
                $qb->where('name_normalized', 'like', $safe . '%');
            })
            ->orderBy('name_normalized')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Finance/Banking/Banks/Index', [
            'banks' => BankIndexResource::collection($banks),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function bulkUpsert(BulkUpsertBanksRequest $request): RedirectResponse
    {
        $this->authorize('create', Bank::class);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated): void {
                $rows = [];
                foreach (($validated['items'] ?? []) as $item) {
                    $name = NormalizesNames::normalize((string) $item['name']);
                    $nameNormalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $name)), 'UTF-8');
                    $uuid = isset($item['uuid']) ? trim((string) $item['uuid']) : '';

                    $rows[] = [
                        'uuid' => $uuid !== ''
                            ? $uuid
                            : (method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid()),
                        'name' => $name,
                        'name_normalized' => $nameNormalized,
                        'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($rows) > 0) {
                    Bank::query()->upsert(
                        $rows,
                        ['uuid'],
                        ['name', 'name_normalized', 'is_active', 'updated_at']
                    );
                }
            });

            return back()->with('success', 'Banks saved.');
        } catch (\Throwable $e) {
            Log::error('Banks bulk upsert failed', ['exception' => $e]);
            return back()->with('error', 'Unable to save banks. Please try again.');
        }
    }

    public function deactivate(Request $request, string $uuid): RedirectResponse
    {
        $bank = Bank::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $bank);

        $bank->is_active = false;
        $bank->save();

        return back()->with('success', 'Bank deactivated.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $bank = Bank::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $bank);

        if (BankAccount::query()->where('bank_id', $bank->id)->exists()) {
            return back()->with('error', 'Cannot delete this bank because it has bank accounts.');
        }

        $bank->delete();
        return back()->with('success', 'Bank deleted.');
    }
}
