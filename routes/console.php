<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Traits\NormalizesNames;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('names:normalize {--only-active : Only update active rows where applicable} {--dry-run : Show counts only, do not update} {--chunk=500 : Chunk size}', function () {
    $onlyActive = (bool) $this->option('only-active');
    $dryRun = (bool) $this->option('dry-run');
    $chunk = (int) ($this->option('chunk') ?? 500);
    $chunk = $chunk > 0 ? $chunk : 500;

    $this->info('Normalizing names...');
    $this->line('Options: only-active='.(int) $onlyActive.' dry-run='.(int) $dryRun.' chunk='.$chunk);

    $updatedTotal = 0;

    $process = function (string $table, string $pk, array $columns, ?callable $scope = null) use (&$updatedTotal, $chunk, $dryRun) {
        $q = DB::table($table)->orderBy($pk);
        if ($scope) {
            $scope($q);
        }

        $lastId = 0;
        while (true) {
            $rows = (clone $q)
                ->where($pk, '>', $lastId)
                ->limit($chunk)
                ->get(array_values(array_unique(array_merge([$pk], $columns))));

            if ($rows->count() === 0) {
                break;
            }

            foreach ($rows as $r) {
                $lastId = (int) $r->{$pk};

                $updates = [];
                foreach ($columns as $col) {
                    $current = $r->{$col} ?? null;
                    $nullable = $current === null;
                    $normalized = NormalizesNames::normalize(is_string($current) ? $current : null, $nullable);

                    if ($normalized !== $current) {
                        $updates[$col] = $normalized;
                    }
                }

                if (! empty($updates)) {
                    $updatedTotal++;
                    if (! $dryRun) {
                        DB::table($table)->where($pk, $lastId)->update($updates);
                    }
                }
            }
        }
    };

    $process('zones', 'id', ['name'], function ($q) use ($onlyActive) {
        if ($onlyActive) {
            $q->where('is_active', true);
        }
    });

    $process('jumuiyas', 'id', ['name'], function ($q) use ($onlyActive) {
        if ($onlyActive) {
            $q->where('is_active', true);
        }
    });

    $process('families', 'id', ['family_name'], function ($q) use ($onlyActive) {
        if ($onlyActive) {
            $q->where('is_active', true);
        }
    });

    $process('members', 'id', ['first_name', 'middle_name', 'last_name'], function ($q) use ($onlyActive) {
        if ($onlyActive) {
            $q->where('is_active', true);
        }
    });

    $process('parish_staff', 'id', ['first_name', 'middle_name', 'last_name'], function ($q) use ($onlyActive) {
        if ($onlyActive) {
            $q->where('is_active', true);
        }
    });

    $this->info(($dryRun ? 'Dry run: ' : '').'Rows requiring update: '.$updatedTotal);
})->purpose('Normalize casing of stored names for consistent display');
