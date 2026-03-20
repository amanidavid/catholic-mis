<?php

namespace App\Http\Controllers;

use App\Models\Structure\Parish;
use App\Models\Sacraments\SacramentProgramCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * @return array<string, int>
     */
    protected function statusCounts(string $table, int $parishId, Carbon $fromDt, Carbon $toDt, array $extraWhere = []): array
    {
        $query = DB::table($table)
            ->select('status', DB::raw('COUNT(*) as c'))
            ->where('parish_id', $parishId)
            ->whereBetween('created_at', [$fromDt, $toDt]);

        foreach ($extraWhere as $col => $val) {
            $query->where($col, $val);
        }

        /** @var array<string, string|int|float|null> $raw */
        $raw = $query
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $out = [];
        foreach ($raw as $k => $v) {
            $out[(string) $k] = (int) $v;
        }

        return $out;
    }

    protected function resolveParishId(): int
    {
        $user = Auth::user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId <= 0) {
            $parishId = (int) (Parish::query()->orderBy('id')->value('id') ?? 0);
        }

        return $parishId;
    }

    public function index(Request $request): RedirectResponse|Response
    {
        if (! Parish::query()->exists()) {
            return redirect()->route('setup.index');
        }

        $user = Auth::user();

        if (! $user) {
            return Inertia::render('NoPermissions');
        }

        $parishId = $this->resolveParishId();

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = isset($validated['from']) && is_string($validated['from']) ? trim($validated['from']) : '';
        $to = isset($validated['to']) && is_string($validated['to']) ? trim($validated['to']) : '';

        $fromDt = null;
        $toDt = null;
        try {
            $fromDt = $from !== '' ? Carbon::parse($from)->startOfDay() : now()->startOfMonth()->startOfDay();
        } catch (\Throwable) {
            $fromDt = now()->startOfMonth()->startOfDay();
        }
        try {
            $toDt = $to !== '' ? Carbon::parse($to)->endOfDay() : now()->endOfMonth()->endOfDay();
        } catch (\Throwable) {
            $toDt = now()->endOfMonth()->endOfDay();
        }

        if ($fromDt && $toDt && $fromDt->greaterThan($toDt)) {
            [$fromDt, $toDt] = [$toDt->copy()->startOfDay(), $fromDt->copy()->endOfDay()];
        }

        $cards = [];

        if ($user->can('reports.dashboard.view')) {
            $fallbackCommunityHref = $user->can('reports.community.view')
                ? route('reports.community.members-by-jumuiya')
                : route('dashboard');

            $cacheKey = "dashboard:parish:{$parishId}:structure:totals";
            $structure = Cache::remember($cacheKey, 300, function () use ($parishId) {
                $zones = DB::table('zones')
                    ->where('parish_id', $parishId)
                    ->where('is_active', true)
                    ->count();

                $jumuiyas = DB::table('jumuiyas')
                    ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                    ->where('zones.parish_id', $parishId)
                    ->where('zones.is_active', true)
                    ->where('jumuiyas.is_active', true)
                    ->count();

                $families = DB::table('families')
                    ->join('jumuiyas', 'jumuiyas.id', '=', 'families.jumuiya_id')
                    ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                    ->where('zones.parish_id', $parishId)
                    ->where('zones.is_active', true)
                    ->where('jumuiyas.is_active', true)
                    ->where('families.is_active', true)
                    ->count();

                $members = DB::table('members')
                    ->join('families', 'families.id', '=', 'members.family_id')
                    ->join('jumuiyas', 'jumuiyas.id', '=', 'families.jumuiya_id')
                    ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                    ->where('zones.parish_id', $parishId)
                    ->where('zones.is_active', true)
                    ->where('jumuiyas.is_active', true)
                    ->where('families.is_active', true)
                    ->where('members.is_active', true)
                    ->count();

                return [
                    'zones' => $zones,
                    'jumuiyas' => $jumuiyas,
                    'families' => $families,
                    'members' => $members,
                ];
            });

            $cards[] = [
                'key' => 'zones',
                'label' => 'Zones',
                'value' => $structure['zones'] ?? 0,
                'href' => $user->can('zones.view') ? route('zones.index') : $fallbackCommunityHref,
                'can' => true,
            ];
            $cards[] = [
                'key' => 'jumuiyas',
                'label' => 'Jumuiyas',
                'value' => $structure['jumuiyas'] ?? 0,
                'href' => $user->can('jumuiyas.view') ? route('jumuiyas.index') : $fallbackCommunityHref,
                'can' => true,
            ];
            $cards[] = [
                'key' => 'families',
                'label' => 'Families',
                'value' => $structure['families'] ?? 0,
                'href' => $user->can('families.view') ? route('families.index') : $fallbackCommunityHref,
                'can' => true,
            ];
            $cards[] = [
                'key' => 'members',
                'label' => 'Members',
                'value' => $structure['members'] ?? 0,
                'href' => $user->can('members.view') ? route('members.index') : $fallbackCommunityHref,
                'can' => true,
            ];
        }

        if ($user->can('baptisms.view')) {
            $scopeKey = $fromDt->toDateString().':'.$toDt->toDateString();
            $byStatus = Cache::remember("dashboard:parish:{$parishId}:baptisms:by_status:{$scopeKey}", 300, function () use ($parishId, $fromDt, $toDt) {
                return $this->statusCounts('baptisms', $parishId, $fromDt, $toDt);
            });

            $cards[] = [
                'key' => 'baptisms',
                'label' => 'Baptisms',
                'value' => array_sum($byStatus),
                'breakdown' => $byStatus,
                'href' => route('baptisms.index'),
                'can' => true,
            ];
        }

        if ($user->can('marriages.view')) {
            $scopeKey = $fromDt->toDateString().':'.$toDt->toDateString();
            $byStatus = Cache::remember("dashboard:parish:{$parishId}:marriages:by_status:{$scopeKey}", 300, function () use ($parishId, $fromDt, $toDt) {
                return $this->statusCounts('marriages', $parishId, $fromDt, $toDt);
            });

            $cards[] = [
                'key' => 'marriages',
                'label' => 'Marriages',
                'value' => array_sum($byStatus),
                'breakdown' => $byStatus,
                'href' => route('marriages.index'),
                'can' => true,
            ];
        }

        if ($user->can('communions.view') || $user->can('communions.parish.view')) {
            $scopeKey = $fromDt->toDateString().':'.$toDt->toDateString();
            $byStatus = Cache::remember("dashboard:parish:{$parishId}:communions:by_status:{$scopeKey}", 300, function () use ($parishId, $fromDt, $toDt) {
                return $this->statusCounts('sacrament_program_registrations', $parishId, $fromDt, $toDt, [
                    'program' => SacramentProgramCycle::PROGRAM_FIRST_COMMUNION,
                ]);
            });

            $cards[] = [
                'key' => 'communions',
                'label' => 'Communions',
                'value' => array_sum($byStatus),
                'breakdown' => $byStatus,
                'href' => route('communions.index'),
                'can' => true,
            ];
        }

        if ($user->can('confirmations.view') || $user->can('confirmations.parish.view')) {
            $scopeKey = $fromDt->toDateString().':'.$toDt->toDateString();
            $byStatus = Cache::remember("dashboard:parish:{$parishId}:confirmations:by_status:{$scopeKey}", 300, function () use ($parishId, $fromDt, $toDt) {
                return $this->statusCounts('sacrament_program_registrations', $parishId, $fromDt, $toDt, [
                    'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
                ]);
            });

            $cards[] = [
                'key' => 'confirmations',
                'label' => 'Confirmations',
                'value' => array_sum($byStatus),
                'breakdown' => $byStatus,
                'href' => route('confirmations.index'),
                'can' => true,
            ];
        }

        $cards = array_values(array_filter($cards, fn ($c) => (bool) ($c['can'] ?? true)));

        if (count($cards) === 0) {
            return Inertia::render('NoPermissions');
        }

        return Inertia::render('Dashboard', [
            'cards' => $cards,
            'filters' => [
                'from' => $fromDt?->toDateString(),
                'to' => $toDt?->toDateString(),
            ],
        ]);
    }
}
