<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature tests for queue number generation (QueueController::generate).
 *
 * Covers:
 *   - Valid patient + polyclinic + doctor → registration record created, queue_number formatted, redirect to ticket
 *   - First registration of the day → queue_sequence = 1
 *   - Second registration same polyclinic same day → queue_sequence = 2
 *   - First registration next day → queue_sequence = 1 again
 *   - Property 11: Queue Number Is Correctly Formatted and Sequentially Increments
 *   - Property 13: Registration Record Persists All Required Links
 *   - Property 14: Queue Sequence Resets Daily Per Polyclinic
 *
 * Validates: Requirements 5.1, 5.2, 5.4, 5.5
 */
class QueueGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private Polyclinic $polyclinic;
    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polyclinic = Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $this->doctor = Doctor::create(['name' => 'Dr. Budi', 'polyclinic_id' => $this->polyclinic->id]);
        $this->patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '1234567890123456',
            'full_name'             => 'Siti Rahayu',
            'date_of_birth'         => '1990-05-15',
            'address'               => 'Jl. Melati No. 3, Surabaya',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset frozen time
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * POST to queue.generate with patient_id in session.
     */
    private function generateQueue(
        ?int $patientId = null,
        ?int $polyclinicId = null,
        ?int $doctorId = null
    ) {
        return $this->withSession(['patient_id' => $patientId ?? $this->patient->id])
            ->post(route('queue.generate'), [
                'polyclinic_id' => $polyclinicId ?? $this->polyclinic->id,
                'doctor_id'     => $doctorId ?? $this->doctor->id,
            ]);
    }

    // -------------------------------------------------------------------------
    // Test: valid patient + polyclinic + doctor → registration record created,
    //       queue_number formatted correctly, redirect to ticket
    // Validates: Requirements 5.1, 5.2, 5.4
    // -------------------------------------------------------------------------

    public function test_valid_request_creates_registration_record(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-07-15'));

        $response = $this->generateQueue();

        $this->assertDatabaseHas('registrations', [
            'patient_id'        => $this->patient->id,
            'polyclinic_id'     => $this->polyclinic->id,
            'doctor_id'         => $this->doctor->id,
            'registration_date' => '2024-07-15 00:00:00',
        ]);
    }

    public function test_valid_request_generates_correctly_formatted_queue_number(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-07-15'));

        $this->generateQueue();

        $registration = Registration::first();

        $this->assertNotNull($registration);
        $this->assertMatchesRegularExpression(
            '/^A-\d{2}$/',
            $registration->queue_number
        );
    }

    public function test_valid_request_redirects_to_ticket_page(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-07-15'));

        $response = $this->generateQueue();

        $registration = Registration::first();

        $response->assertRedirect(route('queue.ticket', $registration));
    }

    // -------------------------------------------------------------------------
    // Test: first registration of the day → queue_sequence = 1
    // Validates: Requirement 5.5
    // -------------------------------------------------------------------------

    public function test_first_registration_of_day_has_queue_sequence_one(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-07-15'));

        $this->generateQueue();

        $registration = Registration::first();

        $this->assertNotNull($registration);
        $this->assertSame(1, $registration->queue_sequence);
        $this->assertSame('A-01', $registration->queue_number);
    }

    // -------------------------------------------------------------------------
    // Test: second registration same polyclinic same day → queue_sequence = 2
    // Validates: Requirement 5.2
    // -------------------------------------------------------------------------

    public function test_second_registration_same_day_has_queue_sequence_two(): void
    {
        Carbon::setTestNow(Carbon::parse('2024-07-15'));

        // First registration
        $this->generateQueue();

        // Second registration (same polyclinic, same day)
        $patient2 = Patient::create([
            'medical_record_number' => 'RM-000002',
            'nik'                   => '9876543210987654',
            'full_name'             => 'Budi Santoso',
            'date_of_birth'         => '1988-02-20',
            'address'               => 'Jl. Anggrek No. 5, Jakarta',
        ]);

        $this->generateQueue($patient2->id);

        $second = Registration::where('patient_id', $patient2->id)->first();

        $this->assertNotNull($second);
        $this->assertSame(2, $second->queue_sequence);
        $this->assertSame('A-02', $second->queue_number);
    }

    // -------------------------------------------------------------------------
    // Test: first registration next day → queue_sequence = 1 again
    // Validates: Requirement 5.5
    // -------------------------------------------------------------------------

    public function test_first_registration_next_day_resets_queue_sequence(): void
    {
        // Day 1: register some patients
        Carbon::setTestNow(Carbon::parse('2024-07-15'));
        $this->generateQueue();

        $patient2 = Patient::create([
            'medical_record_number' => 'RM-000002',
            'nik'                   => '9876543210987654',
            'full_name'             => 'Budi Santoso',
            'date_of_birth'         => '1988-02-20',
            'address'               => 'Jl. Anggrek No. 5, Jakarta',
        ]);
        $this->generateQueue($patient2->id);

        // Day 2: new day should reset
        Carbon::setTestNow(Carbon::parse('2024-07-16'));

        $patient3 = Patient::create([
            'medical_record_number' => 'RM-000003',
            'nik'                   => '1111222233334444',
            'full_name'             => 'Dewi Lestari',
            'date_of_birth'         => '1995-10-01',
            'address'               => 'Jl. Flamboyan No. 8, Yogyakarta',
        ]);
        $this->generateQueue($patient3->id);

        $nextDayReg = Registration::where('patient_id', $patient3->id)->first();

        $this->assertNotNull($nextDayReg);
        $this->assertSame(1, $nextDayReg->queue_sequence);
        $this->assertSame('A-01', $nextDayReg->queue_number);
        $this->assertEquals('2024-07-16', $nextDayReg->registration_date->toDateString());
    }

    // =========================================================================
    // Property 11: Queue Number Is Correctly Formatted and Sequentially Increments
    //
    // For any polyclinic with code C that has issued N queue numbers on a given
    // registration date, the next issued queue number shall be "{C}-{pad(N+1,2)}".
    //
    // Validates: Requirements 5.1, 5.2
    // =========================================================================

    #[DataProvider('queueFormatSequenceProvider')]
    public function test_property11_queue_number_formatted_and_increments(
        string $code,
        int $existingCount,
        string $expectedQueueNumber,
        int $expectedSequence
    ): void {
        Carbon::setTestNow(Carbon::parse('2024-08-01'));

        $poly = Polyclinic::create(['name' => "Poly {$code}", 'code' => $code]);
        $doc = Doctor::create(['name' => 'Dr. Test', 'polyclinic_id' => $poly->id]);

        // Pre-fill existing registrations using direct DB inserts
        for ($i = 1; $i <= $existingCount; $i++) {
            $p = Patient::create([
                'medical_record_number' => 'RM-' . str_pad((string) ($i + 10), 6, '0', STR_PAD_LEFT),
                'nik'                   => str_pad((string) ($i + 100), 16, '0', STR_PAD_LEFT),
                'full_name'             => "Patient {$i}",
                'date_of_birth'         => '1985-01-01',
                'address'               => "Address {$i}",
            ]);

            $this->generateQueue($p->id, $poly->id, $doc->id);
        }

        // Issue the next queue number via HTTP
        $nextPatient = Patient::create([
            'medical_record_number' => 'RM-' . str_pad((string) ($existingCount + 50), 6, '0', STR_PAD_LEFT),
            'nik'                   => str_pad((string) ($existingCount + 500), 16, '0', STR_PAD_LEFT),
            'full_name'             => 'Next Patient',
            'date_of_birth'         => '1990-01-01',
            'address'               => 'Next Address',
        ]);

        $response = $this->generateQueue($nextPatient->id, $poly->id, $doc->id);

        $reg = Registration::where('patient_id', $nextPatient->id)
            ->where('polyclinic_id', $poly->id)
            ->first();

        $this->assertNotNull($reg);
        $this->assertSame($expectedQueueNumber, $reg->queue_number);
        $this->assertSame($expectedSequence, $reg->queue_sequence);
    }

    /**
     * **Validates: Requirements 5.1, 5.2**
     */
    public static function queueFormatSequenceProvider(): array
    {
        return [
            'code B, first ticket'  => ['B', 0, 'B-01', 1],
            'code B, second ticket' => ['B', 1, 'B-02', 2],
            'code B, fifth ticket'  => ['B', 4, 'B-05', 5],
            'code INT, first ticket' => ['INT', 0, 'INT-01', 1],
            'code INT, third ticket' => ['INT', 2, 'INT-03', 3],
        ];
    }

    // =========================================================================
    // Property 13: Registration Record Persists All Required Links
    //
    // For any successful queue generation, the resulting Registration record
    // shall have non-null values for patient_id, polyclinic_id, doctor_id,
    // queue_number, and registration_date, matching the inputs provided.
    //
    // Validates: Requirement 5.4
    // =========================================================================

    #[DataProvider('registrationLinksProvider')]
    public function test_property13_registration_persists_all_required_links(
        string $polyCode,
        string $polyName,
        string $doctorName,
        string $date
    ): void {
        Carbon::setTestNow(Carbon::parse($date));

        $poly = Polyclinic::create(['name' => $polyName, 'code' => $polyCode]);
        $doc = Doctor::create(['name' => $doctorName, 'polyclinic_id' => $poly->id]);

        $response = $this->generateQueue($this->patient->id, $poly->id, $doc->id);

        $reg = Registration::where('patient_id', $this->patient->id)
            ->where('polyclinic_id', $poly->id)
            ->where('doctor_id', $doc->id)
            ->first();

        $this->assertNotNull($reg, 'Registration record should exist');
        $this->assertSame($this->patient->id, $reg->patient_id);
        $this->assertSame($poly->id, $reg->polyclinic_id);
        $this->assertSame($doc->id, $reg->doctor_id);
        $this->assertNotNull($reg->queue_number);
        $this->assertNotEmpty($reg->queue_number);
        $this->assertNotNull($reg->registration_date);
        $this->assertEquals($date, $reg->registration_date->toDateString());
    }

    /**
     * **Validates: Requirement 5.4**
     */
    public static function registrationLinksProvider(): array
    {
        return [
            'internal medicine, today' => ['C', 'Penyakit Dalam', 'Dr. Ahmad', '2024-07-15'],
            'pediatrics, different day' => ['D', 'Anak', 'Dr. Sari', '2024-08-20'],
            'ophthalmology, year end'  => ['E', 'Mata', 'Dr. Wati', '2024-12-31'],
        ];
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

    #[DataProvider('dailyResetProvider')]
    public function test_property14_queue_sequence_resets_daily_per_polyclinic(
        int $prevDayCount,
        string $prevDate,
        string $newDate
    ): void {
        // Issue $prevDayCount registrations on previous date
        if ($prevDayCount > 0) {
            Carbon::setTestNow(Carbon::parse($prevDate));

            for ($i = 1; $i <= $prevDayCount; $i++) {
                $p = Patient::create([
                    'medical_record_number' => 'RM-' . str_pad((string) ($i + 200), 6, '0', STR_PAD_LEFT),
                    'nik'                   => str_pad((string) ($i + 2000), 16, '0', STR_PAD_LEFT),
                    'full_name'             => "Prev Patient {$i}",
                    'date_of_birth'         => '1985-01-01',
                    'address'               => "Prev Address {$i}",
                ]);

                $this->generateQueue($p->id);
            }
        }

        // Move to the new date and issue the first registration
        Carbon::setTestNow(Carbon::parse($newDate));

        $newPatient = Patient::create([
            'medical_record_number' => 'RM-000999',
            'nik'                   => '5555666677778888',
            'full_name'             => 'New Day Patient',
            'date_of_birth'         => '1992-06-10',
            'address'               => 'Jl. Baru No. 1',
        ]);

        $this->generateQueue($newPatient->id);

        $reg = Registration::where('patient_id', $newPatient->id)
            ->whereDate('registration_date', $newDate)
            ->first();

        $this->assertNotNull($reg);
        $this->assertSame(1, $reg->queue_sequence);
        $this->assertStringEndsWith('-01', $reg->queue_number);
    }

    /**
     * **Validates: Requirement 5.5**
     */
    public static function dailyResetProvider(): array
    {
        return [
            '1 yesterday, reset today'   => [1, '2024-07-14', '2024-07-15'],
            '5 yesterday, reset today'   => [5, '2024-07-14', '2024-07-15'],
            '3 last week, reset today'   => [3, '2024-07-08', '2024-07-15'],
            '10 last month, reset today' => [10, '2024-06-15', '2024-07-15'],
        ];
    }
}
