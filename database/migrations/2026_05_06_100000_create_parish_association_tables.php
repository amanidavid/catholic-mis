<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Traits\NormalizesNames;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parish_associations')) {
            Schema::create('parish_associations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('parish_id')->constrained('parishes', 'id', 'fk_pa_parish')->onDelete('restrict');
                $table->string('name');
                $table->string('name_normalized', 255);
                $table->string('code', 50)->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['parish_id', 'name_normalized'], 'uq_pa_parish_name_norm');
                $table->index(['parish_id', 'is_active', 'sort_order', 'name_normalized'], 'idx_pa_parish_active_sort_name');
            });
        }

        if (! Schema::hasTable('parish_association_leader_roles')) {
            Schema::create('parish_association_leader_roles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('parish_id')->constrained('parishes', 'id', 'fk_palr_parish')->onDelete('restrict');
                $table->string('name');
                $table->string('name_normalized', 255);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['parish_id', 'name_normalized'], 'uq_palr_parish_name_norm');
                $table->index(['parish_id', 'is_active', 'sort_order', 'name_normalized'], 'idx_palr_parish_active_sort_name');
            });
        }

        if (! Schema::hasTable('parish_association_members')) {
            Schema::create('parish_association_members', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('parish_association_id')->constrained('parish_associations', 'id', 'fk_pam_assoc')->onDelete('cascade');
                $table->foreignId('member_id')->constrained('members', 'id', 'fk_pam_member')->onDelete('restrict');
                $table->date('joined_at')->nullable();
                $table->date('end_date')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['parish_association_id', 'member_id'], 'uq_association_members_association_member');
                $table->index(['parish_association_id', 'is_active', 'end_date'], 'idx_association_members_assoc_active_end');
                $table->index(['member_id', 'is_active', 'end_date'], 'idx_association_members_member_active_end');
            });
        }

        if (! Schema::hasTable('parish_association_leaderships')) {
            Schema::create('parish_association_leaderships', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('parish_association_id')->constrained('parish_associations', 'id', 'fk_pal_assoc')->onDelete('cascade');
                $table->foreignId('member_id')->constrained('members', 'id', 'fk_pal_member')->onDelete('restrict');
                $table->foreignId('parish_association_leader_role_id')
                    ->constrained('parish_association_leader_roles', 'id', 'fk_pal_role')
                    ->onDelete('restrict');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['parish_association_id', 'is_active', 'end_date'], 'idx_association_leaderships_assoc_active_end');
                $table->index(['member_id', 'is_active', 'end_date'], 'idx_association_leaderships_member_active_end');
                $table->index('parish_association_leader_role_id', 'idx_association_leaderships_role_id');
            });
        }

        $parishIds = DB::table('parishes')->pluck('id');

        $defaultAssociations = [
            [
                'name' => 'UWAKA',
                'code' => 'UWAKA',
                'description' => 'Umoja wa Wanaume Wakatoliki unaolenga kuimarisha imani, malezi ya kifamilia, na huduma kwa jamii.',
            ],
            [
                'name' => 'WAWATA',
                'code' => 'WAWATA',
                'description' => 'Wanawake Wakatoliki Tanzania wanaojikita katika imani, malezi ya kifamilia, na huduma kwa jamii.',
            ],
            [
                'name' => 'VIWAWA',
                'code' => 'VIWAWA',
                'description' => 'Vijana Wakatoliki walioko nje ya mfumo wa shule wanaoshiriki shughuli za kiroho na maendeleo ya kijamii.',
            ],
            [
                'name' => 'Utume wa Fatima',
                'code' => 'FATIMA',
                'description' => 'Utume unaohamasisha sala, hasa Rozari, toba, na kuishi ujumbe wa Bikira Maria wa Fatima.',
            ],
            [
                'name' => 'Moyo Mtakatifu wa Yesu',
                'code' => 'MMY',
                'description' => 'Chama kinachokuza ibada kwa Moyo Mtakatifu wa Yesu, upendo, na kujitoa kwa Kristo.',
            ],
            [
                'name' => 'Mt. Augustino',
                'code' => 'AUGUSTINO',
                'description' => 'Kundi linalosisitiza umoja, upendo, na kutafuta ukweli wa Mungu kwa roho ya Mt. Augustino.',
            ],
            [
                'name' => 'CPT',
                'code' => 'CPT',
                'description' => 'Christian Professionals of Tanzania wanaoishi imani yao katika taaluma na maendeleo ya jamii.',
            ],
            [
                'name' => 'Karismatic',
                'code' => 'KARISMATIC',
                'description' => 'Catholic Charismatic Renewal unaosisitiza vipaji vya Roho Mtakatifu, maombi ya nguvu, sifa, na ibada hai.',
            ],
            [
                'name' => 'Marafiki wa Shirika la Roho Mtakatifu',
                'code' => 'SPIRITANS',
                'description' => 'Washirika wanaosaidia kazi za kimisioni na kijamii za Congregation of the Holy Spirit.',
            ],
        ];

        $defaultLeaderRoles = [
            'Mwenyekiti',
            'Makamu Mwenyekiti',
            'Katibu',
            'Katibu Msaidizi',
            'Mhazini',
            'Mratibu',
            'Mshauri wa Kiroho',
        ];

        foreach ($parishIds as $parishId) {
            foreach ($defaultAssociations as $index => $association) {
                $exists = DB::table('parish_associations')
                    ->where('parish_id', $parishId)
                    ->where('name', $association['name'])
                    ->exists();

                if (! $exists) {
                    DB::table('parish_associations')->insert([
                        'uuid' => (string) Str::uuid(),
                        'parish_id' => $parishId,
                        'name' => $association['name'],
                        'name_normalized' => mb_strtolower((string) (NormalizesNames::normalize($association['name']) ?? $association['name']), 'UTF-8'),
                        'code' => $association['code'],
                        'description' => $association['description'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            foreach ($defaultLeaderRoles as $index => $roleName) {
                $exists = DB::table('parish_association_leader_roles')
                    ->where('parish_id', $parishId)
                    ->where('name', $roleName)
                    ->exists();

                if (! $exists) {
                    DB::table('parish_association_leader_roles')->insert([
                        'uuid' => (string) Str::uuid(),
                        'parish_id' => $parishId,
                        'name' => $roleName,
                        'name_normalized' => mb_strtolower((string) (NormalizesNames::normalize($roleName) ?? $roleName), 'UTF-8'),
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_association_leaderships');
        Schema::dropIfExists('parish_association_members');
        Schema::dropIfExists('parish_association_leader_roles');
        Schema::dropIfExists('parish_associations');
    }
};
