<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustChangePassword
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $mustChange = false;
        if (property_exists($user, 'must_change_password') || array_key_exists('must_change_password', $user->getAttributes())) {
            $mustChange = (bool) $user->must_change_password;
        }

        if (! $mustChange) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $allowed = [
            'profile.edit',
            'profile.update',
            'password.update',
            'logout',
        ];

        if (is_string($routeName) && in_array($routeName, $allowed, true)) {
            return $next($request);
        }

        if ($request->isMethod('get')) {
            return redirect()->route('profile.edit')->with('error', 'Please update your password to continue.');
        }

        return back()->with('error', 'Please update your password to continue.');
    }
}
