<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\PracticeSchedule;
use Illuminate\Database\Seeder;

class PracticeScheduleSeeder extends Seeder
{
    /**
     * Seed the practice_schedules table.
     * Covers Monday (1) through Saturday (6) with varied schedules.
     */
    public function run(): void
    {
        $doctors = Doctor::with('polyclinic')->get();

        // Define schedule patterns: each doctor gets practice days Mon-Sat
        // with morning or afternoon shifts to add variety.
        $schedulePatterns = [
            // Pattern A: Mon, Wed, Fri morning
            'morning_mwf' => [
                ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '12:00'],
                ['day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '12:00'],
            ],
            // Pattern B: Tue, Thu, Sat afternoon
            'afternoon_tts' => [
                ['day_of_week' => 2, 'start_time' => '13:00', 'end_time' => '17:00'],
                ['day_of_week' => 4, 'start_time' => '13:00', 'end_time' => '17:00'],
                ['day_of_week' => 6, 'start_time' => '13:00', 'end_time' => '16:00'],
            ],
            // Pattern C: Mon, Tue, Wed, Thu morning
            'morning_mtwt' => [
                ['day_of_week' => 1, 'start_time' => '07:30', 'end_time' => '11:30'],
                ['day_of_week' => 2, 'start_time' => '07:30', 'end_time' => '11:30'],
                ['day_of_week' => 3, 'start_time' => '07:30', 'end_time' => '11:30'],
                ['day_of_week' => 4, 'start_time' => '07:30', 'end_time' => '11:30'],
            ],
            // Pattern D: Wed, Thu, Fri, Sat afternoon
            'afternoon_wtfs' => [
                ['day_of_week' => 3, 'start_time' => '14:00', 'end_time' => '17:00'],
                ['day_of_week' => 4, 'start_time' => '14:00', 'end_time' => '17:00'],
                ['day_of_week' => 5, 'start_time' => '14:00', 'end_time' => '17:00'],
                ['day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '12:00'],
            ],
        ];

        $patternKeys = array_keys($schedulePatterns);

        foreach ($doctors as $index => $doctor) {
            // Assign pattern based on doctor index to ensure variety
            $patternKey = $patternKeys[$index % count($patternKeys)];
            $slots = $schedulePatterns[$patternKey];

            foreach ($slots as $slot) {
                PracticeSchedule::firstOrCreate(
                    [
                        'doctor_id' => $doctor->id,
                        'polyclinic_id' => $doctor->polyclinic_id,
                        'day_of_week' => $slot['day_of_week'],
                    ],
                    [
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                    ]
                );
            }
        }
    }
}
