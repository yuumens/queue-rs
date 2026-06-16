<?php

namespace Tests\Unit;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\Registration;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for QueueService.
 *
 * Covers:
 *   - Queue number format {CODE}-\d{2}                                      – Requirements 5.1, 5.2
 *   - First registration of the day produces queue_sequence = 1             – Requirement 5.5
 *   - Nth registration produces queue_sequence = N                          – Requirement 5.2
 *   - New day resets queue_sequence to 1                                    – Requirement 5.5
 *   - Property 11: Queue Number Is Correctly Formatted and Sequentially Increments
 *   - Property 14: Queue Sequence Resets Daily Per Polyclinic
 *
 * Validates: Requirements 5.1, 5.2, 5.5
 */
class QueueServiceTest extends TestCase
{
    use RefreshDatabase;

    private QueueService $service;
    private Patient $patient;
    private Polyclinic $polyclinic;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new QueueService();

        // Shared fixtures used across most tests
        $this->polyclinic = Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $this->doctor     = Doctor::create(['name' => 'Dr. Budi', 'polyclinic_id' => $this->polyclinic->id]);
        $this->patient    = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '1234567890123456',
            'full_name'             => 'Siti Rahayu',
            'date_of_birth'         => '1990-05-15',
            'address'               => 'Jl. Melati No. 3, Surabaya',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Issue one queue number using the shared patient/polyclinic/doctor on $date.
     */
    private function issue(Carbon $date): Registration
    {
        return $this->service->issueQueueNumber(
            $this->patient->id,
            $this->polyclinic->id,
            $this->doctor->id,
            $date
        );
    }

    /**
     * Create a fresh patient with a unique NIK for tests that need multiple patients.
     */
    private function makePatient(int $index): Patient
    {
        return Patient::create([
            'medical_record_number' => 'RM-' . str_pad((string) $index, 6, '0', STR_PAD_LEFT),
            'nik'                   => str_pad((string) $index, 16, '0', STR_PAD_LEFT),
            'full_name'             => "Patient {$index}",
            'date_of_birth'         => '1985-01-01',
            'address'               => "Address {$index}",
        ]);
    }

    // -------------------------------------------------------------------------
    // Queue Number Format
    // -------------------------------------------------------------------------

    /**
     * Returned queue_number must match {CODE}-\d{2} for a single-letter code.
     * Validates: Requirements 5.1, 5.2
     */
    public function test_queue_number_matches_format_for_code_a(): void
    {
        $reg = $this->issue(Carbon::parse('2024-07-01'));

        $this->assertMatchesRegularExpression(
            '/^A-\d{2}$/',
            $reg->queue_number,
            "queue_number '{$reg->queue_number}' does not match A-\\d{2}"
        );
    }

    /**
     * queue_number prefix must reflect the polyclinic's code exactly.
     * Validates: Requirements 5.1, 5.2
     */
    public function test_queue_number_prefix_reflects_polyclinic_code(): void
    {
        $poly = Polyclinic::create(['name' => 'Anak', 'code' => 'B']);
        $doc  = Doctor::create(['name' => 'Dr. Citra', 'polyclinic_id' => $poly->id]);

        $reg = $this->service->issueQueueNumber(
            $this->patient->id,
            $poly->id,
            $doc->id,
            Carbon::parse('2024-07-01')
        );

        $this->assertStringStartsWith('B-', $reg->queue_number);
    }

    /**
     * queue_number numeric part must be zero-padded to at least 2 digits.
     * Validates: Requirements 5.1, 5.2
     */
    public function test_queue_number_numeric_part_is_zero_padded(): void
    {
        $reg = $this->issue(Carbon::parse('2024-07-01'));

        // Numeric portion is everything after the first '-'
        $numericPart = substr($reg->queue_number, strpos($reg->queue_number, '-') + 1);

        $this->assertGreaterThanOrEqual(
            2,
            strlen($numericPart),
            "Numeric part '{$numericPart}' is shorter than 2 characters"
        );
        $this->assertMatchesRegularExpression(
            '/^\d{2,}$/',
            $numericPart,
            "Numeric part '{$numericPart}' is not all digits"
        );
    }

    // -------------------------------------------------------------------------
    // First Registration Produces Sequence 1
    // -------------------------------------------------------------------------

    /**
     * When no registrations exist for a polyclinic today, the first one gets queue_sequence = 1.
     * Validates: Requirement 5.5
     */
    public function test_first_registration_of_day_has_sequence_one(): void
    {
        $reg = $this->issue(Carbon::parse('2024-07-01'));

        $this->assertSame(1, $reg->queue_sequence);
    }

    /**
     * First registration of the day should produce queue_number ending in '01'.
     * Validates: Requirements 5.1, 5.5
     */
    public function test_first_registration_queue_number_is_x_01(): void
    {
        $reg = $this->issue(Carbon::parse('2024-07-01'));

        $this->assertSame('A-01', $reg->queue_number);
    }

    // -------------------------------------------------------------------------
    // Nth Registration Produces Sequence N
    // -------------------------------------------------------------------------

    /**
     * Each subsequent registration for the same polyclinic on the same day increments the sequence.
     * Validates: Requirement 5.2
     */
    public function test_second_registration_has_sequence_two(): void
    {
        $date = Carbon::parse('2024-07-01');
        $this->issue($date);
        $second = $this->issue($date);

        $this->assertSame(2, $second->queue_sequence);
        $this->assertSame('A-02', $second->queue_number);
    }

    /**
     * Third registration gets sequence 3 and correct queue number.
     * Validates: Requirement 5.2
     */
    public function test_third_registration_has_sequence_three(): void
    {
        $date = Carbon::parse('2024-07-01');
        $this->issue($date);
        $this->issue($date);
        $third = $this->issue($date);

        $this->assertSame(3, $third->queue_sequence);
        $this->assertSame('A-03', $third->queue_number);
    }

    /**
     * After N registrations the returned queue_sequence equals N.
     * Validates: Requirement 5.2
     */
    public function test_nth_registration_has_sequence_n(): void
    {
        $date = Carbon::parse('2024-07-02');
        $n    = 5;

        $last = null;
        for ($i = 1; $i <= $n; $i++) {
            $last = $this->issue($date);
        }

        $this->assertSame($n, $last->queue_sequence);
    }

    // -------------------------------------------------------------------------
    // New Day Resets Sequence to 1
    // -------------------------------------------------------------------------

    /**
     * After several registrations today, tomorrow's first registration resets to sequence 1.
     * Validates: Requirement 5.5
     */
    public function test_new_day_resets_sequence_to_one(): void
    {
        $today    = Carbon::parse('2024-07-01');
        $tomorrow = Carbon::parse('2024-07-02');

        // Issue 3 registrations today
        for ($i = 0; $i < 3; $i++) {
            $this->issue($today);
        }

        // First registration on a new day
        $nextDayReg = $this->issue($tomorrow);

        $this->assertSame(1, $nextDayReg->queue_sequence);
        $this->assertSame('A-01', $nextDayReg->queue_number);
    }

    /**
     * Sequence is independent per calendar date — different dates never share state.
     * Validates: Requirement 5.5
     */
    public function test_sequence_is_independent_per_date(): void
    {
        $day1 = Carbon::parse('2024-07-01');
        $day2 = Carbon::parse('2024-07-08');

        // Five registrations on day 1
        for ($i = 0; $i < 5; $i++) {
            $this->issue($day1);
        }

        // First registration on day 2 should be sequence 1, not 6
        $reg = $this->issue($day2);
        $this->assertSame(1, $reg->queue_sequence);
    }

    /**
     * Sequence resets for each new polyclinic independently on the same date.
     * Validates: Requirement 5.5
     */
    public function test_different_polyclinics_have_independent_sequences(): void
    {
        $polyB = Polyclinic::create(['name' => 'Mata', 'code' => 'B']);
        $docB  = Doctor::create(['name' => 'Dr. Siti', 'polyclinic_id' => $polyB->id]);
        $date  = Carbon::parse('2024-07-01');

        // Two registrations at polyclinic A
        $this->issue($date);
        $this->issue($date);

        // First registration at polyclinic B — must start at 1, not 3
        $regB = $this->service->issueQueueNumber(
            $this->patient->id,
            $polyB->id,
            $docB->id,
            $date
        );

        $this->assertSame(1, $regB->queue_sequence);
        $this->assertSame('B-01', $regB->queue_number);
    }

    // =========================================================================
    // Property 11: Queue Number Is Correctly Formatted and Sequentially Increments
    //
    // For any polyclinic with code C that has issued N queue numbers on a given
    // registration date, the next issued queue number shall be "{C}-{pad(N+1,2)}".
    //
    // Validates: Requirements 5.1, 5.2
    // =========================================================================

    /**
     * Property 11 data provider: (polyclinic code, batch size before next issuance).
     *
     * Each row is [code, existingCount] meaning after `existingCount` registrations,
     * the next queue number must be "{code}-{pad(existingCount+1, 2)}".
     *
     * Codes are chosen to be distinct from the setUp() fixture code 'A'.
     */
    public static function queueFormatProvider(): array
    {
        return [
            'code P1, first ticket'    => ['P1', 0],
            'code P1, second ticket'   => ['P1', 1],
            'code P1, fifth ticket'    => ['P1', 4],
            'code B, first ticket'     => ['B',  0],
            'code INT, first ticket'   => ['INT', 0],
            'code INT, third ticket'   => ['INT', 2],
            'code GIG, tenth ticket'   => ['GIG', 9],
        ];
    }

    /**
     * Property 11: Queue Number Is Correctly Formatted and Sequentially Increments.
     *
     * Validates: Requirements 5.1, 5.2
     */
    #[DataProvider('queueFormatProvider')]
    public function test_property11_queue_number_is_correctly_formatted_and_increments(
        string $code,
        int $existingCount
    ): void {
        // Create a distinct polyclinic for this sub-test (setUp already created code 'A')
        $poly = Polyclinic::create(['name' => "Poly {$code}", 'code' => $code]);
        $doc  = Doctor::create(['name' => 'Dr. Test', 'polyclinic_id' => $poly->id]);

        // Reuse setUp() patient (no per-patient unique constraint with polyclinic+date)
        $date = Carbon::parse('2024-08-01');

        // Pre-issue $existingCount registrations
        for ($i = 0; $i < $existingCount; $i++) {
            $this->service->issueQueueNumber($this->patient->id, $poly->id, $doc->id, $date);
        }

        // Issue the next one — this is the (existingCount + 1)-th
        $reg      = $this->service->issueQueueNumber($this->patient->id, $poly->id, $doc->id, $date);
        $expected = $code . '-' . str_pad((string) ($existingCount + 1), 2, '0', STR_PAD_LEFT);

        $this->assertSame(
            $expected,
            $reg->queue_number,
            "Expected queue_number '{$expected}', got '{$reg->queue_number}'"
        );

        $this->assertSame(
            $existingCount + 1,
            $reg->queue_sequence,
            "Expected queue_sequence " . ($existingCount + 1) . ", got {$reg->queue_sequence}"
        );

        // The queue_number must match the generic pattern {CODE}-\d{2,}
        $this->assertMatchesRegularExpression(
            '/^' . preg_quote($code, '/') . '-\d{2,}$/',
            $reg->queue_number
        );
    }

    // =========================================================================
    // Property 14: Queue Sequence Resets Daily Per Polyclinic
    //
    // For any polyclinic and any registration date on which no prior registrations
    // exist for that polyclinic, the first queue number issued shall have
    // queue_sequence = 1.
    //
    // Validates: Requirement 5.5
    // =========================================================================

    /**
     * Property 14 data provider: (registrations on previous day, date pairs).
     *
     * Each row is [prevCount, prevDate, newDate] — after prevCount registrations
     * on prevDate, the first registration on newDate must have sequence 1.
     */
    public static function dailyResetProvider(): array
    {
        return [
            'no history, day 1'              => [0, '2024-07-01', '2024-07-01'],
            '1 yesterday, reset today'       => [1, '2024-06-30', '2024-07-01'],
            '5 yesterday, reset today'       => [5, '2024-06-30', '2024-07-01'],
            '10 last week, reset today'      => [10, '2024-06-24', '2024-07-01'],
            '3 this month, reset next month' => [3, '2024-07-31', '2024-08-01'],
        ];
    }

    /**
     * Property 14: Queue Sequence Resets Daily Per Polyclinic.
     *
     * Validates: Requirement 5.5
     */
    #[DataProvider('dailyResetProvider')]
    public function test_property14_queue_sequence_resets_daily_per_polyclinic(
        int $prevCount,
        string $prevDate,
        string $newDate
    ): void {
        $prev = Carbon::parse($prevDate);
        $new  = Carbon::parse($newDate);

        // Issue $prevCount registrations on $prevDate (skip if 0 or same date)
        if ($prevCount > 0 && $prevDate !== $newDate) {
            for ($i = 0; $i < $prevCount; $i++) {
                $this->issue($prev);
            }
        }

        // The first registration on $newDate must always be sequence 1
        $reg = $this->issue($new);

        $this->assertSame(
            1,
            $reg->queue_sequence,
            "Expected queue_sequence 1 on {$newDate} after {$prevCount} registrations on {$prevDate}, "
            . "got {$reg->queue_sequence}"
        );

        $this->assertMatchesRegularExpression(
            '/^A-01$/',
            $reg->queue_number,
            "Expected queue_number 'A-01' on fresh date {$newDate}, got '{$reg->queue_number}'"
        );
    }
}
