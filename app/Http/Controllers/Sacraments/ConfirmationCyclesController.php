<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Models\Sacraments\SacramentProgramCycle;
use App\Services\Sacraments\SacramentWorkflowEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ConfirmationCyclesController extends Controller
{
    private function canGlobalOverride(?\App\Models\User $user): bool
    {
        return (bool) ($user?->can('users.manage')
            || $user?->can('permissions.manage')
            || $user?->can('sacraments.cycle.override'));
    }

    private function resolveParishId(?\App\Models\User $user): int
    {
        $parishId = (int) ($user?->parish_id ?? 0);
        if ($parishId > 0) {
            return $parishId;
        }

        return (int) (DB::table('parishes')->orderBy('id')->value('id') ?? 0);
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmations.cycles.manage')) {
            abort(403);
        }

        $parishId = $this->resolveParishId($user);
        if ($parishId <= 0 && ! $this->canGlobalOverride($user)) {
            abort(403, 'Missing parish context.');
        }

        $cycles = SacramentProgramCycle::query()
            ->where('program', SacramentProgramCycle::PROGRAM_CONFIRMATION)
            ->when(! $this->canGlobalOverride($user), fn ($q) => $q->where('parish_id', $parishId))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Sacraments/Confirmations/Cycles/Index', [
            'cycles' => $cycles,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmations.cycles.manage')) {
            abort(403);
        }

        return Inertia::render('Sacraments/Confirmations/Cycles/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmations.cycles.manage')) {
            abort(403);
        }

        $parishId = $this->resolveParishId($user);
        if ($parishId <= 0 && ! $this->canGlobalOverride($user)) {
            abort(403, 'Missing parish context.');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:190'],
            'registration_opens_at' => ['nullable', 'date_format:Y-m-d'],
            'registration_closes_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:registration_opens_at'],
            'late_registration_closes_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:registration_closes_at'],
            'status' => ['required', 'string', 'in:draft,open,closed,archived'],
        ]);

        $validator->sometimes(
            ['registration_opens_at', 'registration_closes_at', 'late_registration_closes_at'],
            ['after_or_equal:today'],
            fn ($input) => (($input->status ?? null) === SacramentProgramCycle::STATUS_OPEN)
        );

        $validated = $validator->validate();

        $cycle = SacramentProgramCycle::query()->create([
            'uuid' => null,
            'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            'parish_id' => $parishId,
            'name' => trim((string) $validated['name']),
            'registration_opens_at' => $validated['registration_opens_at'] ?? null,
            'registration_closes_at' => $validated['registration_closes_at'] ?? null,
            'late_registration_closes_at' => $validated['late_registration_closes_at'] ?? null,
            'status' => (string) $validated['status'],
            'created_by_user_id' => (int) $user->id,
        ]);

        app(SacramentWorkflowEventService::class)->record(
            $request,
            (int) $cycle->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_CYCLE,
            (int) $cycle->id,
            'cycle_create',
            null,
            (string) ($cycle->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
                'name' => (string) ($cycle->name ?? ''),
            ]
        );

        return redirect()->route('confirmations.cycles.index')->with('success', 'Cycle created.');
    }

    public function edit(Request $request, SacramentProgramCycle $cycle): Response
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmations.cycles.manage')) {
            abort(403);
        }

        if (($cycle->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $parishId = $this->resolveParishId($user);
        if (! $this->canGlobalOverride($user) && ($parishId <= 0 || (int) $cycle->parish_id !== $parishId)) {
            abort(403);
        }

        return Inertia::render('Sacraments/Confirmations/Cycles/Edit', [
            'cycle' => $cycle,
        ]);
    }

    public function update(Request $request, SacramentProgramCycle $cycle): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmations.cycles.manage')) {
            abort(403);
        }

        if (($cycle->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $parishId = $this->resolveParishId($user);
        if (! $this->canGlobalOverride($user) && ($parishId <= 0 || (int) $cycle->parish_id !== $parishId)) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:190'],
            'registration_opens_at' => ['nullable', 'date_format:Y-m-d'],
            'registration_closes_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:registration_opens_at'],
            'late_registration_closes_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:registration_closes_at'],
            'status' => ['required', 'string', 'in:draft,open,closed,archived'],
        ]);

        $validator->sometimes(
            ['registration_opens_at', 'registration_closes_at', 'late_registration_closes_at'],
            ['after_or_equal:today'],
            fn ($input) => (($input->status ?? null) === SacramentProgramCycle::STATUS_OPEN)
        );

        $validated = $validator->validate();

        $fromStatus = (string) ($cycle->status ?? '');

        $cycle->forceFill([
            'name' => trim((string) $validated['name']),
            'registration_opens_at' => $validated['registration_opens_at'] ?? null,
            'registration_closes_at' => $validated['registration_closes_at'] ?? null,
            'late_registration_closes_at' => $validated['late_registration_closes_at'] ?? null,
            'status' => (string) $validated['status'],
        ])->save();

        app(SacramentWorkflowEventService::class)->record(
            $request,
            (int) $cycle->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_CYCLE,
            (int) $cycle->id,
            'cycle_update',
            $fromStatus,
            (string) ($cycle->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
                'name' => (string) ($cycle->name ?? ''),
            ]
        );

        return back()->with('success', 'Cycle updated.');
    }

    public function setStatus(Request $request, SacramentProgramCycle $cycle): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->can('confirmations.cycles.manage')) {
            abort(403);
        }

        if (($cycle->program ?? null) !== SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            abort(404);
        }

        $parishId = $this->resolveParishId($user);
        if (! $this->canGlobalOverride($user) && ($parishId <= 0 || (int) $cycle->parish_id !== $parishId)) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:draft,open,closed,archived'],
        ]);

        if (($validated['status'] ?? null) === SacramentProgramCycle::STATUS_OPEN) {
            $today = now()->toDateString();
            $opens = $cycle->registration_opens_at?->toDateString();
            $closes = $cycle->registration_closes_at?->toDateString();
            $late = $cycle->late_registration_closes_at?->toDateString();

            if (($opens && $opens < $today) || ($closes && $closes < $today) || ($late && $late < $today)) {
                return back()->withErrors([
                    'status' => 'Cannot set status to open when the registration window dates are in the past.',
                ]);
            }
        }

        $fromStatus = (string) ($cycle->status ?? '');
        $cycle->forceFill(['status' => (string) $validated['status']])->save();

        app(SacramentWorkflowEventService::class)->record(
            $request,
            (int) $cycle->parish_id,
            SacramentWorkflowEventService::ENTITY_PROGRAM_CYCLE,
            (int) $cycle->id,
            'cycle_status',
            $fromStatus,
            (string) ($cycle->status ?? null),
            [
                'program' => SacramentProgramCycle::PROGRAM_CONFIRMATION,
            ]
        );

        return back()->with('success', 'Cycle status updated.');
    }
}
