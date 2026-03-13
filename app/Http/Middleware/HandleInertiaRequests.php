<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user && method_exists($user, 'loadMissing')) {
            $user->loadMissing([
                'roles:id,name',
                'permissions:id,name',
                'roles.permissions:id,name',
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user
                    ? [
                        'name' => $user->name,
                        'email' => $user->email,
                        'permissions' => $user->relationLoaded('roles')
                            ? collect()
                                ->merge($user->permissions?->pluck('name') ?? [])
                                ->merge($user->roles?->flatMap(fn ($r) => $r->permissions?->pluck('name') ?? []) ?? [])
                                ->filter()
                                ->unique()
                                ->values()
                                ->all()
                            : (method_exists($user, 'getAllPermissions')
                                ? $user->getAllPermissions()->pluck('name')->values()->all()
                                : []),
                    ]
                    : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
