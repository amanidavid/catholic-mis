<?php

namespace App\Http\Controllers\Finance\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Finance\Ledger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PettyCashLedgerLookupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['data' => []], 401);
        }

        $canUseLookup = $user->can('finance.petty-cash-vouchers.view')
            || $user->can('finance.petty-cash-vouchers.create')
            || $user->can('finance.petty-cash-replenishments.view')
            || $user->can('finance.petty-cash-replenishments.create');

        if (! $canUseLookup) {
            return response()->json(['data' => []], 403);
        }

        $purpose = is_string($request->query('purpose')) ? trim((string) $request->query('purpose')) : 'expense';
        if (! in_array($purpose, ['expense', 'source'], true)) {
            $purpose = 'expense';
        }

        $q = is_string($request->query('q')) ? trim((string) $request->query('q')) : '';
        $currencyUuid = is_string($request->query('currency_uuid')) ? trim((string) $request->query('currency_uuid')) : '';

        $rows = Ledger::query()
            ->join('account_subtypes', 'account_subtypes.id', '=', 'ledgers.account_subtype_id')
            ->join('account_types', 'account_types.id', '=', 'account_subtypes.account_type_id')
            ->join('account_groups', 'account_groups.id', '=', 'account_types.account_group_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'ledgers.currency_id')
            ->select([
                'ledgers.uuid',
                'ledgers.name',
                'ledgers.account_code',
                'currencies.uuid as currency_uuid',
            ])
            ->where('ledgers.is_active', true)
            ->when($purpose === 'expense', function ($qb) {
                $qb->where(function ($expense) {
                    $expense->where('account_groups.code', 2)
                        ->orWhere('account_groups.name_normalized', 'expense')
                        ->orWhereRaw('LOWER(account_groups.name) IN (?, ?)', ['expense', 'expenses']);
                });
            })
            ->when($currencyUuid !== '', fn ($qb) => $qb->where('currencies.uuid', $currencyUuid))
            ->when($q !== '', function ($qb) use ($q) {
                $safe = addcslashes($q, '%_\\');
                $qb->where(function ($w) use ($safe) {
                    $w->where('ledgers.name', 'like', $safe . '%')
                        ->orWhere('ledgers.account_code', 'like', $safe . '%');
                });
            })
            ->orderBy('ledgers.name')
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'uuid' => $row->uuid,
                'name' => $row->name,
                'account_code' => $row->account_code,
                'currency_uuid' => $row->currency_uuid,
            ])
            ->values();

        return response()->json(['data' => $rows]);
    }
}
