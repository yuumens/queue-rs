<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\Registration;
use App\Services\QueueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Concurrency tests for queue number generation uniqueness.
 *
 * Property 12: Concurrent Queue Generation Produces No Duplicates
 *
 * For any number of simultaneous queue generation requests for the same polyclinic
 * on the same registration date, all resulting queue numbers shall be distinct —
 * no two registrations shall share the same (polyclinic_id, queue_sequence, registration_date)
 * combination.
 *
 * Validates: Requirements 5.3, 8.3
 */
class QueueConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private Polyclinic $polyclinic;
    private Doctor $doctor;
    private QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->polyclinic = Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $this->doctor = Doctor::create(['name' => 'Dr. Budi', 'polyclinic_id' => $this->polyclinic->id]);
        $this->queueService = app(QueueService::class);

        Carbon::setTestNow(Carbon::parse('2024-08-01'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a unique patient for testing.
     */
    private function createPatient(int $index): Patient
    {
        return Patient::create([
            'medical_record_number' => 'RM-' . str_pad((string) ($index + 100), 6, '0', STR_PAD_LEFT),
            'nik'                   => str_pad((string) ($index + 1000), 16, '0', STR_PAD_LEFT),
            'full_name'             => "Patient {$index}",
            'date_of_birth'         => '1990-01-01',
            'address'               => "Address {$index}",
        ]);
    }

    // =========================================================================
    // Property 12: Concurrent Queue Generation Produces No Duplicates
    //
    // Simulates concurrent-like queue generation by rapidly issuing multiple
    // queue numbers for the same polyclinic on the same date, then asserts
    // all queue_sequence values are distinct.
    //
    // **Validates: Requirements 5.3, 8.3**
    // =========================================================================

    /**
     * Test that rapid sequential queue generation produces no duplicate sequences.
     *
     * Simulates concurrent access by rapidly calling issueQueueNumber() multiple
     * times for the same polyclinic and date. All resulting queue_sequence values
     * must be unique.
     */
    public function test_rapid_queue_generation_produces_no_duplicate_sequences(): void
    {
        $date = Carbon::parse('2024-08-01');
        $count = 20;

        // Rapidly issue queue numbers for the same polyclinic and date
        $registrations = [];
        for ($i = 1; $i <= $count; $i++) {
            $patient = $this->createPatient($i);
            $registrations[] = $this->queueService->issueQueueNumber(
                $patient->id,
                $this->polyclinic->id,
                $this->doctor->id,
                $date
            );
        }

        // Collect all queue_sequence values
        $sequences = array_map(fn ($reg) => $reg->queue_sequence, $registrations);

        // Assert all sequences are distinct (no duplicates)
        $this->assertCount($count, array_unique($sequences));

        // Assert sequences form a contiguous range from 1 to $count
        sort($sequences);
        $this->assertSame(range(1, $count), $sequences);
    }

    /**
     * Test that rapid queue generation via HTTP endpoint produces no duplicates.
     *
     * Simulates concurrent HTTP requests by rapidly POSTing to queue.generate
     * for the same polyclinic on the same date.
     */
    public function test_rapid_http_queue_generation_produces_no_duplicates(): void
    {
        $count = 10;

        for ($i = 1; $i <= $count; $i++) {
            $patient = $this->createPatient($i + 50);

            $this->withSession(['patient_id' => $patient->id])
                ->post(route('queue.generate'), [
                    'polyclinic_id' => $this->polyclinic->id,
                    'doctor_id'     => $this->doctor->id,
                ]);
        }

        // Fetch all registrations for this polyclinic on this date
        $registrations = Registration::where('polyclinic_id', $this->polyclinic->id)
            ->whereDate('registration_date', '2024-08-01')
            ->get();

        $this->assertCount($count, $registrations);

        // Assert all queue_sequence values are distinct
        $sequences = $registrations->pluck('queue_sequence')->toArray();
        $this->assertCount($count, array_unique($sequences));

        // Assert all queue_number values are distinct
        $queueNumbers = $registrations->pluck('queue_number')->toArray();
        $this->assertCount($count, array_unique($queueNumbers));
    }

    /**
     * Test that the database unique constraint prevents duplicate (polyclinic_id, queue_sequence, registration_date).
     *
     * Directly attempts to insert a duplicate registration record and asserts
     * the database rejects it, confirming the uniqueness constraint at the DB level.
     */
    public function test_database_constraint_prevents_duplicate_queue_sequence(): void
    {
        $date = Carbon::parse('2024-08-01');
        $patient1 = $this->createPatient(1);
        $patient2 = $this->createPatient(2);

        // Issue first queue number normally
        $this->queueService->issueQueueNumber(
            $patient1->id,
            $this->polyclinic->id,
            $this->doctor->id,
            $date
        );

        // Attempt to directly insert a duplicate (bypassing the service lock logic)
        $this->expectException(\Illuminate\Database\QueryException::class);

        Registration::create([
            'patient_id'        => $patient2->id,
            'polyclinic_id'     => $this->polyclinic->id,
            'doctor_id'         => $this->doctor->id,
            'queue_number'      => 'A-01',
            'queue_sequence'    => 1, // duplicate sequence for same polyclinic+date
            'registration_date' => $date->toDateString(),
        ]);
    }

    /**
     * Test that multiple polyclinics can have independent sequences on the same date.
     *
     * Ensures concurrent-like generation across different polyclinics does not
     * interfere — each polyclinic maintains its own sequence space.
     */
    public function test_concurrent_generation_across_polyclinics_independent(): void
    {
        $date = Carbon::parse('2024-08-01');

        $polyclinicB = Polyclinic::create(['name' => 'Anak', 'code' => 'B']);
        $doctorB = Doctor::create(['name' => 'Dr. Sari', 'polyclinic_id' => $polyclinicB->id]);

        $countPerPolyclinic = 10;

        // Interleave registrations between two polyclinics
        for ($i = 1; $i <= $countPerPolyclinic; $i++) {
            $patientA = $this->createPatient($i);
            $patientB = $this->createPatient($i + $countPerPolyclinic);

            $this->queueService->issueQueueNumber(
                $patientA->id,
                $this->polyclinic->id,
                $this->doctor->id,
                $date
            );

            $this->queueService->issueQueueNumber(
                $patientB->id,
                $polyclinicB->id,
                $doctorB->id,
                $date
            );
        }

        // Check polyclinic A sequences
        $seqA = Registration::where('polyclinic_id', $this->polyclinic->id)
            ->whereDate('registration_date', '2024-08-01')
            ->pluck('queue_sequence')
            ->sort()
            ->values()
            ->toArray();

        $this->assertSame(range(1, $countPerPolyclinic), $seqA);

        // Check polyclinic B sequences
        $seqB = Registration::where('polyclinic_id', $polyclinicB->id)
            ->whereDate('registration_date', '2024-08-01')
            ->pluck('queue_sequence')
            ->sort()
            ->values()
            ->toArray();

        $this->assertSame(range(1, $countPerPolyclinic), $seqB);

        // All queue numbers across both polyclinics are distinct
        $allQueueNumbers = Registration::whereDate('registration_date', '2024-08-01')
            ->pluck('queue_number')
            ->toArray();

        $this->assertCount($countPerPolyclinic * 2, array_unique($allQueueNumbers));
    }

    /**
     * Test that the unique constraint covers the combined triple (polyclinic_id, queue_sequence, registration_date).
     *
     * Same sequence on different dates is allowed; same sequence on different polyclinics is allowed.
     */
    public function test_uniqueness_scoped_to_polyclinic_and_date_combination(): void
    {
        $date1 = Carbon::parse('2024-08-01');
        $date2 = Carbon::parse('2024-08-02');

        $patient1 = $this->createPatient(1);
        $patient2 = $this->createPatient(2);
        $patient3 = $this->createPatient(3);

        // Issue queue_sequence=1 for polyclinic A on date1
        Carbon::setTestNow($date1);
        $reg1 = $this->queueService->issueQueueNumber(
            $patient1->id,
            $this->polyclinic->id,
            $this->doctor->id,
            $date1
        );

        // Issue queue_sequence=1 for polyclinic A on date2 (allowed — different date)
        Carbon::setTestNow($date2);
        $reg2 = $this->queueService->issueQueueNumber(
            $patient2->id,
            $this->polyclinic->id,
            $this->doctor->id,
            $date2
        );

        // Both should have sequence 1 since they are on different dates
        $this->assertSame(1, $reg1->queue_sequence);
        $this->assertSame(1, $reg2->queue_sequence);

        // Create second polyclinic and issue sequence 1 on date1 (allowed — different polyclinic)
        $polyclinicC = Polyclinic::create(['name' => 'Mata', 'code' => 'C']);
        $doctorC = Doctor::create(['name' => 'Dr. Wati', 'polyclinic_id' => $polyclinicC->id]);

        Carbon::setTestNow($date1);
        $reg3 = $this->queueService->issueQueueNumber(
            $patient3->id,
            $polyclinicC->id,
            $doctorC->id,
            $date1
        );

        $this->assertSame(1, $reg3->queue_sequence);

        // All three registrations exist without constraint violations
        $this->assertDatabaseCount('registrations', 3);
    }
}
