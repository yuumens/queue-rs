<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end feature test for the full new patient flow.
 *
 * Flow: GET / → click "Pasien Baru" → fill form → POST /register
 *       → GET /queue/select → select polyclinic + doctor → POST /queue/generate
 *       → GET /queue/ticket/{id}
 *
 * Asserts at each step: correct redirect, correct session state, correct DB records,
 * correct view content.
 *
 * Validates: Requirements 1.4, 3.5, 4.5, 5.4, 5.6, 6.1
 */
class EndToEndNewPatientTest extends TestCase
{
    use RefreshDatabase;

    private Polyclinic $polyclinic;
    private Doctor $doctor;
    private PracticeSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Fix the current date to a Wednesday (dayOfWeek = 3)
        Carbon::setTestNow(Carbon::parse('2024-07-17 09:00:00')); // Wednesday

        // Seed polyclinic, doctor, and practice schedule for today
        $this->polyclinic = Polyclinic::create([
            'name' => 'Penyakit Dalam',
            'code' => 'A',
        ]);

        $this->doctor = Doctor::create([
            'name' => 'Dr. Budi Santoso',
            'polyclinic_id' => $this->polyclinic->id,
        ]);

        $this->schedule = PracticeSchedule::create([
            'doctor_id' => $this->doctor->id,
            'polyclinic_id' => $this->polyclinic->id,
            'day_of_week' => Carbon::now()->dayOfWeek, // Wednesday = 3
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset frozen time
        parent::tearDown();
    }

    /**
     * Test Step 1: GET / returns 200 and shows "Pasien Baru" link.
     *
     * Validates: Requirement 1.4
     */
    public function test_step1_home_page_displays_pasien_baru_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Pasien Baru');
        $response->assertSee(route('patient.register'));
    }

    /**
     * Test Step 2: POST /register with valid data → 302 to queue.select,
     * patient in DB, session has patient_id.
     *
     * Validates: Requirements 3.5, 1.4
     */
    public function test_step2_register_new_patient_redirects_to_queue_select(): void
    {
        $patientData = [
            'full_name' => 'Siti Rahayu',
            'date_of_birth' => '1990-05-15',
            'address' => 'Jl. Melati No. 3, Surabaya',
            'nik' => '3578012345670001',
        ];

        $response = $this->post('/register', $patientData);

        // Assert redirect to queue.select
        $response->assertRedirect(route('queue.select'));

        // Assert patient is in DB
        $this->assertDatabaseHas('patients', [
            'full_name' => 'Siti Rahayu',
            'nik' => '3578012345670001',
            'date_of_birth' => '1990-05-15',
            'address' => 'Jl. Melati No. 3, Surabaya',
        ]);

        // Assert MR number is correctly formatted
        $patient = Patient::where('nik', '3578012345670001')->first();
        $this->assertNotNull($patient);
        $this->assertMatchesRegularExpression('/^RM-\d{6}$/', $patient->medical_record_number);

        // Assert session has patient_id
        $response->assertSessionHas('patient_id', $patient->id);
    }

    /**
     * Test Step 3: GET /queue/select with session → 200.
     *
     * Validates: Requirement 4.5
     */
    public function test_step3_queue_select_page_accessible_with_session(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik' => '3578012345670001',
            'full_name' => 'Siti Rahayu',
            'date_of_birth' => '1990-05-15',
            'address' => 'Jl. Melati No. 3, Surabaya',
        ]);

        $response = $this->withSession(['patient_id' => $patient->id])
            ->get('/queue/select');

        $response->assertStatus(200);
    }

    /**
     * Test Step 4: POST /queue/generate with polyclinic_id and doctor_id → 302 to queue.ticket.
     *
     * Validates: Requirements 5.4, 5.6
     */
    public function test_step4_queue_generate_creates_registration_and_redirects_to_ticket(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik' => '3578012345670001',
            'full_name' => 'Siti Rahayu',
            'date_of_birth' => '1990-05-15',
            'address' => 'Jl. Melati No. 3, Surabaya',
        ]);

        $response = $this->withSession(['patient_id' => $patient->id])
            ->post('/queue/generate', [
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
            ]);

        // Assert redirect to ticket page
        $registration = Registration::first();
        $this->assertNotNull($registration);
        $response->assertRedirect(route('queue.ticket', $registration));

        // Assert registration record is correct
        $this->assertSame($patient->id, $registration->patient_id);
        $this->assertSame($this->polyclinic->id, $registration->polyclinic_id);
        $this->assertSame($this->doctor->id, $registration->doctor_id);
        $this->assertSame('A-01', $registration->queue_number);
        $this->assertSame(1, $registration->queue_sequence);
        $this->assertEquals('2024-07-17', $registration->registration_date->toDateString());
    }

    /**
     * Test Step 5: GET /queue/ticket/{id} → 200, see all ticket data.
     *
     * Validates: Requirement 6.1
     */
    public function test_step5_ticket_page_displays_all_required_data(): void
    {
        $patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik' => '3578012345670001',
            'full_name' => 'Siti Rahayu',
            'date_of_birth' => '1990-05-15',
            'address' => 'Jl. Melati No. 3, Surabaya',
        ]);

        $registration = Registration::create([
            'patient_id' => $patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'queue_number' => 'A-01',
            'queue_sequence' => 1,
            'registration_date' => '2024-07-17',
        ]);

        $response = $this->withSession(['patient_id' => $patient->id])
            ->get(route('queue.ticket', $registration));

        $response->assertStatus(200);
        $response->assertSee('Siti Rahayu');
        $response->assertSee('RM-000001');
        $response->assertSee('Penyakit Dalam');
        $response->assertSee('Dr. Budi Santoso');
        $response->assertSee('A-01');
        $response->assertSee('17 July 2024');
    }

    /**
     * Test the complete flow end-to-end in a single test method,
     * verifying correct state transitions across all steps.
     *
     * Validates: Requirements 1.4, 3.5, 4.5, 5.4, 5.6, 6.1
     */
    public function test_full_new_patient_flow_end_to_end(): void
    {
        // Step 1: Visit home page
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Pasien Baru');

        // Step 2: Register new patient
        $patientData = [
            'full_name' => 'Ahmad Wijaya',
            'date_of_birth' => '1985-03-20',
            'address' => 'Jl. Kenanga No. 10, Jakarta',
            'nik' => '3171012345670002',
        ];

        $response = $this->post('/register', $patientData);
        $response->assertRedirect(route('queue.select'));

        // Verify patient was created in the DB
        $patient = Patient::where('nik', '3171012345670002')->first();
        $this->assertNotNull($patient);
        $this->assertSame('Ahmad Wijaya', $patient->full_name);
        $this->assertMatchesRegularExpression('/^RM-\d{6}$/', $patient->medical_record_number);

        // Step 3: Access queue select page (follow redirect)
        $response = $this->withSession(['patient_id' => $patient->id])
            ->get('/queue/select');
        $response->assertStatus(200);

        // Step 4: Generate queue number
        $response = $this->withSession(['patient_id' => $patient->id])
            ->post('/queue/generate', [
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
            ]);

        // Verify registration record
        $registration = Registration::where('patient_id', $patient->id)->first();
        $this->assertNotNull($registration);
        $this->assertSame('A-01', $registration->queue_number);
        $this->assertSame(1, $registration->queue_sequence);
        $this->assertSame($this->polyclinic->id, $registration->polyclinic_id);
        $this->assertSame($this->doctor->id, $registration->doctor_id);
        $this->assertEquals('2024-07-17', $registration->registration_date->toDateString());

        // Assert redirect to ticket page
        $response->assertRedirect(route('queue.ticket', $registration));

        // Step 5: View ticket
        $response = $this->withSession(['patient_id' => $patient->id])
            ->get(route('queue.ticket', $registration));

        $response->assertStatus(200);
        $response->assertSee('Ahmad Wijaya');
        $response->assertSee($patient->medical_record_number);
        $response->assertSee('Penyakit Dalam');
        $response->assertSee('Dr. Budi Santoso');
        $response->assertSee('A-01');
        $response->assertSee('17 July 2024');
    }

    /**
     * Test that accessing queue select without session redirects to home.
     *
     * Validates: Requirement 4.5 (session guard)
     */
    public function test_queue_select_without_session_redirects_to_home(): void
    {
        $response = $this->get('/queue/select');

        $response->assertRedirect('/');
    }

    /**
     * Test that queue generate without session redirects to home.
     *
     * Validates: Requirement 5.6 (session guard)
     */
    public function test_queue_generate_without_session_redirects_to_home(): void
    {
        $response = $this->post('/queue/generate', [
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
        ]);

        $response->assertRedirect('/');
    }
}
