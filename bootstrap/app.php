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
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Inertia\Inertia;

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

            return Inertia::render('Error', [
                'status' => 404,
                'title' => 'Page not found',
                'message' => $message,
            ])->toResponse($request)->setStatusCode(404);
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, Request $request) {
            $message = 'Action not allowed';

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message);
            }

            return Inertia::render('Error', [
                'status' => 405,
                'title' => 'Method not allowed',
                'message' => $message,
            ])->toResponse($request)->setStatusCode(405);
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

            return Inertia::render('Error', [
                'status' => 403,
                'title' => 'Forbidden',
                'message' => $message,
            ])->toResponse($request)->setStatusCode(403);
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
                return redirect()
                    ->route('error.show')
                    ->with('error_page', [
                        'status' => 500,
                        'title' => 'Server error',
                        'message' => $message,
                        'reference' => $id,
                    ]);
            }

            return Inertia::render('Error', [
                'status' => 500,
                'title' => 'Server error',
                'message' => $message,
                'reference' => $id,
            ])->toResponse($request)->setStatusCode(500);
        });

        $exceptions->renderable(function (HttpExceptionInterface $e, Request $request) {
            $status = (int) ($e->getStatusCode() ?: 500);

            $title = match ($status) {
                400 => 'Bad request',
                401 => 'Unauthorized',
                403 => 'Forbidden',
                404 => 'Page not found',
                405 => 'Method not allowed',
                419 => 'Session expired',
                422 => 'Unprocessable request',
                429 => 'Too many requests',
                503 => 'Service unavailable',
                default => 'Error',
            };

            $message = match ($status) {
                400 => 'The request could not be processed.',
                401 => 'Please login to continue.',
                403 => 'You are not allowed to do this.',
                404 => 'Page not found.',
                405 => 'Action not allowed.',
                419 => 'Session expired. Please login again.',
                422 => 'The request could not be processed.',
                429 => 'Too many requests. Please try again later.',
                503 => 'Service is temporarily unavailable. Please try again later.',
                default => 'An error occurred. Please try again.',
            };

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $status);
            }

            if ($request->header('X-Inertia')) {
                return back()->with('error', $message);
            }

            return Inertia::render('Error', [
                'status' => $status,
                'title' => $title,
                'message' => $message,
            ])->toResponse($request)->setStatusCode($status);
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
                return redirect()
                    ->route('error.show')
                    ->with('error_page', [
                        'status' => 500,
                        'title' => 'Server error',
                        'message' => 'An unexpected error occurred.',
                        'reference' => $id,
                    ]);
            }

            return Inertia::render('Error', [
                'status' => 500,
                'title' => 'Server error',
                'message' => 'An unexpected error occurred.',
                'reference' => $id,
            ])->toResponse($request)->setStatusCode(500);
        });
    })->create();
