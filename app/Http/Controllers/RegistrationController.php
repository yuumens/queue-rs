<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateNikException;
use App\Http\Requests\StorePatientRequest;
use App\Services\PatientService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly PatientService $patientService,
    ) {}

    /**
     * Show the new patient registration form.
     */
    public function create(): View
    {
        return view('patient.register');
    }

    /**
     * Store a new patient registration.
     */
    public function store(StorePatientRequest $request): RedirectResponse
    {
        try {
            $patient = $this->patientService->createPatient($request->validated());

            session(['patient_id' => $patient->id]);

            return redirect()->route('queue.select');
        } catch (DuplicateNikException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['nik' => $e->getMessage()]);
        } catch (QueryException $e) {
            Log::error('Patient registration failed: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Pendaftaran gagal, silakan coba lagi.']);
        }
    }
}
