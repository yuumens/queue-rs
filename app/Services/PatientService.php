<?php

namespace App\Services;

use App\Exceptions\DuplicateNikException;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class PatientService
{
    /**
     * Create a new patient record.
     *
     * Checks for a duplicate NIK and throws DuplicateNikException if found.
     * Generates a Medical Record number in the format "RM-NNNNNN" (six zero-padded digits)
     * using MAX(id)+1 inside a DB transaction to handle concurrent inserts safely.
     *
     * @param  array{full_name: string, date_of_birth: string, address: string, nik: string}  $data
     * @return Patient
     *
     * @throws DuplicateNikException
     */
    public function createPatient(array $data): Patient
    {
        // Check for duplicate NIK before entering the transaction so we can
        // surface a friendly error immediately without acquiring a DB lock.
        if (Patient::where('nik', $data['nik'])->exists()) {
            throw new DuplicateNikException($data['nik']);
        }

        return DB::transaction(function () use ($data) {
            // Re-check inside the transaction in case of a race between the
            // outer check above and now (belt-and-suspenders guard).
            if (Patient::where('nik', $data['nik'])->exists()) {
                throw new DuplicateNikException($data['nik']);
            }

            // Generate MR number: "RM-" + 6-digit zero-padded (MAX(id) + 1).
            // MAX(id) returns null when the table is empty, so we default to 0.
            $next = (Patient::max('id') ?? 0) + 1;
            $medicalRecordNumber = 'RM-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);

            return Patient::create([
                'medical_record_number' => $medicalRecordNumber,
                'nik'                   => $data['nik'],
                'full_name'             => $data['full_name'],
                'date_of_birth'         => $data['date_of_birth'],
                'address'               => $data['address'],
            ]);
        });
    }
}
