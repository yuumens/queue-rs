<?php

namespace Database\Seeders;

use App\Models\Polyclinic;
use Illuminate\Database\Seeder;

class PolyclinicSeeder extends Seeder
{
    /**
     * Seed the polyclinics table with representative data.
     */
    public function run(): void
    {
        $polyclinics = [
            ['name' => 'Penyakit Dalam', 'code' => 'A'],
            ['name' => 'Anak', 'code' => 'B'],
            ['name' => 'Mata', 'code' => 'C'],
            ['name' => 'THT', 'code' => 'D'],
        ];

        foreach ($polyclinics as $polyclinic) {
            Polyclinic::firstOrCreate(
                ['code' => $polyclinic['code']],
                $polyclinic
            );
        }
    }
}
