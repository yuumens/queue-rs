<?php

namespace Tests\Feature;

use App\Livewire\PatientSearch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * End-to-end feature test for the full returning patient flow.
 *
 * Flow: GET / → click "Pasien Lama" → search by MR number → confirm
 *       → GET /queue/select → select polyclinic + doctor → POST /queue/generate
 *       → GET /queue/ticket/{id}
 *
 * Asserts session contains correct patient_id after confirm, ticket shows correct patient.
 *
 * Validates: Requirements 1.5, 2.2, 2.7, 4.5, 5.4, 6.1
 */
class EndToEndReturningPatientTest extends TestCase
{
    use RefreshDatabase;

    private Polyclinic $polyclinic;
    private Doctor $doctor;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Fix the current date to a Wednesday (dayOfWeek = 3)
        Carbon::setTestNow(Carbon::parse('2024-07-17 09:00:00')); // Wednesday

        // Seed an existing patient in the DB
        $this->patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik' => '3578012345670001',
            'full_name' => 'Budi Hartono',
            'date_of_birth' => '1980-08-25',
            'address' => 'Jl. Merdeka No. 45, Bandung',
        ]);

        // Seed polyclinic, doctor, and practice schedule for today
        $this->polyclinic = Polyclinic::create([
            'name' => 'Penyakit Dalam',
            'code' => 'A',
        ]);

        $this->doctor = Doctor::create([
            'name' => 'Dr. Andi Pratama',
            'polyclinic_id' => $this->polyclinic->id,
        ]);

        PracticeSchedule::create([
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
     * Test Step 1: GET / returns 200 and shows "Pasien Lama" link.
     *
     * Validates: Requirement 1.5
     */
    public function test_step1_home_page_displays_pasien_lama_link(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Pasien Lama');
        $response->assertSee(route('patient.verify'));
    }

    /**
     * Test Step 2: GET /verify returns 200 (verification page).
     *
     * Validates: Requirement 2.2
     */
    public function test_step2_verify_page_accessible(): void
    {
        $response = $this->get('/verify');

        $response->assertStatus(200);
    }

    /**
     * Test Step 3: Livewire PatientSearch can search by MR number and find the patient.
     *
     * Validates: Requirement 2.2
     */
    public function test_step3_livewire_search_by_mr_number_finds_patient(): void
    {
        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', 'RM-000001')
            ->call('search')
            ->assertSet('results', function ($results) {
                return count($results) === 1
                    && $results[0]['medical_record_number'] === 'RM-000001'
                    && $results[0]['full_name'] === 'Budi Hartono';
            });
    }

    /**
     * Test Step 4: Livewire PatientSearch can select a patient from results.
     *
     * Validates: Requirement 2.2
     */
    public function test_step4_livewire_select_patient(): void
    {
        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', 'RM-000001')
            ->call('search')
            ->call('selectPatient', $this->patient->id)
            ->assertSet('selectedPatient.id', $this->patient->id)
            ->assertSet('selectedPatient.full_name', 'Budi Hartono');
    }

    /**
     * Test Step 5: POST /verify/confirm with patient_id → redirect to queue.select, session has patient_id.
     *
     * Validates: Requirement 2.7
     */
    public function test_step5_confirm_stores_patient_in_session_and_redirects(): void
    {
        $response = $this->post('/verify/confirm', [
            'patient_id' => $this->patient->id,
        ]);

        $response->assertRedirect(route('queue.select'));
        $response->assertSessionHas('patient_id', $this->patient->id);
    }

    /**
     * Test Step 6: GET /queue/select with session → 200.
     *
     * Validates: Requirement 4.5
     */
    public function test_step6_queue_select_accessible_after_confirm(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get('/queue/select');

        $response->assertStatus(200);
    }

    /**
     * Test Step 7: POST /queue/generate → creates registration and redirects to ticket.
     *
     * Validates: Requirement 5.4
     */
    public function test_step7_queue_generate_creates_registration(): void
    {
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->post('/queue/generate', [
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
            ]);

        $registration = Registration::first();
        $this->assertNotNull($registration);
        $response->assertRedirect(route('queue.ticket', $registration));

        $this->assertSame($this->patient->id, $registration->patient_id);
        $this->assertSame($this->polyclinic->id, $registration->polyclinic_id);
        $this->assertSame($this->doctor->id, $registration->doctor_id);
        $this->assertSame('A-01', $registration->queue_number);
        $this->assertSame(1, $registration->queue_sequence);
        $this->assertEquals('2024-07-17', $registration->registration_date->toDateString());
    }

    /**
     * Test Step 8: GET /queue/ticket/{id} → 200, shows correct patient data.
     *
     * Validates: Requirement 6.1
     */
    public function test_step8_ticket_displays_correct_returning_patient_data(): void
    {
        $registration = Registration::create([
            'patient_id' => $this->patient->id,
            'polyclinic_id' => $this->polyclinic->id,
            'doctor_id' => $this->doctor->id,
            'queue_number' => 'A-01',
            'queue_sequence' => 1,
            'registration_date' => '2024-07-17',
        ]);

        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $registration));

        $response->assertStatus(200);
        $response->assertSee('Budi Hartono');
        $response->assertSee('RM-000001');
        $response->assertSee('Penyakit Dalam');
        $response->assertSee('Dr. Andi Pratama');
        $response->assertSee('A-01');
        $response->assertSee('17 July 2024');
    }

    /**
     * Test the complete returning patient flow end-to-end in a single test method,
     * verifying correct state transitions across all steps.
     *
     * Validates: Requirements 1.5, 2.2, 2.7, 4.5, 5.4, 6.1
     */
    public function test_full_returning_patient_flow_end_to_end(): void
    {
        // Step 1: Visit home page and see "Pasien Lama"
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Pasien Lama');

        // Step 2: Visit verify page
        $response = $this->get('/verify');
        $response->assertStatus(200);

        // Step 3: Use Livewire to search by MR number and select the patient
        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', 'RM-000001')
            ->call('search')
            ->assertSet('results', function ($results) {
                return count($results) === 1
                    && $results[0]['medical_record_number'] === 'RM-000001';
            })
            ->call('selectPatient', $this->patient->id)
            ->assertSet('selectedPatient.id', $this->patient->id);

        // Step 4: Confirm patient identity → session stores patient_id, redirect to queue.select
        $response = $this->post('/verify/confirm', [
            'patient_id' => $this->patient->id,
        ]);
        $response->assertRedirect(route('queue.select'));
        $response->assertSessionHas('patient_id', $this->patient->id);

        // Step 5: Access queue select page
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get('/queue/select');
        $response->assertStatus(200);

        // Step 6: Generate queue number
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->post('/queue/generate', [
                'polyclinic_id' => $this->polyclinic->id,
                'doctor_id' => $this->doctor->id,
            ]);

        // Verify registration record
        $registration = Registration::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($registration);
        $this->assertSame('A-01', $registration->queue_number);
        $this->assertSame(1, $registration->queue_sequence);
        $this->assertSame($this->polyclinic->id, $registration->polyclinic_id);
        $this->assertSame($this->doctor->id, $registration->doctor_id);
        $this->assertEquals('2024-07-17', $registration->registration_date->toDateString());

        // Assert redirect to ticket page
        $response->assertRedirect(route('queue.ticket', $registration));

        // Step 7: View ticket — verify all correct patient data is displayed
        $response = $this->withSession(['patient_id' => $this->patient->id])
            ->get(route('queue.ticket', $registration));

        $response->assertStatus(200);
        $response->assertSee('Budi Hartono');
        $response->assertSee('RM-000001');
        $response->assertSee('Penyakit Dalam');
        $response->assertSee('Dr. Andi Pratama');
        $response->assertSee('A-01');
        $response->assertSee('17 July 2024');
    }
}
