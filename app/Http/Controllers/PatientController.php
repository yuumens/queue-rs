<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPatientRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PatientController extends Controller
{
    /**
     * Show the main menu / welcome page.
     */
    public function index(): View
    {
        return view('welcome');
    }

    /**
     * Show the returning patient verification page (Livewire host).
     */
    public function verify(): View
    {
        return view('patient.verify');
    }

    /**
     * Confirm the selected patient and store in session.
     */
    public function confirm(ConfirmPatientRequest $request): RedirectResponse
    {
        session(['patient_id' => $request->validated()['patient_id']]);

        return redirect()->route('queue.select');
    }
}
