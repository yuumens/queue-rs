<?php

namespace Tests\Feature;

use App\Livewire\PolyclinicDoctorSelector;
use App\Models\Doctor;
use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Livewire component tests for PolyclinicDoctorSelector.
 *
 * Covers:
 *   - Polyclinics list contains only today's active polyclinics      – Requirement 4.1
 *   - Selecting a polyclinic loads correct doctors                    – Requirement 4.3
 *   - Polyclinic with no doctors today shows empty doctor list        – Requirement 4.7
 *   - Empty schedule shows noClinicsAvailable                         – Requirement 4.6
 *   - Property 8: Polyclinic List Contains Exactly Today's Active Polyclinics
 *   - Property 9: Doctor Filter Shows Only Doctors Scheduled Today for the Selected Polyclinic
 *   - Property 10: Doctor List Entries Include Name and Practice Hours
 *
 * Validates: Requirements 4.1, 4.3, 4.4, 4.6, 4.7
 */
class PolyclinicDoctorSelectorTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createPolyclinic(array $attributes = []): Polyclinic
    {
        static $counter = 0;
        $counter++;

        return Polyclinic::create(array_merge([
            'name' => "Polyclinic {$counter}",
            'code' => chr(64 + $counter), // A, B, C, ...
        ], $attributes));
    }

    private function createDoctor(array $attributes = []): Doctor
    {
        static $counter = 0;
        $counter++;

        if (!isset($attributes['polyclinic_id'])) {
            $polyclinic = $this->createPolyclinic();
            $attributes['polyclinic_id'] = $polyclinic->id;
        }

        return Doctor::create(array_merge([
            'name' => "Doctor {$counter}",
        ], $attributes));
    }

    private function createSchedule(int $doctorId, int $polyclinicId, int $dayOfWeek, string $startTime = '08:00', string $endTime = '12:00'): PracticeSchedule
    {
        return PracticeSchedule::create([
            'doctor_id'     => $doctorId,
            'polyclinic_id' => $polyclinicId,
            'day_of_week'   => $dayOfWeek,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
        ]);
    }

    // -------------------------------------------------------------------------
    // Basic Functionality Tests
    // Validates: Requirement 4.1
    // -------------------------------------------------------------------------

    public function test_polyclinics_contains_only_todays_polyclinics(): void
    {
        // Set today to Wednesday (day_of_week = 3)
        Carbon::setTestNow(Carbon::create(2025, 1, 8)); // Wednesday

        $polyA = $this->createPolyclinic(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $polyB = $this->createPolyclinic(['name' => 'Anak', 'code' => 'B']);
        $polyC = $this->createPolyclinic(['name' => 'Bedah', 'code' => 'C']);

        $docA = $this->createDoctor(['name' => 'Dr. A', 'polyclinic_id' => $polyA->id]);
        $docB = $this->createDoctor(['name' => 'Dr. B', 'polyclinic_id' => $polyB->id]);
        $docC = $this->createDoctor(['name' => 'Dr. C', 'polyclinic_id' => $polyC->id]);

        // Schedule Dr. A on Wednesday (day 3) at polyclinic A
        $this->createSchedule($docA->id, $polyA->id, 3);
        // Schedule Dr. B on Wednesday (day 3) at polyclinic B
        $this->createSchedule($docB->id, $polyB->id, 3);
        // Schedule Dr. C on Thursday (day 4) at polyclinic C — NOT today
        $this->createSchedule($docC->id, $polyC->id, 4);

        $component = Livewire::test(PolyclinicDoctorSelector::class);

        $component->assertSet('polyclinics', function ($polyclinics) use ($polyA, $polyB, $polyC) {
            $ids = $polyclinics->pluck('id')->toArray();
            return count($ids) === 2
                && in_array($polyA->id, $ids)
                && in_array($polyB->id, $ids)
                && !in_array($polyC->id, $ids);
        });

        Carbon::setTestNow(); // reset
    }

    // -------------------------------------------------------------------------
    // Selecting a polyclinic loads correct doctors
    // Validates: Requirement 4.3
    // -------------------------------------------------------------------------

    public function test_selecting_polyclinic_loads_correct_doctors(): void
    {
        // Set today to Monday (day_of_week = 1)
        Carbon::setTestNow(Carbon::create(2025, 1, 6)); // Monday

        $poly = $this->createPolyclinic(['name' => 'Penyakit Dalam', 'code' => 'P']);
        $doc1 = $this->createDoctor(['name' => 'Dr. Andi', 'polyclinic_id' => $poly->id]);
        $doc2 = $this->createDoctor(['name' => 'Dr. Budi', 'polyclinic_id' => $poly->id]);
        $doc3 = $this->createDoctor(['name' => 'Dr. Citra', 'polyclinic_id' => $poly->id]);

        // Doc1 and Doc2 work Monday, Doc3 works Tuesday
        $this->createSchedule($doc1->id, $poly->id, 1, '08:00', '12:00');
        $this->createSchedule($doc2->id, $poly->id, 1, '13:00', '17:00');
        $this->createSchedule($doc3->id, $poly->id, 2, '08:00', '12:00');

        $component = Livewire::test(PolyclinicDoctorSelector::class)
            ->set('selectedPolyclinicId', $poly->id);

        $component->assertSet('doctors', function (array $doctors) use ($doc1, $doc2, $doc3) {
            $doctorIds = array_column($doctors, 'doctor_id');
            return count($doctors) === 2
                && in_array($doc1->id, $doctorIds)
                && in_array($doc2->id, $doctorIds)
                && !in_array($doc3->id, $doctorIds);
        });

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Polyclinic with no doctors today shows empty doctor list
    // Validates: Requirement 4.7
    // -------------------------------------------------------------------------

    public function test_polyclinic_with_no_doctors_today_shows_empty_doctor_list(): void
    {
        // Set today to Friday (day_of_week = 5)
        Carbon::setTestNow(Carbon::create(2025, 1, 10)); // Friday

        $poly = $this->createPolyclinic(['name' => 'Mata', 'code' => 'M']);
        $doc = $this->createDoctor(['name' => 'Dr. Eye', 'polyclinic_id' => $poly->id]);

        // Doctor only works on Saturday (day 6), not Friday
        // But we need the polyclinic to appear in the list — schedule someone else on Friday
        $doc2 = $this->createDoctor(['name' => 'Dr. Other', 'polyclinic_id' => $poly->id]);
        $this->createSchedule($doc2->id, $poly->id, 5, '08:00', '12:00');
        $this->createSchedule($doc->id, $poly->id, 6, '08:00', '12:00');

        // Now simulate selecting a different polyclinic that has no one scheduled on Friday
        $polyEmpty = $this->createPolyclinic(['name' => 'THT', 'code' => 'T']);
        $docTht = $this->createDoctor(['name' => 'Dr. THT', 'polyclinic_id' => $polyEmpty->id]);
        $this->createSchedule($docTht->id, $polyEmpty->id, 6); // Saturday only

        // The polyEmpty won't appear in polyclinics (no Friday schedule), so let's test
        // a polyclinic that appears but after selecting shows empty doctors due to schedule
        // Actually, if a polyclinic appears in the list, it has at least one schedule today.
        // The scenario for 4.7 is: user selects a polyclinic but its doctors list is empty.
        // This can happen if we set selectedPolyclinicId to a polyclinic that has no schedule today.
        $component = Livewire::test(PolyclinicDoctorSelector::class)
            ->set('selectedPolyclinicId', $polyEmpty->id);

        $component->assertSet('doctors', []);

        Carbon::setTestNow();
    }

    // -------------------------------------------------------------------------
    // Empty schedule shows noClinicsAvailable
    // Validates: Requirement 4.6
    // -------------------------------------------------------------------------

    public function test_empty_schedule_shows_no_clinics_available(): void
    {
        // Set today to Sunday (day_of_week = 0)
        Carbon::setTestNow(Carbon::create(2025, 1, 5)); // Sunday

        // Create polyclinics with doctors, but no schedules on Sunday
        $poly = $this->createPolyclinic(['name' => 'Penyakit Dalam', 'code' => 'D']);
        $doc = $this->createDoctor(['name' => 'Dr. Sunday Off', 'polyclinic_id' => $poly->id]);
        $this->createSchedule($doc->id, $poly->id, 1); // Monday only

        $component = Livewire::test(PolyclinicDoctorSelector::class);

        $component
            ->assertSet('noClinicsAvailable', true)
            ->assertSet('polyclinics', function ($polyclinics) {
                return $polyclinics->isEmpty();
            });

        Carbon::setTestNow();
    }

    public function test_no_schedules_at_all_shows_no_clinics_available(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 6)); // Monday

        // No polyclinics, no schedules at all
        $component = Livewire::test(PolyclinicDoctorSelector::class);

        $component
            ->assertSet('noClinicsAvailable', true)
            ->assertSet('polyclinics', function ($polyclinics) {
                return $polyclinics->isEmpty();
            });

        Carbon::setTestNow();
    }

    // =========================================================================
    // Property 8: Polyclinic List Contains Exactly Today's Active Polyclinics
    //
    // For any practice schedule configuration and any given date, the polyclinic
    // selection page shall display exactly those polyclinics that have at least
    // one PracticeSchedule record with day_of_week matching the day of week of
    // the given date — no more, no fewer.
    //
    // Validates: Requirements 4.1, 7.4
    // =========================================================================

    #[DataProvider('polyclinicScheduleProvider')]
    public function test_property8_polyclinic_list_contains_exactly_todays_active_polyclinics(
        int $todayDayOfWeek,
        array $scheduleConfig,
        int $expectedPolyclinicCount,
        array $expectedPolyclinicCodes
    ): void {
        // Set the test day: pick a date that corresponds to the given day_of_week
        // 2025-01-05 is Sunday (0), 2025-01-06 is Monday (1), ..., 2025-01-11 is Saturday (6)
        $baseDate = Carbon::create(2025, 1, 5)->addDays($todayDayOfWeek);
        Carbon::setTestNow($baseDate);

        $polyclinics = [];
        $doctors = [];

        // Create polyclinics and doctors from config
        foreach ($scheduleConfig as $config) {
            $code = $config['code'];
            if (!isset($polyclinics[$code])) {
                $polyclinics[$code] = $this->createPolyclinic(['name' => "Poly {$code}", 'code' => $code]);
            }
            $doctor = $this->createDoctor([
                'name'          => $config['doctor_name'],
                'polyclinic_id' => $polyclinics[$code]->id,
            ]);
            $this->createSchedule(
                $doctor->id,
                $polyclinics[$code]->id,
                $config['day_of_week'],
                $config['start_time'] ?? '08:00',
                $config['end_time'] ?? '12:00'
            );
        }

        $component = Livewire::test(PolyclinicDoctorSelector::class);

        $component->assertSet('polyclinics', function ($result) use ($expectedPolyclinicCount, $expectedPolyclinicCodes) {
            if ($result->count() !== $expectedPolyclinicCount) {
                return false;
            }
            $codes = $result->pluck('code')->toArray();
            foreach ($expectedPolyclinicCodes as $code) {
                if (!in_array($code, $codes)) {
                    return false;
                }
            }
            return true;
        });

        if ($expectedPolyclinicCount === 0) {
            $component->assertSet('noClinicsAvailable', true);
        } else {
            $component->assertSet('noClinicsAvailable', false);
        }

        Carbon::setTestNow();
    }

    /**
     * **Validates: Requirements 4.1**
     */
    public static function polyclinicScheduleProvider(): array
    {
        return [
            'Monday: two polyclinics active' => [
                1, // Monday
                [
                    ['code' => 'A', 'doctor_name' => 'Dr. A1', 'day_of_week' => 1],
                    ['code' => 'B', 'doctor_name' => 'Dr. B1', 'day_of_week' => 1],
                    ['code' => 'C', 'doctor_name' => 'Dr. C1', 'day_of_week' => 3], // Wednesday
                ],
                2,
                ['A', 'B'],
            ],
            'Wednesday: only one polyclinic active' => [
                3, // Wednesday
                [
                    ['code' => 'A', 'doctor_name' => 'Dr. A1', 'day_of_week' => 1],
                    ['code' => 'B', 'doctor_name' => 'Dr. B1', 'day_of_week' => 1],
                    ['code' => 'C', 'doctor_name' => 'Dr. C1', 'day_of_week' => 3],
                ],
                1,
                ['C'],
            ],
            'Sunday: no polyclinics active' => [
                0, // Sunday
                [
                    ['code' => 'A', 'doctor_name' => 'Dr. A1', 'day_of_week' => 1],
                    ['code' => 'B', 'doctor_name' => 'Dr. B1', 'day_of_week' => 2],
                ],
                0,
                [],
            ],
            'Saturday: all polyclinics active' => [
                6, // Saturday
                [
                    ['code' => 'A', 'doctor_name' => 'Dr. A1', 'day_of_week' => 6],
                    ['code' => 'B', 'doctor_name' => 'Dr. B1', 'day_of_week' => 6],
                    ['code' => 'C', 'doctor_name' => 'Dr. C1', 'day_of_week' => 6],
                ],
                3,
                ['A', 'B', 'C'],
            ],
            'multiple doctors same polyclinic still counts once' => [
                2, // Tuesday
                [
                    ['code' => 'A', 'doctor_name' => 'Dr. A1', 'day_of_week' => 2],
                    ['code' => 'A', 'doctor_name' => 'Dr. A2', 'day_of_week' => 2],
                    ['code' => 'B', 'doctor_name' => 'Dr. B1', 'day_of_week' => 3],
                ],
                1,
                ['A'],
            ],
        ];
    }

    // =========================================================================
    // Property 9: Doctor Filter Shows Only Doctors Scheduled Today for the
    //             Selected Polyclinic
    //
    // For any polyclinic selected on any given date, the returned doctor list
    // shall contain exactly those doctors who have a PracticeSchedule record
    // with polyclinic_id matching the selected polyclinic and day_of_week
    // matching the current day of week.
    //
    // Validates: Requirements 4.3
    // =========================================================================

    #[DataProvider('doctorFilterProvider')]
    public function test_property9_doctor_filter_shows_only_doctors_scheduled_today(
        int $todayDayOfWeek,
        string $selectedPolyclinicCode,
        array $scheduleConfig,
        array $expectedDoctorNames
    ): void {
        $baseDate = Carbon::create(2025, 1, 5)->addDays($todayDayOfWeek);
        Carbon::setTestNow($baseDate);

        $polyclinics = [];
        $doctors = [];

        foreach ($scheduleConfig as $config) {
            $code = $config['polyclinic_code'];
            if (!isset($polyclinics[$code])) {
                $polyclinics[$code] = $this->createPolyclinic(['name' => "Poly {$code}", 'code' => $code]);
            }
            $doctorName = $config['doctor_name'];
            if (!isset($doctors[$doctorName])) {
                $doctors[$doctorName] = $this->createDoctor([
                    'name'          => $doctorName,
                    'polyclinic_id' => $polyclinics[$code]->id,
                ]);
            }
            $this->createSchedule(
                $doctors[$doctorName]->id,
                $polyclinics[$code]->id,
                $config['day_of_week'],
                $config['start_time'] ?? '08:00',
                $config['end_time'] ?? '12:00'
            );
        }

        $selectedPoly = $polyclinics[$selectedPolyclinicCode];

        $component = Livewire::test(PolyclinicDoctorSelector::class)
            ->set('selectedPolyclinicId', $selectedPoly->id);

        $component->assertSet('doctors', function (array $doctorList) use ($expectedDoctorNames) {
            if (count($doctorList) !== count($expectedDoctorNames)) {
                return false;
            }
            $names = array_map(fn($d) => $d['doctor']['name'], $doctorList);
            foreach ($expectedDoctorNames as $expectedName) {
                if (!in_array($expectedName, $names)) {
                    return false;
                }
            }
            return true;
        });

        Carbon::setTestNow();
    }

    /**
     * **Validates: Requirements 4.3**
     */
    public static function doctorFilterProvider(): array
    {
        return [
            'Monday: two doctors at polyclinic A' => [
                1, // Monday
                'A',
                [
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Andi', 'day_of_week' => 1],
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Budi', 'day_of_week' => 1],
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Citra', 'day_of_week' => 2], // Tuesday
                    ['polyclinic_code' => 'B', 'doctor_name' => 'Dr. Dani', 'day_of_week' => 1],
                ],
                ['Dr. Andi', 'Dr. Budi'],
            ],
            'Tuesday: one doctor at polyclinic A (Citra)' => [
                2, // Tuesday
                'A',
                [
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Andi', 'day_of_week' => 1],
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Citra', 'day_of_week' => 2],
                    ['polyclinic_code' => 'B', 'doctor_name' => 'Dr. Dani', 'day_of_week' => 2],
                ],
                ['Dr. Citra'],
            ],
            'no doctors scheduled today at selected polyclinic' => [
                0, // Sunday
                'A',
                [
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Andi', 'day_of_week' => 1],
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Budi', 'day_of_week' => 2],
                ],
                [],
            ],
            'same doctor at multiple polyclinics, only shows for selected' => [
                3, // Wednesday
                'B',
                [
                    ['polyclinic_code' => 'A', 'doctor_name' => 'Dr. Multi', 'day_of_week' => 3],
                    ['polyclinic_code' => 'B', 'doctor_name' => 'Dr. Multi', 'day_of_week' => 3],
                    ['polyclinic_code' => 'B', 'doctor_name' => 'Dr. Solo', 'day_of_week' => 3],
                ],
                ['Dr. Multi', 'Dr. Solo'],
            ],
        ];
    }

    // =========================================================================
    // Property 10: Doctor List Entries Include Name and Practice Hours
    //
    // For any doctor returned in the available-doctors list, the rendered entry
    // shall include the doctor's name and the corresponding practice schedule's
    // start_time and end_time.
    //
    // Validates: Requirements 4.4
    // =========================================================================

    #[DataProvider('doctorListEntriesProvider')]
    public function test_property10_doctor_list_entries_include_name_and_practice_hours(
        string $doctorName,
        string $startTime,
        string $endTime
    ): void {
        // Set today to Tuesday (day_of_week = 2)
        Carbon::setTestNow(Carbon::create(2025, 1, 7)); // Tuesday

        $poly = $this->createPolyclinic(['name' => 'General', 'code' => 'G']);
        $doc = $this->createDoctor(['name' => $doctorName, 'polyclinic_id' => $poly->id]);
        $this->createSchedule($doc->id, $poly->id, 2, $startTime, $endTime);

        $component = Livewire::test(PolyclinicDoctorSelector::class)
            ->set('selectedPolyclinicId', $poly->id);

        $component->assertSet('doctors', function (array $doctorList) use ($doctorName, $startTime, $endTime) {
            if (count($doctorList) !== 1) {
                return false;
            }

            $entry = $doctorList[0];

            // Must include doctor name via the eager-loaded relationship
            $hasName = isset($entry['doctor']['name']) && $entry['doctor']['name'] === $doctorName;

            // Must include start_time and end_time
            $hasStartTime = isset($entry['start_time']) && $entry['start_time'] === $startTime;
            $hasEndTime = isset($entry['end_time']) && $entry['end_time'] === $endTime;

            return $hasName && $hasStartTime && $hasEndTime;
        });

        Carbon::setTestNow();
    }

    /**
     * **Validates: Requirements 4.4**
     */
    public static function doctorListEntriesProvider(): array
    {
        return [
            'morning shift' => [
                'Dr. Pagi', '08:00', '12:00',
            ],
            'afternoon shift' => [
                'Dr. Siang', '13:00', '17:00',
            ],
            'evening shift' => [
                'Dr. Malam', '18:00', '21:00',
            ],
            'early morning shift' => [
                'Dr. Subuh', '05:30', '07:30',
            ],
            'full day shift' => [
                'Dr. Marathon', '07:00', '19:00',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Additional edge case tests
    // -------------------------------------------------------------------------

    public function test_selecting_null_polyclinic_clears_doctors(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 6)); // Monday

        $poly = $this->createPolyclinic(['name' => 'Test', 'code' => 'X']);
        $doc = $this->createDoctor(['name' => 'Dr. Test', 'polyclinic_id' => $poly->id]);
        $this->createSchedule($doc->id, $poly->id, 1);

        $component = Livewire::test(PolyclinicDoctorSelector::class)
            ->set('selectedPolyclinicId', $poly->id)
            ->assertSet('doctors', function (array $doctors) {
                return count($doctors) === 1;
            })
            ->set('selectedPolyclinicId', null)
            ->assertSet('doctors', []);

        Carbon::setTestNow();
    }

    public function test_selecting_doctor_resets_when_polyclinic_changes(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 6)); // Monday

        $polyA = $this->createPolyclinic(['name' => 'Poly A', 'code' => 'A']);
        $polyB = $this->createPolyclinic(['name' => 'Poly B', 'code' => 'B']);

        $docA = $this->createDoctor(['name' => 'Dr. A', 'polyclinic_id' => $polyA->id]);
        $docB = $this->createDoctor(['name' => 'Dr. B', 'polyclinic_id' => $polyB->id]);

        $this->createSchedule($docA->id, $polyA->id, 1);
        $this->createSchedule($docB->id, $polyB->id, 1);

        $component = Livewire::test(PolyclinicDoctorSelector::class)
            ->set('selectedPolyclinicId', $polyA->id)
            ->set('selectedDoctorId', $docA->id)
            ->assertSet('selectedDoctorId', $docA->id)
            ->set('selectedPolyclinicId', $polyB->id)
            ->assertSet('selectedDoctorId', null);

        Carbon::setTestNow();
    }
}
