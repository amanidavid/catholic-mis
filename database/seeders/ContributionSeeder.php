<?php

namespace Database\Seeders;

use App\Models\Finance\ContributionCatalog;
use App\Models\Finance\ContributionRule;
use App\Models\Structure\Parish;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContributionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            $this->command->warn('No user found. Skipping contribution seeder.');
            return;
        }

        $catalogs = [
            ['name' => 'Baptism Offering', 'code' => 'BAPT-OFF', 'description' => 'Contribution for baptism ceremony'],
            ['name' => 'Marriage Preparation Fee', 'code' => 'MARR-PREP', 'description' => 'Contribution for marriage preparation'],
            ['name' => 'Mass Intention Contribution', 'code' => 'MASS-INT', 'description' => 'Contribution for mass intentions'],
            ['name' => 'Confirmation Program Fee', 'code' => 'CONF-PROG', 'description' => 'Contribution for confirmation program'],
            ['name' => 'Funeral Arrangement Fee', 'code' => 'FUN-ARR', 'description' => 'Contribution for funeral arrangements'],
            ['name' => 'Offertory Collection', 'code' => 'OFF-COLL', 'description' => 'General offertory contribution'],
            ['name' => 'Tithe', 'code' => 'TITHE', 'description' => 'Regular tithe contribution'],
            ['name' => 'Building Fund', 'code' => 'BLD-FUND', 'description' => 'Contribution for church building fund'],
        ];

        foreach ($catalogs as $catalog) {
            ContributionCatalog::updateOrCreate(
                ['code' => $catalog['code']],
                [
                    'name' => $catalog['name'],
                    'code' => $catalog['code'],
                    'description' => $catalog['description'],
                    'is_active' => true,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]
            );
        }

        $this->command->info('Contribution catalogs seeded successfully.');
    }
}
