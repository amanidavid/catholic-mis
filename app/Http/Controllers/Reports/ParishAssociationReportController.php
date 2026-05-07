<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Concerns\ResolvesOutstationScope;
use App\Http\Controllers\Concerns\ResolvesSingleParishContext;
use App\Http\Controllers\Controller;
use App\Http\Resources\Kitume\ParishAssociationReportRowResource;
use App\Models\Structure\Outstation;
use App\Models\Structure\Parish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ParishAssociationReportController extends Controller
{
    use ResolvesOutstationScope;
    use ResolvesSingleParishContext;

    private const VIEW_ALL_PERMISSION = 'parish-associations.view-all';

    private function fullKitumeAccess(Request $request): bool
    {
        $user = $request->user();

        return (bool) (
            $user?->can(self::VIEW_ALL_PERMISSION)
            || $user?->can('permissions.manage')
        );
    }

    private function scopedAssociationIds(Request $request): ?array
    {
        if ($this->fullKitumeAccess($request)) {
            return null;
        }

        $memberId = (int) ($request->user()?->member_id ?? 0);
        if ($memberId <= 0) {
            return [];
        }

        return DB::table('parish_association_leaderships')
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->distinct()
            ->pluck('parish_association_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('reports.associations.view'), 403);

        $parishId = $this->resolveCurrentParishId($request->user());
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'outstation_uuid' => ['nullable', 'uuid'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim($validated['q']) : '';
        $like = $q !== '' ? addcslashes($q, '%_\\').'%' : null;
        $outstationUuid = is_string($validated['outstation_uuid'] ?? null) ? trim((string) $validated['outstation_uuid']) : '';
        $outstationId = (int) ($this->resolveOutstationIdForParish($parishId, $outstationUuid) ?? 0);
        $scopedAssociationIds = $this->scopedAssociationIds($request);
        $today = now()->toDateString();

        $memberCounts = DB::table('parish_association_members as pam')
            ->join('members', 'members.id', '=', 'pam.member_id')
            ->join('jumuiyas', 'jumuiyas.id', '=', 'members.jumuiya_id')
            ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
            ->select('pam.parish_association_id', DB::raw('COUNT(pam.id) as total_members'))
            ->where('pam.is_active', true)
            ->when($outstationId > 0, fn ($query) => $query->where('zones.outstation_id', $outstationId))
            ->where(function ($q) use ($today) {
                $q->whereNull('pam.end_date')->orWhereDate('pam.end_date', '>=', $today);
            })
            ->groupBy('pam.parish_association_id');

        $leaderCounts = DB::table('parish_association_leaderships as pal')
            ->join('members', 'members.id', '=', 'pal.member_id')
            ->join('jumuiyas', 'jumuiyas.id', '=', 'members.jumuiya_id')
            ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
            ->select('pal.parish_association_id', DB::raw('COUNT(pal.id) as total_leaders'))
            ->where('pal.is_active', true)
            ->when($outstationId > 0, fn ($query) => $query->where('zones.outstation_id', $outstationId))
            ->where(function ($q) use ($today) {
                $q->whereNull('pal.end_date')->orWhereDate('pal.end_date', '>=', $today);
            })
            ->groupBy('pal.parish_association_id');

        $genderCounts = DB::table('parish_association_members as pam')
            ->join('members', 'members.id', '=', 'pam.member_id')
            ->join('jumuiyas', 'jumuiyas.id', '=', 'members.jumuiya_id')
            ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
            ->select(
                'pam.parish_association_id',
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(members.gender, '')) = 'male' THEN 1 ELSE 0 END) as men"),
                DB::raw("SUM(CASE WHEN LOWER(COALESCE(members.gender, '')) = 'female' THEN 1 ELSE 0 END) as women"),
                DB::raw('COUNT(DISTINCT zones.outstation_id) as outstations')
            )
            ->where('pam.is_active', true)
            ->when($outstationId > 0, fn ($query) => $query->where('zones.outstation_id', $outstationId))
            ->where(function ($q) use ($today) {
                $q->whereNull('pam.end_date')->orWhereDate('pam.end_date', '>=', $today);
            })
            ->groupBy('pam.parish_association_id');

        $rows = DB::table('parish_associations as pa')
            ->leftJoinSub($memberCounts, 'mc', fn ($join) => $join->on('mc.parish_association_id', '=', 'pa.id'))
            ->leftJoinSub($leaderCounts, 'lc', fn ($join) => $join->on('lc.parish_association_id', '=', 'pa.id'))
            ->leftJoinSub($genderCounts, 'gc', fn ($join) => $join->on('gc.parish_association_id', '=', 'pa.id'))
            ->where('pa.parish_id', $parishId)
            ->when(is_array($scopedAssociationIds), fn ($query) => $query->whereIn('pa.id', $scopedAssociationIds ?: [0]))
            ->when($outstationId > 0, function ($query) use ($outstationId, $today) {
                $query->whereExists(function ($sub) use ($outstationId, $today) {
                    $sub->select(DB::raw(1))
                        ->from('parish_association_members as pamx')
                        ->join('members', 'members.id', '=', 'pamx.member_id')
                        ->join('jumuiyas', 'jumuiyas.id', '=', 'members.jumuiya_id')
                        ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                        ->whereColumn('pamx.parish_association_id', 'pa.id')
                        ->where('pamx.is_active', true)
                        ->where('zones.outstation_id', $outstationId)
                        ->where(function ($nested) use ($today) {
                            $nested->whereNull('pamx.end_date')->orWhereDate('pamx.end_date', '>=', $today);
                        });
                });
            })
            ->when($like, fn ($query) => $query->where('pa.name', 'like', $like))
            ->orderBy('pa.sort_order')
            ->orderBy('pa.name_normalized')
            ->select([
                'pa.uuid',
                'pa.name',
                'pa.code',
                'pa.description',
                'pa.is_active',
                DB::raw('COALESCE(mc.total_members, 0) as total_members'),
                DB::raw('COALESCE(lc.total_leaders, 0) as total_leaders'),
                DB::raw('COALESCE(gc.men, 0) as men'),
                DB::raw('COALESCE(gc.women, 0) as women'),
                DB::raw('COALESCE(gc.outstations, 0) as outstations'),
            ])
            ->get();

        $outstations = Outstation::query()
            ->where('parish_id', $parishId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['uuid', 'name']);

        return Inertia::render('Reports/Associations/Index', [
            'filters' => [
                'q' => $q,
                'outstation_uuid' => $outstationUuid !== '' ? $outstationUuid : null,
            ],
            'outstations' => $outstations,
            'rows' => ParishAssociationReportRowResource::collection($rows),
        ]);
    }
}
