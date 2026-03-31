<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Accounting\PostJournalRequest;
use App\Http\Requests\Finance\Accounting\StoreJournalRequest;
use App\Http\Resources\Finance\Accounting\JournalIndexResource;
use App\Http\Resources\Finance\Accounting\JournalShowResource;
use App\Http\Resources\Finance\Accounting\LedgerOptionResource;
use App\Models\Finance\DoubleEntry;
use App\Models\Finance\Journal;
use App\Models\Finance\JournalLine;
use App\Models\Finance\Ledger;
use App\Services\Finance\Accounting\JournalNumberService;
use App\Services\Finance\Accounting\JournalPostingService;
use App\Traits\FormatsAmounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends Controller
{
    use FormatsAmounts;

    public function __construct(
        private readonly JournalNumberService $journalNumberService,
        private readonly JournalPostingService $journalPostingService,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Journal::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $qUpper = strtoupper($q);
        $dateFrom = is_string($request->query('date_from')) ? trim((string) $request->query('date_from')) : '';
        $dateTo = is_string($request->query('date_to')) ? trim((string) $request->query('date_to')) : '';

        $perPage = (int) ($request->query('per_page') ?? 15);
        if ($perPage < 5) {
            $perPage = 5;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $lineCounts = JournalLine::query()
            ->selectRaw('journal_id, COUNT(*) as lines_count')
            ->groupBy('journal_id');

        $journals = Journal::query()
            ->leftJoinSub($lineCounts, 'journal_line_counts', function ($join) {
                $join->on('journal_line_counts.journal_id', '=', 'journals.id');
            })
            ->select([
                'journals.id',
                'journals.uuid',
                'journals.journal_no',
                'journals.sequence',
                'journals.journal_year',
                'journals.transaction_date',
                'journals.description',
                'journals.amount',
                'journals.is_posted',
                'journals.posted_at',
                'journals.created_at',
                DB::raw('COALESCE(journal_line_counts.lines_count, 0) as lines_count'),
            ])
            ->when($q !== '', function ($qb) use ($q, $qUpper) {
                $safe = addcslashes($q, '%_\\');
                $safeUpper = addcslashes($qUpper, '%_\\');
                $looksLikeJournalNo = str_starts_with($qUpper, 'JV')
                    || preg_match('/^\d{4}$/', $q) === 1
                    || preg_match('/^\d{4}-\d+$/', $q) === 1
                    || preg_match('/^JV-\d{4}/i', $q) === 1;

                if ($looksLikeJournalNo) {
                    $qb->where('journal_no', 'like', $safeUpper . '%');
                    return;
                }

                $qb->where('description', 'like', $safe . '%');
            })
            ->when($dateFrom !== '' && $dateTo !== '', fn ($qb) => $qb->whereBetween('transaction_date', [$dateFrom, $dateTo]))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $ledgers = Ledger::query()
            ->select(['id', 'uuid', 'name', 'account_code', 'currency_id'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Finance/Accounting/Journals/Index', [
            'journals' => JournalIndexResource::collection($journals),
            'ledgers' => LedgerOptionResource::collection($ledgers)->resolve(),
            'filters' => [
                'q' => $q,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Request $request, string $uuid): Response
    {
        $journal = Journal::query()
            ->where('uuid', $uuid)
            ->with(['lines.ledger:id,uuid,name,account_code'])
            ->firstOrFail();

        $this->authorize('view', $journal);

        return Inertia::render('Finance/Accounting/Journals/Show', [
            'journal' => new JournalShowResource($journal),
        ]);
    }

    public function store(StoreJournalRequest $request): RedirectResponse
    {
        $this->authorize('create', Journal::class);

        $validated = $request->validated();
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $transactionDate = (string) $validated['transaction_date'];
        $year = (int) date('Y', strtotime($transactionDate));

        try {
            $journal = DB::transaction(function () use ($validated, $user, $transactionDate, $year): Journal {
                $no = $this->journalNumberService->nextForYear($year);

                $journal = new Journal();
                $journal->journal_no = $no['journal_no'];
                $journal->sequence = $no['sequence'];
                $journal->journal_year = $no['journal_year'];
                $journal->transaction_date = $transactionDate;
                $journal->description = $validated['description'] ?? null;
                $journal->amount = self::normalizeAmount(0, 4);
                $journal->is_posted = false;
                $journal->created_by = (int) $user->id;
                $journal->save();

                $isQuick = ! empty($validated['quick_ledger_uuid']);

                if ($isQuick) {
                    $lookupLedger = Ledger::query()
                        ->where('uuid', (string) $validated['quick_ledger_uuid'])
                        ->select(['id', 'uuid'])
                        ->first();

                    if (! $lookupLedger) {
                        throw new \RuntimeException('Invalid ledger.');
                    }

                    $mapping = DoubleEntry::query()
                        ->where('ledger_id', (int) $lookupLedger->id)
                        ->whereNull('transaction_type')
                        ->select(['id', 'debit_ledger_id', 'credit_ledger_id'])
                        ->first();

                    if (! $mapping) {
                        throw new \RuntimeException('No double entry mapping configured for the selected ledger.');
                    }

                    $amount = (float) ($validated['amount'] ?? 0);
                    if ($amount <= 0) {
                        throw new \RuntimeException('Amount must be greater than 0.');
                    }

                    $amountNorm = self::normalizeAmount($amount, 4);

                    JournalLine::query()->insert([
                        [
                            'uuid' => method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid(),
                            'journal_id' => (int) $journal->id,
                            'ledger_id' => (int) $mapping->debit_ledger_id,
                            'description' => $validated['description'] ?? null,
                            'comment' => $validated['description'] ?? null,
                            'debit_amount' => $amountNorm,
                            'credit_amount' => self::normalizeAmount(0, 4),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'uuid' => method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid(),
                            'journal_id' => (int) $journal->id,
                            'ledger_id' => (int) $mapping->credit_ledger_id,
                            'description' => $validated['description'] ?? null,
                            'comment' => $validated['description'] ?? null,
                            'debit_amount' => self::normalizeAmount(0, 4),
                            'credit_amount' => $amountNorm,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ]);

                    return $journal;
                }

                $ledgerUuids = collect($validated['lines'] ?? [])->pluck('ledger_uuid')->filter()->unique()->values();
                $ledgerMap = Ledger::query()
                    ->whereIn('uuid', $ledgerUuids)
                    ->select(['id', 'uuid', 'currency_id'])
                    ->get()
                    ->keyBy('uuid');

                $lines = [];
                foreach (($validated['lines'] ?? []) as $line) {
                    $ledger = $ledgerMap->get((string) $line['ledger_uuid']);
                    if (! $ledger) {
                        throw new \RuntimeException('Invalid ledger.');
                    }

                    $debit = array_key_exists('debit_amount', $line) ? (float) $line['debit_amount'] : 0.0;
                    $credit = array_key_exists('credit_amount', $line) ? (float) $line['credit_amount'] : 0.0;

                    if ($debit <= 0 && $credit <= 0) {
                        throw new \RuntimeException('Each line must have a debit or credit amount.');
                    }

                    if ($debit > 0 && $credit > 0) {
                        throw new \RuntimeException('A line cannot have both debit and credit.');
                    }

                    $lines[] = [
                        'uuid' => method_exists(Str::class, 'uuid7') ? (string) Str::uuid7() : (string) Str::uuid(),
                        'journal_id' => (int) $journal->id,
                        'ledger_id' => (int) $ledger->id,
                        'description' => $line['description'] ?? null,
                        'comment' => $line['description'] ?? null,
                        'debit_amount' => self::normalizeAmount($debit, 4),
                        'credit_amount' => self::normalizeAmount($credit, 4),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                JournalLine::query()->insert($lines);

                return $journal;
            }, 3);

            return redirect()->route('finance.journals.show', $journal->uuid)->with('success', 'Journal created.');
        } catch (\Throwable $e) {
            Log::error('Journal create failed', ['exception' => $e]);
            return back()->with('error', 'Unable to create journal. Please try again.');
        }
    }

    public function post(PostJournalRequest $request, string $uuid): RedirectResponse
    {
        $journal = Journal::query()->where('uuid', $uuid)->firstOrFail();
        $this->authorize('post', $journal);

        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->journalPostingService->post($journal, (int) $user->id);
            return back()->with('success', 'Journal posted.');
        } catch (\Throwable $e) {
            Log::error('Journal post failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to post journal.');
        }
    }
}
