<?php

namespace App\Http\Controllers;

use App\Http\Requests\Setup\StoreSetupRequest;
use App\Services\Setup\SetupService;
use Illuminate\Http\RedirectResponse;
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
        $state = $this->setupService->getCurrentSetupState();

        return Inertia::render('Setup/Index', $state);
    }

    public function store(StoreSetupRequest $request): RedirectResponse
    {
        $this->setupService->saveSetup($request->validated());

        return redirect()->route('dashboard')->with('success', 'Setup saved.');
    }
}
