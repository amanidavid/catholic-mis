<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        $q = $request->query('q');
        $module = $request->query('module');

        $q = is_string($q) ? trim($q) : '';
        $module = is_string($module) ? trim($module) : '';

        $safe = addcslashes($q, '%_\\');
        $safeModule = addcslashes($module, '%_\\');

        $permissionsQuery = Permission::query()
            ->select(['name', 'module', 'display_name', 'description', 'sort_order'])
            ->when($module !== '', fn (Builder $qb) => $qb->where('module', 'like', $safeModule.'%'))
            ->when($q !== '', function (Builder $qb) use ($safe) {
                $qb->where(function (Builder $sub) use ($safe) {
                    $sub->where('name', 'like', $safe.'%')
                        ->orWhere('display_name', 'like', $safe.'%')
                        ->orWhere('module', 'like', $safe.'%');
                });
            })
            ->orderBy('module')
            ->orderBy('display_name')
            ->orderBy('name');

        $permissions = $permissionsQuery->paginate(20)->withQueryString();

        $modules = Permission::query()
            ->select(['module'])
            ->whereNotNull('module')
            ->where('module', '!=', '')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->values();

        return Inertia::render('AccessControl/Permissions/Index', [
            'filters' => [
                'q' => $q,
                'module' => $module,
            ],
            'modules' => $modules,
            'permissions' => $permissions,
        ]);
    }
}
