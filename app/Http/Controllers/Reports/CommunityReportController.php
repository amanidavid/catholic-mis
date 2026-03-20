<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ParishStaff;
use App\Models\People\Family;
use App\Models\People\Member;
use App\Models\Structure\Jumuiya;
use App\Models\Structure\Parish;
use App\Models\Structure\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CommunityReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function tableFilters(Request $request): array
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
        ]);

        $q = is_string($validated['q'] ?? null) ? trim((string) $validated['q']) : '';
        $perPage = (int) ($validated['per_page'] ?? 50);

        $like = null;
        if ($q !== '') {
            $safe = addcslashes($q, "%_\\");
            $like = $safe.'%';
        }

        return [$q, $perPage, $like];
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

    public function overview(Request $request): Response
    {
        return $this->membersByJumuiya($request);
    }

    public function familiesByJumuiya(Request $request): Response
    {
        return $this->membersByJumuiya($request);
    }

    public function membersByJumuiya(Request $request): Response
    {
        $parishId = $this->resolveParishId();

        $scopedJumuiyaId = null;
        if (! $request->user()?->can('reports.scope.parish') && $request->user()?->can('reports.scope.jumuiya')) {
            $scopedJumuiyaId = (int) ($request->user()?->member?->jumuiya_id ?? 0);
        }

        if ($scopedJumuiyaId !== null && $scopedJumuiyaId <= 0) {
            $scopedJumuiyaId = null;
        }

        [$q, $perPage, $like] = $this->tableFilters($request);

        $page = (int) ($request->input('page') ?? 1);
        $scopeKey = $scopedJumuiyaId ? "jumuiya:{$scopedJumuiyaId}" : 'all';
        $cacheKey = "reports:community:members-by-jumuiya:parish:{$parishId}:scope:{$scopeKey}:q:".sha1((string) $q).":pp:{$perPage}:p:{$page}";

        $cached = Cache::remember($cacheKey, 60, function () use ($parishId, $like, $perPage, $scopedJumuiyaId) {
            $activeFamilyCounts = DB::table('families')
                ->select('families.jumuiya_id', DB::raw('COUNT(families.id) as families'))
                ->join('jumuiyas', 'jumuiyas.id', '=', 'families.jumuiya_id')
                ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                ->where('families.is_active', true)
                ->where('jumuiyas.is_active', true)
                ->where('zones.parish_id', $parishId)
                ->when($scopedJumuiyaId !== null, function ($q) use ($scopedJumuiyaId) {
                    $q->where('families.jumuiya_id', $scopedJumuiyaId);
                })
                ->groupBy('families.jumuiya_id');

            $activeMemberCounts = DB::table('members')
                ->join('families', 'families.id', '=', 'members.family_id')
                ->join('jumuiyas', 'jumuiyas.id', '=', 'families.jumuiya_id')
                ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                ->select('families.jumuiya_id', DB::raw('COUNT(members.id) as members'))
                ->where('members.is_active', true)
                ->where('families.is_active', true)
                ->where('jumuiyas.is_active', true)
                ->where('zones.parish_id', $parishId)
                ->when($scopedJumuiyaId !== null, function ($q) use ($scopedJumuiyaId) {
                    $q->where('families.jumuiya_id', $scopedJumuiyaId);
                })
                ->groupBy('families.jumuiya_id');

            $paginator = DB::table('jumuiyas')
                ->join('zones', 'zones.id', '=', 'jumuiyas.zone_id')
                ->leftJoinSub($activeFamilyCounts, 'fc', function ($j) {
                    $j->on('fc.jumuiya_id', '=', 'jumuiyas.id');
                })
                ->leftJoinSub($activeMemberCounts, 'mc', function ($j) {
                    $j->on('mc.jumuiya_id', '=', 'jumuiyas.id');
                })
                ->where('zones.parish_id', $parishId)
                ->where('zones.is_active', true)
                ->where('jumuiyas.is_active', true)
                ->when($scopedJumuiyaId !== null, function ($q) use ($scopedJumuiyaId) {
                    $q->where('jumuiyas.id', $scopedJumuiyaId);
                })
                ->when($like !== null, function ($qb) use ($like) {
                    $qb->where(function ($q) use ($like) {
                        $q->where('zones.name', 'like', $like)
                            ->orWhere('jumuiyas.name', 'like', $like);
                    });
                })
                ->orderBy('zones.name')
                ->orderBy('jumuiyas.name')
                ->select([
                    'zones.uuid as zone_uuid',
                    'zones.name as zone_name',
                    'jumuiyas.uuid as jumuiya_uuid',
                    'jumuiyas.name as jumuiya_name',
                    DB::raw('COALESCE(fc.families, 0) as families'),
                    DB::raw('COALESCE(mc.members, 0) as members'),
                ])
                ->simplePaginate($perPage)
                ->withQueryString();

            $rows = $paginator->through(function ($r) {
                return [
                    'zone_uuid' => (string) ($r->zone_uuid ?? ''),
                    'zone_name' => (string) ($r->zone_name ?? ''),
                    'jumuiya_uuid' => (string) ($r->jumuiya_uuid ?? ''),
                    'jumuiya_name' => (string) ($r->jumuiya_name ?? ''),
                    'families' => (int) ($r->families ?? 0),
                    'members' => (int) ($r->members ?? 0),
                ];
            })->values();

            return [
                'rows' => $rows,
                'pagination' => [
                    'current_page' => (int) $paginator->currentPage(),
                    'per_page' => (int) $paginator->perPage(),
                    'from' => $paginator->firstItem() ? (int) $paginator->firstItem() : null,
                    'to' => $paginator->lastItem() ? (int) $paginator->lastItem() : null,
                    'has_more' => (bool) $paginator->hasMorePages(),
                ],
            ];
        });

        return Inertia::render('Reports/Community/MembersByJumuiya', [
            'rows' => $cached['rows'],
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
            'pagination' => $cached['pagination'],
        ]);
    }

    public function jumuiyasByZone(Request $request): Response
    {
        return $this->membersByJumuiya($request);
    }

    public function staffSummary(Request $request): Response
    {
        return $this->membersByJumuiya($request);
    }
}
