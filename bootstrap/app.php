<?php

use App\Exceptions\DuplicateNikException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'patient.session' => \App\Http\Middleware\EnsurePatientInSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (DuplicateNikException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'NIK sudah terdaftar dalam sistem.'], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['nik' => 'NIK sudah terdaftar dalam sistem.']);
        });

        $exceptions->renderable(function (QueryException $e, Request $request) {
            if (config('app.debug')) {
                return null; // Let Laravel's default handler show details in debug mode
            }

            Log::error('Database error occurred', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Terjadi kesalahan pada sistem. Silakan coba lagi.',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Terjadi kesalahan pada sistem. Silakan coba lagi.']);
        });
    })->create();
