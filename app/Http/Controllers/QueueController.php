<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {}

    /**
     * Show polyclinic and doctor selection page.
     */
    public function select(): View
    {
        return view('queue.select');
    }

    /**
     * Generate a queue number for the patient.
     */
    public function generate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'polyclinic_id' => 'required|exists:polyclinics,id',
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $patientId = session('patient_id');

        if (!$patientId) {
            return redirect()->route('home');
        }

        try {
            $registration = $this->queueService->issueQueueNumber(
                (int) $patientId,
                (int) $validated['polyclinic_id'],
                (int) $validated['doctor_id'],
                Carbon::now()
            );

            // Clear polyclinic/doctor selection from session but keep patient_id
            $request->session()->forget(['polyclinic_id', 'doctor_id']);

            return redirect()->route('queue.ticket', $registration);
        } catch (QueryException $e) {
            Log::error('Queue generation failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Pendaftaran antrian gagal. Silakan coba lagi.');
        }
    }

    /**
     * Show the queue ticket.
     */
    public function ticket(Registration $registration): View
    {
        $registration->load(['patient', 'polyclinic', 'doctor']);

        return view('queue.ticket', compact('registration'));
    }
}
