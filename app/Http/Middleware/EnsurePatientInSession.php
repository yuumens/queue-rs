<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePatientInSession
{
    /**
     * Handle an incoming request.
     *
     * Ensure that a patient_id exists in the session before allowing access
     * to queue-related routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('patient_id')) {
            return redirect('/')->with('error', 'Silakan pilih atau daftarkan pasien terlebih dahulu.');
        }

        return $next($request);
    }
}
