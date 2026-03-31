<?php

namespace App\Http\Controllers\Finance\Banking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Banking\StoreBankAccountTransactionRequest;
use App\Http\Resources\Finance\Accounting\LedgerOptionResource;
use App\Http\Resources\Finance\Banking\BankAccountOptionResource;
use App\Http\Resources\Finance\Banking\BankAccountTransactionIndexResource;
use App\Http\Resources\Finance\Banking\BankTransactionMappingResource;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankAccountTransaction;
use App\Models\Finance\DoubleEntry;
use App\Models\Finance\Ledger;
use App\Services\Finance\Banking\BankTransactionPostingService;
use App\Support\Finance\BankTransactionTypes;
use App\Traits\FormatsAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class BankAccountTransactionController extends Controller
{
    use FormatsAmounts;

    public function __construct(
        private readonly BankTransactionPostingService $bankTransactionPostingService,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BankAccountTransaction::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $bankAccountUuid = is_string($request->query('bank_account_uuid')) ? trim((string) $request->query('bank_account_uuid')) : '';
        $dateFrom = is_string($request->query('date_from')) ? trim((string) $request->query('date_from')) : '';
        $dateTo = is_string($request->query('date_to')) ? trim((string) $request->query('date_to')) : '';
        $perPage = (int) ($request->query('per_page') ?? 15);
        $perPage = max(5, min(100, $perPage));
        $hasOverrideColumns = Schema::hasColumns('bank_account_transactions', [
            'debit_ledger_id',
            'credit_ledger_id',
            'is_manual_override',
        ]);

        $transactionWith = [
            'bankAccount:id,uuid,bank_id,currency_id,account_name,account_number',
            'bankAccount.bank:id,uuid,name',
            'bankAccount.currency:id,uuid,code,name',
            'doubleEntry:id,uuid',
            'journal:id,uuid,journal_no',
        ];

        if ($hasOverrideColumns) {
            $transactionWith[] = 'debitLedger:id,uuid,name,account_code';
            $transactionWith[] = 'creditLedger:id,uuid,name,account_code';
        }

        $transactionSelect = [
            'id',
            'uuid',
            'bank_account_id',
            'double_entry_id',
            'transaction_date',
            'transaction_type',
            'direction',
            'amount',
            'reference_no',
            'description',
            'source_type',
            'source_id',
            'journal_id',
            'created_at',
        ];

        if ($hasOverrideColumns) {
            $transactionSelect[] = 'debit_ledger_id';
            $transactionSelect[] = 'credit_ledger_id';
            $transactionSelect[] = 'is_manual_override';
        }

        $items = BankAccountTransaction::query()
            ->with($transactionWith)
            ->select($transactionSelect)
            ->when($bankAccountUuid !== '', function ($qb) use ($bankAccountUuid) {
                $accountId = BankAccount::query()->where('uuid', $bankAccountUuid)->value('id');
                if ($accountId) {
                    $qb->where('bank_account_id', (int) $accountId);
                }
            })
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function ($w) use ($safe) {
                    $w->where('reference_no', 'like', $safe . '%')
                        ->orWhere('description', 'like', $safe . '%')
                        ->orWhere('transaction_type', 'like', $safe . '%');
                });
            })
            ->when($dateFrom !== '' && $dateTo !== '', fn ($qb) => $qb->whereBetween('transaction_date', [$dateFrom, $dateTo]))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $accounts = BankAccount::query()
            ->with(['bank:id,uuid,name', 'currency:id,uuid,code,name', 'ledger:id,uuid,name,account_code'])
            ->select(['id', 'uuid', 'bank_id', 'ledger_id', 'currency_id', 'account_name', 'account_number'])
            ->where('is_active', true)
            ->orderBy('account_name_normalized')
            ->get();

        $ledgers = Ledger::query()
            ->select(['id', 'uuid', 'name', 'account_code', 'currency_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $mappings = DoubleEntry::query()
            ->with([
                'ledger:id,uuid',
                'debitLedger:id,uuid,name,account_code',
                'creditLedger:id,uuid,name,account_code',
            ])
            ->select(['id', 'uuid', 'ledger_id', 'transaction_type', 'debit_ledger_id', 'credit_ledger_id'])
            ->whereNotNull('transaction_type')
            ->orderBy('transaction_type')
            ->get();

        return Inertia::render('Finance/Banking/Transactions/Index', [
            'items' => BankAccountTransactionIndexResource::collection($items),
            'accounts' => BankAccountOptionResource::collection($accounts)->resolve(),
            'ledgers' => LedgerOptionResource::collection($ledgers)->resolve(),
            'mappings' => BankTransactionMappingResource::collection($mappings)->resolve(),
            'transaction_types' => BankTransactionTypes::labels(),
            'filters' => [
                'q' => $q,
                'bank_account_uuid' => $bankAccountUuid,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreBankAccountTransactionRequest $request): RedirectResponse
    {
        $this->authorize('create', BankAccountTransaction::class);

        $validated = $request->validated();
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $account = BankAccount::query()->where('uuid', (string) $validated['bank_account_uuid'])->select(['id', 'ledger_id'])->first();
        if (! $account) {
            return back()->with('error', 'Invalid bank account.');
        }

        try {
            $item = $this->bankTransactionPostingService->createAndPost($account, $validated, (int) $user->id);
            $item->loadMissing('journal:id,journal_no');

            return back()->with('success', "Bank transaction saved and posted to journal {$item->journal?->journal_no}.");
        } catch (\Throwable $e) {
            Log::error('Bank transaction save failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to save bank transaction. Please try again.');
        }
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $item = BankAccountTransaction::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $item);

        try {
            $item->delete();
            return back()->with('success', 'Bank transaction deleted.');
        } catch (\Throwable $e) {
            Log::error('Bank transaction delete failed', ['exception' => $e]);
            return back()->with('error', 'Unable to delete bank transaction.');
        }
    }
}
