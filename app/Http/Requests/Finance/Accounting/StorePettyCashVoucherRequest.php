<?php

namespace App\Http\Requests\Finance\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StorePettyCashVoucherRequest extends FormRequest
{
    private const SAFE_TEXT_REGEX = "/^[\\pL\\pN\\s\\-\\.,&()\\/'#:]+$/u";

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $lines = collect($this->input('lines', []))
            ->map(function ($line) {
                if (! is_array($line)) {
                    return $line;
                }

                return [
                    'expense_ledger_uuid' => isset($line['expense_ledger_uuid']) ? trim((string) $line['expense_ledger_uuid']) : null,
                    'description' => isset($line['description']) && is_string($line['description'])
                        ? trim(preg_replace('/\s+/u', ' ', $line['description']))
                        : ($line['description'] ?? null),
                    'amount' => $line['amount'] ?? null,
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'payee_name' => is_string($this->payee_name) ? trim(preg_replace('/\s+/u', ' ', $this->payee_name)) : $this->payee_name,
            'reference_no' => is_string($this->reference_no) ? strtoupper(trim($this->reference_no)) : $this->reference_no,
            'description' => is_string($this->description) ? trim(preg_replace('/\s+/u', ' ', $this->description)) : $this->description,
            'lines' => $lines,
        ]);
    }

    public function rules(): array
    {
        return [
            'petty_cash_fund_uuid' => ['required', 'string', 'size:36', Rule::exists('petty_cash_funds', 'uuid')->where(fn ($q) => $q->where('is_active', true))],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'payee_name' => ['nullable', 'string', 'max:120', 'regex:' . self::SAFE_TEXT_REGEX, 'not_regex:/<[^>]*>/'],
            'reference_no' => ['nullable', 'string', 'max:100', 'regex:/^[A-Z0-9\-\/_#\.]+$/', 'not_regex:/<[^>]*>/'],
            'description' => ['nullable', 'string', 'max:150', 'regex:' . self::SAFE_TEXT_REGEX, 'not_regex:/<[^>]*>/'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.expense_ledger_uuid' => ['required', 'string', 'size:36', Rule::exists('ledgers', 'uuid')->where(fn ($q) => $q->where('is_active', true))],
            'lines.*.description' => ['nullable', 'string', 'max:150', 'regex:' . self::SAFE_TEXT_REGEX, 'not_regex:/<[^>]*>/'],
            'lines.*.amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $ledgerUuids = collect($this->input('lines', []))
                ->pluck('expense_ledger_uuid')
                ->filter(fn ($uuid) => is_string($uuid) && trim($uuid) !== '')
                ->map(fn ($uuid) => trim((string) $uuid))
                ->unique()
                ->values();

            if ($ledgerUuids->isEmpty()) {
                return;
            }

            $validExpenseLedgerCount = DB::table('ledgers')
                ->join('account_subtypes', 'account_subtypes.id', '=', 'ledgers.account_subtype_id')
                ->join('account_types', 'account_types.id', '=', 'account_subtypes.account_type_id')
                ->join('account_groups', 'account_groups.id', '=', 'account_types.account_group_id')
                ->whereIn('ledgers.uuid', $ledgerUuids)
                ->where('ledgers.is_active', true)
                ->where(function ($expense) {
                    $expense->where('account_groups.code', 2)
                        ->orWhere('account_groups.name_normalized', 'expense')
                        ->orWhereRaw('LOWER(account_groups.name) IN (?, ?)', ['expense', 'expenses']);
                })
                ->count();

            if ($validExpenseLedgerCount !== $ledgerUuids->count()) {
                $validator->errors()->add('lines', 'Each expense line must use an active ledger from the Expense account group.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'petty_cash_fund_uuid.required' => 'Please select the petty cash fund.',
            'petty_cash_fund_uuid.size' => 'The selected petty cash fund is invalid.',
            'petty_cash_fund_uuid.exists' => 'The selected petty cash fund is invalid or inactive.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be in the future.',
            'payee_name.max' => 'Payee name must not exceed 120 characters.',
            'payee_name.regex' => 'Payee name contains invalid characters.',
            'payee_name.not_regex' => 'Payee name must not contain HTML or script tags.',
            'reference_no.max' => 'Reference number must not exceed 100 characters.',
            'reference_no.regex' => 'Reference number contains invalid characters.',
            'reference_no.not_regex' => 'Reference number must not contain HTML or script tags.',
            'description.max' => 'Voucher description must not exceed 150 characters.',
            'description.regex' => 'Voucher description contains invalid characters.',
            'description.not_regex' => 'Voucher description must not contain HTML or script tags.',
            'attachments.array' => 'Attachments must be sent as a valid list of files.',
            'attachments.max' => 'You may upload up to 10 attachments for a voucher.',
            'attachments.*.file' => 'Each attachment must be a valid file.',
            'attachments.*.max' => 'Each attachment must not exceed 5 MB.',
            'attachments.*.mimes' => 'Attachments must be PDF, JPG, JPEG, or PNG files.',
            'lines.required' => 'At least one expense line is required.',
            'lines.array' => 'Expense lines must be sent in a valid list.',
            'lines.min' => 'Add at least one expense line.',
            'lines.*.expense_ledger_uuid.required' => 'Each expense line must have a ledger.',
            'lines.*.expense_ledger_uuid.size' => 'One of the selected expense ledgers is invalid.',
            'lines.*.expense_ledger_uuid.exists' => 'One of the selected expense ledgers is invalid or inactive.',
            'lines.*.description.max' => 'Line description must not exceed 150 characters.',
            'lines.*.description.regex' => 'Line description contains invalid characters.',
            'lines.*.description.not_regex' => 'Line description must not contain HTML or script tags.',
            'lines.*.amount.required' => 'Each expense line must have an amount.',
            'lines.*.amount.numeric' => 'Each expense line amount must be a valid number.',
            'lines.*.amount.gt' => 'Each expense line amount must be greater than zero.',
        ];
    }
}
