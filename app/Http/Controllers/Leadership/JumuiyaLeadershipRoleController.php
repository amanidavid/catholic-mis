<?php

namespace App\Http\Controllers\Leadership;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leadership\StoreJumuiyaLeadershipRoleRequest;
use App\Http\Requests\Leadership\UpdateJumuiyaLeadershipRoleRequest;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\Leadership\JumuiyaLeadershipRole;
use App\Http\Resources\Leadership\JumuiyaLeadershipRoleResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class JumuiyaLeadershipRoleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', JumuiyaLeadershipRole::class);

        $activeOnly = filter_var($request->query('active_only', false), FILTER_VALIDATE_BOOL);

        $roles = JumuiyaLeadershipRole::query()
            ->when($activeOnly, fn ($qb) => $qb->where('is_active', true))
            ->orderBy('name')
            ->get();

        if ($request->header('X-Inertia')) {
            return Inertia::render('Leadership/Roles/Index', [
                'filters' => [
                    'active_only' => $activeOnly,
                ],
                'roles' => JumuiyaLeadershipRoleResource::collection($roles),
            ]);
        }

        return JumuiyaLeadershipRoleResource::collection($roles);
    }

    public function store(StoreJumuiyaLeadershipRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', JumuiyaLeadershipRole::class);

        $validated = $request->validated();

        try {
            $name = trim($validated['name']);

            $exists = JumuiyaLeadershipRole::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists();

            if ($exists) {
                return back()->with('error', 'Role name already exists.');
            }

            JumuiyaLeadershipRole::create([
                'name' => $name,
                'system_role_name' => $validated['system_role_name'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            return back()->with('success', 'Leadership role created.');
        } catch (\Throwable $e) {
            Log::error('Leadership role create failed', ['exception' => $e]);
            return back()->with('error', 'Unable to create leadership role. Please try again.');
        }
    }

    public function update(UpdateJumuiyaLeadershipRoleRequest $request, JumuiyaLeadershipRole $jumuiyaLeadershipRole): RedirectResponse
    {
        $this->authorize('update', $jumuiyaLeadershipRole);

        $validated = $request->validated();

        if (array_key_exists('is_active', $validated) && ((bool) $validated['is_active']) === false) {
            $inUse = JumuiyaLeadership::query()
                ->where('jumuiya_leadership_role_id', $jumuiyaLeadershipRole->id)
                ->exists();

            if ($inUse) {
                return back()->with('error', 'Unable to deactivate role. This role has leadership history.');
            }
        }

        try {
            if (array_key_exists('name', $validated)) {
                $name = trim((string) $validated['name']);

                $exists = JumuiyaLeadershipRole::query()
                    ->where('id', '!=', $jumuiyaLeadershipRole->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->exists();

                if ($exists) {
                    return back()->with('error', 'Role name already exists.');
                }

                $jumuiyaLeadershipRole->name = $name;
            }

            if (array_key_exists('is_active', $validated)) {
                $jumuiyaLeadershipRole->is_active = (bool) $validated['is_active'];
            }

            if (array_key_exists('system_role_name', $validated)) {
                $jumuiyaLeadershipRole->system_role_name = $validated['system_role_name'] !== null
                    ? trim((string) $validated['system_role_name'])
                    : null;
            }

            $jumuiyaLeadershipRole->save();

            return back()->with('success', 'Leadership role updated.');
        } catch (\Throwable $e) {
            Log::error('Leadership role update failed', [
                'exception' => $e,
                'role_uuid' => $jumuiyaLeadershipRole->uuid,
            ]);

            return back()->with('error', 'Unable to update leadership role. Please try again.');
        }
    }

    public function destroy(JumuiyaLeadershipRole $jumuiyaLeadershipRole): RedirectResponse
    {
        $this->authorize('delete', $jumuiyaLeadershipRole);

        try {
            $inUse = JumuiyaLeadership::query()
                ->where('jumuiya_leadership_role_id', $jumuiyaLeadershipRole->id)
                ->exists();

            if ($inUse) {
                return back()->with('error', 'Unable to delete role. This role has leadership history. Deactivate it instead.');
            }

            $jumuiyaLeadershipRole->delete();

            return back()->with('success', 'Leadership role deleted.');
        } catch (\Throwable $e) {
            Log::error('Leadership role delete failed', [
                'exception' => $e,
                'role_uuid' => $jumuiyaLeadershipRole->uuid,
            ]);

            return back()->with('error', 'Unable to delete leadership role. Please try again.');
        }
    }
}
