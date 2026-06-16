<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Polyclinic;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Seed the doctors table with representative data.
     * Each polyclinic gets at least 2 doctors.
     */
    public function run(): void
    {
        $doctors = [
            'A' => [
                'dr. Ahmad Suryadi',
                'dr. Siti Rahayu',
            ],
            'B' => [
                'dr. Budi Santoso',
                'dr. Dewi Anggraeni',
            ],
            'C' => [
                'dr. Hendra Wijaya',
                'dr. Ratna Sari',
            ],
            'D' => [
                'dr. Fajar Nugroho',
                'dr. Rina Kusuma',
            ],
        ];

        foreach ($doctors as $polyclinicCode => $doctorNames) {
            $polyclinic = Polyclinic::where('code', $polyclinicCode)->first();

            if (! $polyclinic) {
                continue;
            }

            foreach ($doctorNames as $name) {
                Doctor::firstOrCreate(
                    ['name' => $name, 'polyclinic_id' => $polyclinic->id],
                    ['name' => $name, 'polyclinic_id' => $polyclinic->id]
                );
            }
        }
    }
}
