<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            // \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\MustChangePassword::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request) {
            $message = 'Page not found';

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message);
            }

            return response($message, 404);
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            $message = 'Action not allowed';

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message);
            }

            return response($message, 405);
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            $message = 'Please login to continue.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 401);
            }

            return redirect()->route('login')->with('error', $message);
        });

        $exceptions->renderable(function (AuthorizationException $e, Request $request) {
            $message = 'You are not allowed to do this.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message);
            }

            return response($message, 403);
        });

        $exceptions->renderable(function (TokenMismatchException $e, Request $request) {
            $message = 'Session expired. Please login again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            return redirect()->route('login')->with('error', $message);
        });

        $exceptions->renderable(function (QueryException $e, Request $request) {
            $id = (string) Str::uuid();
            logger()->error('Database query error', [
                'error_id' => $id,
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'url' => $request->fullUrl(),
            ]);

            $message = "A database error occurred.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message);
            }

            return response($message, 500);
        });

        $exceptions->renderable(function (\Throwable $e, Request $request) {
            if ($e instanceof ValidationException) {
                return null;
            }

            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return null;
            }

            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return null;
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            $id = (string) Str::uuid();
            logger()->error('Unhandled application error', [
                'error_id' => $id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);

            $message = "An unexpected error occurred. reference $id";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message);
            }

            return response($message, 500);
        });
    })->create();
