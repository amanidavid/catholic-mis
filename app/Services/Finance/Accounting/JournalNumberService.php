<?php

namespace App\Services\Finance\Accounting;

use App\Models\Finance\Journal;
use Illuminate\Support\Facades\DB;

class JournalNumberService
{
    /**
     * Generate the next journal number for the given year.
     * Format: JV-YYYY-000001
     *
     * @return array{journal_no:string, journal_year:int, sequence:int}
     */
    public function nextForYear(int $year): array
    {
        return DB::transaction(function () use ($year): array {
            // Lock year rows to avoid duplicates under concurrency.
            $max = Journal::query()
                ->where('journal_year', $year)
                ->lockForUpdate()
                ->max('sequence');

            $next = ((int) $max) + 1;
            $journalNo = sprintf('JV-%d-%06d', $year, $next);

            return [
                'journal_no' => $journalNo,
                'journal_year' => $year,
                'sequence' => $next,
            ];
        }, 3);
    }
}
