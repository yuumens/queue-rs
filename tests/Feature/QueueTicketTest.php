<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature tests for the queue ticket display page (QueueController::ticket).
 *
 * Covers:
 *   - Valid registration → 200, page contains queue_number, patient name,
 *     polyclinic name, doctor name, registration_date, MR number
 *   - Invalid registration ID → 404
 *   - Property 15: Queue Ticket Displays All Required Patient and Queue Data
 *
 * Validates: Requirements 6.1
 */
class QueueTicketTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private Polyclinic $polyclinic;
    private Doctor $doctor;
    private Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '1234567890123456',
            'full_name'             => 'Siti Rahayu',
            'date_of_birth'         => '1990-05-15',
            'address'               => 'Jl. Melati No. 3, Surabaya',
        ]);

        $this->polyclinic = Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $this->doctor = Doctor::create(['name' => 'Dr. Budi Santoso', 'polyclinic_id' => $this->polyclinic->id]);

        $this->registration = Registration::create([
            'patient_id'        => $this->patient->id,
            'polyclinic_id'     => $this->polyclinic->id,
            'doctor_id'         => $this->doctor->id,
            'queue_number'      => 'A-01',
            'queue_sequence'    => 1,
            'registration_date' => '2024-07-15',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test: valid registration → 200, page contains all required data
    // Validates: Requirement 6.1
    // -------------------------------------------------------------------------

    public function test_ticket_page_returns_200_for_valid_registration(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $this->registration));

        $response->assertStatus(200);
    }

    public function test_ticket_page_contains_queue_number(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $this->registration));

        $response->assertSee($this->registration->queue_number);
    }

    public function test_ticket_page_contains_patient_name(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $this->registration));

        $response->assertSee($this->patient->full_name);
    }

    public function test_ticket_page_contains_polyclinic_name(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $this->registration));

        $response->assertSee($this->polyclinic->name);
    }

    public function test_ticket_page_contains_doctor_name(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $this->registration));

        $response->assertSee($this->doctor->name);
    }

    public function test_ticket_page_contains_registration_date(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $this->registration));

        // The view formats the date as 'd F Y' (e.g. "15 July 2024")
        $response->assertSee('15 July 2024');
    }

    public function test_ticket_page_contains_medical_record_number(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $this->registration));

        $response->assertSee($this->patient->medical_record_number);
    }

    // -------------------------------------------------------------------------
    // Test: invalid registration ID → 404
    // -------------------------------------------------------------------------

    public function test_ticket_page_returns_404_for_invalid_registration_id(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', ['registration' => 99999]));

        $response->assertStatus(404);
    }

    // =========================================================================
    // Property 15: Queue Ticket Displays All Required Patient and Queue Data
    //
    // For any valid Registration record, the rendered queue ticket page shall
    // contain the patient's full name, Medical_Record_Number, polyclinic name,
    // doctor name, Queue_Number, and registration date.
    //
    // Validates: Requirements 6.1
    // =========================================================================

    #[DataProvider('ticketDisplayDataProvider')]
    public function test_property15_ticket_displays_all_required_data(
        string $patientName,
        string $mrNumber,
        string $nik,
        string $polyclinicName,
        string $polyclinicCode,
        string $doctorName,
        string $queueNumber,
        int $queueSequence,
        string $registrationDate,
        string $expectedDateDisplay
    ): void {
        $patient = Patient::create([
            'medical_record_number' => $mrNumber,
            'nik'                   => $nik,
            'full_name'             => $patientName,
            'date_of_birth'         => '1985-03-20',
            'address'               => 'Jl. Test No. 1',
        ]);

        $polyclinic = Polyclinic::create(['name' => $polyclinicName, 'code' => $polyclinicCode]);
        $doctor = Doctor::create(['name' => $doctorName, 'polyclinic_id' => $polyclinic->id]);

        $registration = Registration::create([
            'patient_id'        => $patient->id,
            'polyclinic_id'     => $polyclinic->id,
            'doctor_id'         => $doctor->id,
            'queue_number'      => $queueNumber,
            'queue_sequence'    => $queueSequence,
            'registration_date' => $registrationDate,
        ]);

        $response = $this->withSession(['patient_id' => $patient->id])
            ->get(route('queue.ticket', $registration));

        $response->assertStatus(200);
        $response->assertSee($patientName);
        $response->assertSee($mrNumber);
        $response->assertSee($polyclinicName);
        $response->assertSee($doctorName);
        $response->assertSee($queueNumber);
        $response->assertSee($expectedDateDisplay);
    }

    /**
     * **Validates: Requirements 6.1**
     */
    public static function ticketDisplayDataProvider(): array
    {
        return [
            'internal medicine patient' => [
                'Ahmad Fauzi',
                'RM-000010',
                '3201010101010001',
                'Penyakit Dalam',
                'PD',
                'Dr. Rina Wijaya',
                'PD-01',
                1,
                '2024-08-01',
                '01 August 2024',
            ],
            'pediatrics patient' => [
                'Dewi Lestari',
                'RM-000020',
                '3201020202020002',
                'Anak',
                'AN',
                'Dr. Hendra Kusuma',
                'AN-03',
                3,
                '2024-12-25',
                '25 December 2024',
            ],
            'ophthalmology patient' => [
                'Bambang Supriadi',
                'RM-000030',
                '3201030303030003',
                'Mata',
                'MT',
                'Dr. Sari Indah',
                'MT-10',
                10,
                '2024-01-02',
                '02 January 2024',
            ],
        ];
    }
}
