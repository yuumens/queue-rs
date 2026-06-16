<?php

namespace App\Services;

use App\Models\Polyclinic;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class QueueService
{
    /**
     * Issue a new queue number for a patient at a given polyclinic on a given date.
     *
     * Uses a DB transaction with a pessimistic lock (lockForUpdate) on the
     * registrations table to atomically determine the next queue_sequence for
     * the (polyclinic_id, registration_date) combination, ensuring no duplicates
     * even under concurrent requests.
     *
     * Queue number format: "{polyclinic_code}-{sequence_zero_padded_to_2}"
     * Examples: "A-01", "A-02", "B-01"
     *
     * @param  int     $patientId     ID of the patient being registered
     * @param  int     $polyclinicId  ID of the selected polyclinic
     * @param  int     $doctorId      ID of the selected doctor
     * @param  Carbon  $date          The registration date (typically today)
     * @return Registration
     */
    public function issueQueueNumber(
        int $patientId,
        int $polyclinicId,
        int $doctorId,
        Carbon $date
    ): Registration {
        return DB::transaction(function () use ($patientId, $polyclinicId, $doctorId, $date) {
            // Acquire a pessimistic write lock on the latest registration row for
            // this polyclinic on this date. This blocks any concurrent transaction
            // from reading the max sequence until we are done, preventing duplicate
            // queue numbers. Using lockForUpdate() on a SELECT query (max aggregate
            // via a subquery) is the standard Laravel approach for this pattern.
            $currentMax = Registration::where('polyclinic_id', $polyclinicId)
                ->whereDate('registration_date', $date->toDateString())
                ->lockForUpdate()
                ->max('queue_sequence');

            // Increment from the current maximum, defaulting to 1 if no registrations
            // exist yet for this polyclinic today (daily reset behaviour).
            $sequence = ($currentMax ?? 0) + 1;

            // Resolve the polyclinic code for the queue number prefix.
            $polyclinic = Polyclinic::findOrFail($polyclinicId);

            // Format: "{CODE}-{seq}" where seq is zero-padded to at least 2 digits.
            $queueNumber = $polyclinic->code . '-' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);

            return Registration::create([
                'patient_id'        => $patientId,
                'polyclinic_id'     => $polyclinicId,
                'doctor_id'         => $doctorId,
                'queue_number'      => $queueNumber,
                'queue_sequence'    => $sequence,
                'registration_date' => $date->toDateString(),
            ]);
        });
    }
}
