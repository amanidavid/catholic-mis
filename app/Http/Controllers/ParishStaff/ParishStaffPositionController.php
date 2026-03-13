<?php

namespace App\Http\Controllers\ParishStaff;

use App\Http\Controllers\Controller;
use App\Http\Requests\ParishStaff\StoreParishStaffPositionRequest;
use App\Http\Requests\ParishStaff\UpdateParishStaffPositionRequest;
use App\Models\ParishStaffAssignment;
use App\Models\ParishStaffAssignmentRole;
use App\Models\Structure\Parish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ParishStaffPositionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function currentParishId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        if (! empty($user->parish_id)) {
            return (int) $user->parish_id;
        }

        if ($user->can('permissions.manage')) {
            return Parish::query()->orderBy('id')->value('id');
        }

        return null;
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ParishStaffAssignmentRole::class);

        $parishId = $this->currentParishId($request);
        if (! $parishId) {
            return Inertia::render('ParishStaff/Positions/Index', [
                'positions' => [],
            ]);
        }

        $positions = ParishStaffAssignmentRole::query()
            ->select(['uuid', 'name', 'is_active'])
            ->where('parish_id', $parishId)
            ->orderBy('name')
            ->get();

        return Inertia::render('ParishStaff/Positions/Index', [
            'positions' => $positions,
        ]);
    }

    public function store(StoreParishStaffPositionRequest $request): RedirectResponse
    {
        $this->authorize('create', ParishStaffAssignmentRole::class);

        $parishId = $this->currentParishId($request);
        if (! $parishId) {
            return back()->with('error', 'Invalid parish context.');
        }

        $validated = $request->validated();

        try {
            $name = trim($validated['name']);
            $nameKey = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags($name)) ?? ''), 'UTF-8');

            $exists = ParishStaffAssignmentRole::query()
                ->where('parish_id', $parishId)
                ->where('name_key', $nameKey)
                ->exists();

            if ($exists) {
                return back()->with('error', 'Position name already exists.');
            }

            ParishStaffAssignmentRole::query()->create([
                'parish_id' => $parishId,
                'name' => $name,
                'name_key' => $nameKey,
                'is_active' => true,
            ]);

            return back()->with('success', 'Position created.');
        } catch (\Throwable $e) {
            Log::error('Staff position create failed', ['exception' => $e]);
            return back()->with('error', 'Unable to create position. Please try again.');
        }
    }

    public function update(UpdateParishStaffPositionRequest $request, ParishStaffAssignmentRole $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $role->parish_id !== $parishId) {
            return back()->with('error', 'Invalid position.');
        }

        $validated = $request->validated();

        if (array_key_exists('is_active', $validated) && ((bool) $validated['is_active']) === false) {
            $inUse = ParishStaffAssignment::query()
                ->where('parish_staff_assignment_role_id', $role->id)
                ->exists();

            if ($inUse) {
                return back()->with('error', 'Unable to deactivate. This position has assignment history.');
            }
        }

        try {
            if (array_key_exists('name', $validated) && $validated['name'] !== null) {
                $name = trim((string) $validated['name']);
                $nameKey = mb_strtolower(trim(preg_replace('/\s+/u', ' ', strip_tags($name)) ?? ''), 'UTF-8');

                $exists = ParishStaffAssignmentRole::query()
                    ->where('parish_id', $role->parish_id)
                    ->where('id', '!=', $role->id)
                    ->where('name_key', $nameKey)
                    ->exists();

                if ($exists) {
                    return back()->with('error', 'Position name already exists.');
                }

                $role->name = $name;
                $role->name_key = $nameKey;
            }

            if (array_key_exists('is_active', $validated) && $validated['is_active'] !== null) {
                $role->is_active = (bool) $validated['is_active'];
            }

            $role->save();

            return back()->with('success', 'Position updated.');
        } catch (\Throwable $e) {
            Log::error('Staff position update failed', ['exception' => $e, 'position_uuid' => $role->uuid]);
            return back()->with('error', 'Unable to update position. Please try again.');
        }
    }

    public function destroy(Request $request, ParishStaffAssignmentRole $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $parishId = $this->currentParishId($request);
        if ($parishId && (int) $role->parish_id !== $parishId) {
            return back()->with('error', 'Invalid position.');
        }

        $inUse = ParishStaffAssignment::query()
            ->where('parish_staff_assignment_role_id', $role->id)
            ->exists();

        if ($inUse) {
            return back()->with('error', 'Unable to delete. This position has assignment history. Deactivate it instead.');
        }

        try {
            $role->delete();
            return back()->with('success', 'Position deleted.');
        } catch (\Throwable $e) {
            Log::error('Staff position delete failed', ['exception' => $e, 'position_uuid' => $role->uuid]);
            return back()->with('error', 'Unable to delete position. Please try again.');
        }
    }
}
