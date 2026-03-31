<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Accounting\PettyCashActionRequest;
use App\Http\Requests\Finance\Accounting\StorePettyCashVoucherRequest;
use App\Http\Resources\Finance\Accounting\PettyCashFundIndexResource;
use App\Http\Resources\Finance\Accounting\PettyCashVoucherIndexResource;
use App\Models\Finance\PettyCashFund;
use App\Models\Finance\PettyCashVoucherAttachment;
use App\Models\Finance\PettyCashVoucher;
use App\Services\Finance\Accounting\JournalNumberService;
use App\Services\Finance\Accounting\JournalPostingService;
use App\Services\Finance\Accounting\PettyCashService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PettyCashVoucherController extends Controller
{
    public function __construct(
        private readonly PettyCashService $pettyCashService,
        private readonly JournalNumberService $journalNumberService,
        private readonly JournalPostingService $journalPostingService,
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PettyCashVoucher::class);

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $status = is_string($request->query('status')) ? trim((string) $request->query('status')) : '';
        $dateFrom = is_string($request->query('date_from')) ? trim((string) $request->query('date_from')) : '';
        $dateTo = is_string($request->query('date_to')) ? trim((string) $request->query('date_to')) : '';

        $items = PettyCashVoucher::query()
            ->with([
                'fund:id,uuid,name',
                'journal:id,uuid,journal_no',
                'attachments:id,uuid,petty_cash_voucher_id,original_name,mime_type,size_bytes,created_at',
                'lines:id,uuid,petty_cash_voucher_id,expense_ledger_id,description,amount',
                'lines.expenseLedger:id,uuid,name,account_code',
            ])
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function ($w) use ($safe) {
                    $w->where('voucher_no', 'like', $safe . '%')
                        ->orWhere('payee_name', 'like', $safe . '%')
                        ->orWhere('reference_no', 'like', $safe . '%')
                        ->orWhere('description', 'like', $safe . '%');
                });
            })
            ->when($status !== '', fn ($qb) => $qb->where('status', $status))
            ->when($dateFrom !== '' && $dateTo !== '', fn ($qb) => $qb->whereBetween('transaction_date', [$dateFrom, $dateTo]))
            ->when($dateFrom !== '' && $dateTo === '', fn ($qb) => $qb->where('transaction_date', '>=', $dateFrom))
            ->when($dateFrom === '' && $dateTo !== '', fn ($qb) => $qb->where('transaction_date', '<=', $dateTo))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->simplePaginate(20)
            ->withQueryString();

        $funds = PettyCashFund::query()
            ->join('ledgers', 'ledgers.id', '=', 'petty_cash_funds.ledger_id')
            ->join('currencies', 'currencies.id', '=', 'petty_cash_funds.currency_id')
            ->select([
                'petty_cash_funds.id',
                'petty_cash_funds.uuid',
                'petty_cash_funds.name',
                'petty_cash_funds.code',
                'petty_cash_funds.ledger_id',
                'petty_cash_funds.currency_id',
                'petty_cash_funds.imprest_amount',
                'petty_cash_funds.is_active',
                'ledgers.uuid as ledger_uuid',
                'ledgers.name as ledger_name',
                'ledgers.opening_balance as ledger_opening_balance',
                'ledgers.opening_balance_type as ledger_opening_balance_type',
                'currencies.uuid as currency_uuid',
                'currencies.code as currency_code',
            ])
            ->where('petty_cash_funds.is_active', true)
            ->orderBy('petty_cash_funds.name')
            ->get();

        $fundLedgerIds = $funds
            ->pluck('ledger_id')
            ->filter()
            ->unique()
            ->values();

        $glSums = $fundLedgerIds->isEmpty()
            ? collect()
            : DB::table('general_ledgers')
                ->selectRaw('ledger_id, COALESCE(SUM(debit_amount - credit_amount), 0) as gl_delta')
                ->whereIn('ledger_id', $fundLedgerIds)
                ->groupBy('ledger_id')
                ->pluck('gl_delta', 'ledger_id');

        $funds = $funds->map(function ($fund) use ($glSums) {
            $opening = (float) ($fund->ledger_opening_balance ?? 0);
            $openingSigned = ($fund->ledger_opening_balance_type ?? 'debit') === 'credit' ? -$opening : $opening;
            $delta = (float) ($glSums[$fund->ledger_id] ?? 0);
            $fund->gl_balance_signed = $openingSigned + $delta;

            return $fund;
        });

        return Inertia::render('Finance/Accounting/PettyCash/Vouchers/Index', [
            'items' => PettyCashVoucherIndexResource::collection($items),
            'funds' => PettyCashFundIndexResource::collection($funds)->resolve(),
            'filters' => [
                'q' => $q,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'statuses' => ['draft', 'submitted', 'approved', 'posted', 'cancelled'],
        ]);
    }

    public function store(StorePettyCashVoucherRequest $request): RedirectResponse
    {
        $this->authorize('create', PettyCashVoucher::class);

        $validated = $request->validated();
        $user = $request->user();
        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        $fund = PettyCashFund::query()->where('uuid', (string) $validated['petty_cash_fund_uuid'])->where('is_active', true)->first();
        if (! $fund) {
            return back()->with('error', 'Invalid petty cash fund. Select an active fund and try again.');
        }

        $storedAttachments = [];

        try {
            $storedAttachments = $this->storeVoucherAttachments($request->file('attachments', []), 'draft');
            $this->pettyCashService->createVoucher($fund, $validated, (int) $user->id, $storedAttachments);
            return back()->with('success', 'Petty cash voucher created as draft.');
        } catch (\Throwable $e) {
            $this->cleanupStoredVoucherAttachments($storedAttachments);
            Log::error('Petty cash voucher create failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to create petty cash voucher.');
        }
    }

    public function update(StorePettyCashVoucherRequest $request, string $uuid): RedirectResponse
    {
        $voucher = $this->findVoucher($uuid);
        $this->authorize('update', $voucher);

        $storedAttachments = [];

        try {
            $storedAttachments = $this->storeVoucherAttachments($request->file('attachments', []), $voucher->uuid);
            $this->pettyCashService->updateDraftVoucher($voucher, $request->validated(), $storedAttachments);
            return back()->with('success', 'Petty cash draft updated.');
        } catch (\Throwable $e) {
            $this->cleanupStoredVoucherAttachments($storedAttachments);
            Log::error('Petty cash voucher update failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to update petty cash draft.');
        }
    }

    public function submit(Request $request, string $uuid): RedirectResponse
    {
        $voucher = $this->findVoucher($uuid);
        $this->authorize('submit', $voucher);
        $user = $request->user();

        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->submitVoucher($voucher, (int) $user->id);
            return back()->with('success', 'Petty cash voucher submitted for approval.');
        } catch (\Throwable $e) {
            Log::error('Petty cash voucher submit failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to submit petty cash voucher.');
        }
    }

    public function approve(Request $request, string $uuid): RedirectResponse
    {
        $voucher = $this->findVoucher($uuid);
        $this->authorize('approve', $voucher);
        $user = $request->user();

        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->approveVoucher($voucher, (int) $user->id);
            return back()->with('success', 'Petty cash voucher approved.');
        } catch (\Throwable $e) {
            Log::error('Petty cash voucher approve failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to approve petty cash voucher.');
        }
    }

    public function reject(PettyCashActionRequest $request, string $uuid): RedirectResponse
    {
        $voucher = $this->findVoucher($uuid);
        $this->authorize('reject', $voucher);
        $user = $request->user();

        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->rejectVoucher($voucher, (int) $user->id, $request->validated('reason'));
            return back()->with('success', 'Petty cash voucher returned to draft.');
        } catch (\Throwable $e) {
            Log::error('Petty cash voucher reject failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to reject petty cash voucher.');
        }
    }

    public function post(Request $request, string $uuid): RedirectResponse
    {
        $voucher = $this->findVoucher($uuid);
        $this->authorize('post', $voucher);
        $user = $request->user();

        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->postVoucher($voucher, (int) $user->id, $this->journalNumberService, $this->journalPostingService);
            return back()->with('success', 'Petty cash voucher posted to journal and general ledger.');
        } catch (\Throwable $e) {
            Log::error('Petty cash voucher post failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to post petty cash voucher.');
        }
    }

    public function cancel(PettyCashActionRequest $request, string $uuid): RedirectResponse
    {
        $voucher = $this->findVoucher($uuid);
        $this->authorize('cancel', $voucher);
        $user = $request->user();

        if (! $user) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            $this->pettyCashService->cancelVoucher($voucher, (int) $user->id, $request->validated('reason'));
            return back()->with('success', 'Petty cash voucher cancelled.');
        } catch (\Throwable $e) {
            Log::error('Petty cash voucher cancel failed', ['exception' => $e]);
            return back()->with('error', $e->getMessage() ?: 'Unable to cancel petty cash voucher.');
        }
    }

    public function downloadAttachment(Request $request, string $uuid, string $attachmentUuid)
    {
        $voucher = $this->findVoucher($uuid);
        $this->authorize('view', $voucher);

        $attachment = PettyCashVoucherAttachment::query()
            ->where('uuid', $attachmentUuid)
            ->where('petty_cash_voucher_id', $voucher->id)
            ->firstOrFail();

        $disk = Storage::disk($attachment->storage_disk ?: 'local');
        if (! $disk->exists($attachment->storage_path)) {
            abort(404);
        }

        $disposition = $request->query('disposition') === 'inline' ? 'inline' : 'attachment';

        return $disk->response($attachment->storage_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.$attachment->original_name.'"',
        ]);
    }

    private function storeVoucherAttachments(array $files, string $folder): array
    {
        $storedAttachments = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $attachmentUuid = (string) Str::uuid();
            $extension = $file->getClientOriginalExtension();
            $extension = is_string($extension) && $extension !== '' ? strtolower($extension) : 'bin';
            $relativePath = 'finance/petty-cash-vouchers/'.$folder.'/attachments/'.$attachmentUuid.'.'.$extension;

            $stored = Storage::disk('local')->putFileAs(
                dirname($relativePath),
                $file,
                basename($relativePath)
            );

            if (! $stored) {
                throw new \RuntimeException('Failed to upload voucher attachment.');
            }

            $storedAttachments[] = [
                'original_name' => (string) $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'size_bytes' => (int) $file->getSize(),
                'storage_disk' => 'local',
                'storage_path' => $relativePath,
                'sha256' => hash_file('sha256', Storage::disk('local')->path($relativePath)),
            ];
        }

        return $storedAttachments;
    }

    private function cleanupStoredVoucherAttachments(array $storedAttachments): void
    {
        foreach ($storedAttachments as $attachment) {
            $path = is_array($attachment) ? ($attachment['storage_path'] ?? null) : null;
            if (is_string($path) && $path !== '') {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private function findVoucher(string $uuid): PettyCashVoucher
    {
        return PettyCashVoucher::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
