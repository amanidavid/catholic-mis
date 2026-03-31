<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Accounting\StoreDoubleEntryRequest;
use App\Http\Resources\Finance\Accounting\DoubleEntryIndexResource;
use App\Http\Resources\Finance\Accounting\LedgerOptionResource;
use App\Models\Finance\DoubleEntry;
use App\Models\Finance\Ledger;
use App\Support\Finance\BankTransactionTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DoubleEntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DoubleEntry::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $perPage = (int) ($request->query('per_page') ?? 15);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $items = DoubleEntry::query()
            ->with([
                'ledger:id,uuid,name,account_code',
                'debitLedger:id,uuid,name,account_code',
                'creditLedger:id,uuid,name,account_code',
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function ($w) use ($safe) {
                    $w->where('description', 'like', $safe . '%')
                        ->orWhere('transaction_type', 'like', $safe . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $ledgers = Ledger::query()
            ->select(['id', 'uuid', 'name', 'account_code', 'currency_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/Accounting/DoubleEntries/Index', [
            'items' => DoubleEntryIndexResource::collection($items),
            'ledgers' => LedgerOptionResource::collection($ledgers)->resolve(),
            'transaction_types' => BankTransactionTypes::labels(),
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreDoubleEntryRequest $request): RedirectResponse
    {
        $this->authorize('create', DoubleEntry::class);

        $validated = $request->validated();
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            DB::transaction(function () use ($validated, $user): void {
                $ledgerId = null;
                if (! empty($validated['ledger_uuid'])) {
                    $ledgerId = Ledger::query()->where('uuid', (string) $validated['ledger_uuid'])->value('id');
                    if (! $ledgerId) {
                        throw new \RuntimeException('Invalid ledger.');
                    }
                }

                $debitId = Ledger::query()->where('uuid', (string) $validated['debit_ledger_uuid'])->value('id');
                $creditId = Ledger::query()->where('uuid', (string) $validated['credit_ledger_uuid'])->value('id');

                if (! $debitId || ! $creditId) {
                    throw new \RuntimeException('Invalid debit/credit ledger.');
                }

                if ((int) $debitId === (int) $creditId) {
                    throw new \RuntimeException('Debit ledger and credit ledger must be different.');
                }

                if ($ledgerId) {
                    $duplicateExists = DoubleEntry::query()
                        ->where('ledger_id', (int) $ledgerId)
                        ->where('transaction_type', $validated['transaction_type'] ?? null)
                        ->exists();

                    if ($duplicateExists) {
                        throw new \RuntimeException('A mapping for this lookup ledger and transaction type already exists.');
                    }
                }

                $mapping = new DoubleEntry();
                $mapping->uuid = method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid();
                $mapping->description = $validated['description'] ?? null;
                $mapping->transaction_type = $validated['transaction_type'] ?? null;
                $mapping->ledger_id = $ledgerId;
                $mapping->debit_ledger_id = (int) $debitId;
                $mapping->credit_ledger_id = (int) $creditId;
                $mapping->created_by = (int) $user->id;
                $mapping->save();
            }, 3);

            return back()->with('success', 'Double entry mapping saved.');
        } catch (\Throwable $e) {
            Log::error('Double entry mapping save failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to save mapping.');
        }
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $mapping = DoubleEntry::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $mapping);

        try {
            $mapping->delete();
            return back()->with('success', 'Mapping deleted.');
        } catch (\Throwable $e) {
            Log::error('Double entry mapping delete failed', ['exception' => $e]);
            return back()->with('error', 'Unable to delete mapping.');
        }
    }
}
