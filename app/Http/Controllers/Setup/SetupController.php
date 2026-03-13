<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setup\StoreSetupRequest;
use App\Http\Resources\Structure\DioceseResource;
use App\Http\Resources\Structure\ParishResource;
use App\Models\Structure\Diocese;
use App\Models\Structure\Parish;
use App\Services\Setup\SetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SetupController extends Controller
{
    public function __construct(private readonly SetupService $setupService)
    {
        $this->middleware('auth');
    }

    public function index(): Response
    {
        $this->authorize('viewAny', Diocese::class);

        $diocese = Diocese::query()->orderBy('id')->first();
        $parish = Parish::query()->orderBy('id')->first();

        return Inertia::render('Setup/Index', [
            'diocese' => $diocese ? (new DioceseResource($diocese))->resolve() : null,
            'parish' => $parish ? (new ParishResource($parish))->resolve() : null,
        ]);
    }

    public function store(StoreSetupRequest $request): RedirectResponse
    {
        try {
            $this->setupService->saveSetup($request->validated());

            return redirect()->route('setup.index')->with('success', 'Saved.');
        } catch (\Throwable $e) {
            Log::error('Setup save failed', ['exception' => $e]);

            return back()->with('error', 'Unable to save. Please try again.');
        }
    }
}
