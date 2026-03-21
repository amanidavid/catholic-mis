<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PerformanceMembersSeeder extends Seeder
{
    public function run(): void
    {
        $jumuiyaOverride = (int) (env('PERF_JUMUIYA_ID') ?: 0);

        $targetJumuiyaName = (string) (env('PERF_JUMUIYA_NAME') ?: 'Perf Jumuiya');
        $runTag = (string) (env('PERF_RUN_TAG') ?: Str::uuid());

        $jumuiyaId = $jumuiyaOverride > 0
            ? $jumuiyaOverride
            : (int) (DB::table('jumuiyas')->where('name', $targetJumuiyaName)->orderBy('id')->value('id') ?? 0);

        if ($jumuiyaId <= 0) {
            $parishId = (int) (DB::table('parishes')->orderBy('id')->value('id') ?? 0);

            if ($parishId <= 0) {
                $dioceseId = (int) (DB::table('dioceses')->orderBy('id')->value('id') ?? 0);

                if ($dioceseId <= 0) {
                    $existingDioceseId = (int) (DB::table('dioceses')->where('name', 'Perf Diocese')->value('id') ?? 0);
                    if ($existingDioceseId > 0) {
                        $dioceseId = $existingDioceseId;
                    } else {
                        $dioceseId = (int) DB::table('dioceses')->insertGetId([
                            'uuid' => (string) Str::uuid(),
                            'name' => 'Perf Diocese',
                        'archbishop_name' => null,
                        'established_year' => null,
                        'address' => null,
                        'phone' => null,
                        'email' => null,
                        'website' => null,
                        'country' => null,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                        ]);
                    }
                }

                $existingParishId = (int) (DB::table('parishes')->where('name', 'Perf Parish')->value('id') ?? 0);
                if ($existingParishId > 0) {
                    $parishId = $existingParishId;
                } else {
                    $parishId = (int) DB::table('parishes')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'diocese_id' => $dioceseId,
                        'name' => 'Perf Parish',
                        'code' => null,
                        'patron_saint' => null,
                        'established_year' => null,
                        'address' => null,
                        'phone' => null,
                        'email' => null,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $zoneId = (int) (DB::table('zones')->where('parish_id', $parishId)->where('name', 'Perf Zone')->value('id') ?? 0);

            if ($zoneId <= 0) {
                $zoneId = (int) DB::table('zones')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'parish_id' => $parishId,
                    'name' => 'Perf Zone',
                    'description' => null,
                    'established_year' => null,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $existingJumuiyaId = (int) (DB::table('jumuiyas')->where('zone_id', $zoneId)->where('name', $targetJumuiyaName)->value('id') ?? 0);
            $jumuiyaId = $existingJumuiyaId > 0
                ? $existingJumuiyaId
                : (int) DB::table('jumuiyas')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'zone_id' => $zoneId,
                    'name' => $targetJumuiyaName,
                    'meeting_day' => null,
                    'established_year' => null,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $membersToCreate = (int) (env('PERF_MEMBERS_COUNT') ?: 5000);
        $membersPerFamily = (int) (env('PERF_MEMBERS_PER_FAMILY') ?: 5);

        if ($membersToCreate <= 0) {
            return;
        }

        if ($membersPerFamily <= 0) {
            $membersPerFamily = 1;
        }

        $familiesToCreate = (int) ceil($membersToCreate / $membersPerFamily);

        $familyIds = [];
        for ($i = 1; $i <= $familiesToCreate; $i++) {
            $familyIds[] = (int) DB::table('families')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'jumuiya_id' => $jumuiyaId,
                'family_name' => 'Perf Family '.$runTag.' '.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'family_code' => null,
                'house_number' => null,
                'street' => null,
                'head_of_family_member_id' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $firstNames = [
            'Justin', 'Justina', 'John', 'Jane', 'Joseph', 'Mary', 'Peter', 'Paul', 'Anne', 'Grace',
            'Daniel', 'David', 'Sarah', 'Michael', 'Esther', 'Samuel', 'Martha', 'Lucas', 'James', 'Ruth',
        ];
        $lastNames = [
            'Pickett', 'Mwangi', 'Mushi', 'Ngowi', 'Joseph', 'Kimaro', 'Mrema', 'Mashauri', 'Mallya', 'Kweka',
            'Said', 'Kassim', 'Ali', 'Bakari', 'Amani', 'Moyo', 'Kato', 'Okello', 'Nsubuga', 'Kagame',
        ];

        $phoneStart = 10000000;
        try {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                $maxSuffix = DB::table('members')
                    ->where('phone', 'like', '2557%')
                    ->selectRaw("MAX(CAST(SUBSTRING(phone, 5) AS UNSIGNED)) AS max_suffix")
                    ->value('max_suffix');

                $maxSuffix = is_numeric($maxSuffix) ? (int) $maxSuffix : 0;
                $phoneStart = max($phoneStart, $maxSuffix + 1);
            } elseif ($driver === 'pgsql') {
                $maxSuffix = DB::table('members')
                    ->where('phone', 'like', '2557%')
                    ->selectRaw("MAX(CAST(SUBSTRING(phone FROM 5) AS BIGINT)) AS max_suffix")
                    ->value('max_suffix');

                $maxSuffix = is_numeric($maxSuffix) ? (int) $maxSuffix : 0;
                $phoneStart = max($phoneStart, $maxSuffix + 1);
            } elseif ($driver === 'sqlite') {
                $maxSuffix = DB::table('members')
                    ->where('phone', 'like', '2557%')
                    ->selectRaw("MAX(CAST(SUBSTR(phone, 5) AS INTEGER)) AS max_suffix")
                    ->value('max_suffix');

                $maxSuffix = is_numeric($maxSuffix) ? (int) $maxSuffix : 0;
                $phoneStart = max($phoneStart, $maxSuffix + 1);
            }
        } catch (\Throwable) {
        }

        $rows = [];
        $chunkSize = 500;

        for ($n = 1; $n <= $membersToCreate; $n++) {
            $familyIndex = (int) floor(($n - 1) / $membersPerFamily);
            $familyId = (int) ($familyIds[$familyIndex] ?? 0);

            $first = (string) ($firstNames[array_rand($firstNames)] ?? 'Justin');
            $last = (string) ($lastNames[array_rand($lastNames)] ?? 'Pickett');
            $middle = null;
            $full = trim($first.' '.$last);

            $phoneSuffix = $phoneStart + $n;
            if ($phoneSuffix > 99999999) {
                $phoneSuffix = 10000000 + ($phoneSuffix % 90000000);
            }
            $phone = '2557'.str_pad((string) $phoneSuffix, 8, '0', STR_PAD_LEFT);

            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'family_id' => $familyId,
                'family_relationship_id' => null,
                'jumuiya_id' => $jumuiyaId,
                'first_name' => $first,
                'first_name_key' => mb_strtolower($first, 'UTF-8'),
                'middle_name' => $middle,
                'middle_name_key' => null,
                'last_name' => $last,
                'last_name_key' => mb_strtolower($last, 'UTF-8'),
                'full_name_key' => mb_strtolower($full, 'UTF-8'),
                'gender' => random_int(0, 1) === 0 ? 'male' : 'female',
                'birth_date' => null,
                'phone' => $phone,
                'email' => null,
                'national_id' => null,
                'marital_status' => null,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($rows) >= $chunkSize) {
                DB::table('members')->insert($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            DB::table('members')->insert($rows);
        }
    }
}
