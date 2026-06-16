<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use App\Services\PatientService;
use App\Services\QueueService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for safe error messages on DB constraint violations.
 *
 * Property 19: DB Constraint Violations Return Safe Error Messages
 *
 * For any operation that triggers a database constraint violation (duplicate NIK,
 * duplicate MR number, duplicate queue number), the HTTP response returned to the
 * client shall not contain raw SQL, database driver error text, or stack trace details.
 *
 * Validates: Requirements 8.4
 */
class SafeErrorMessageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unsafe patterns that must never appear in user-facing responses.
     */
    private const UNSAFE_PATTERNS = [
        'SQLSTATE',
        'PDOException',
        'QueryException',
        'Stack trace',
        '#0 ',
        'vendor/',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Disable debug mode so the QueryException handler returns safe messages
        $this->app['config']->set('app.debug', false);
    }

    /**
     * Assert that a response body does not contain any unsafe DB error details.
     */
    private function assertResponseIsSafe(string $content): void
    {
        foreach (self::UNSAFE_PATTERNS as $pattern) {
            $this->assertStringNotContainsString(
                $pattern,
                $content,
                "Response body must not contain '{$pattern}' — raw DB error details are leaking."
            );
        }
    }

    // -------------------------------------------------------------------------
    // Test: Duplicate NIK via form validation → response is safe (no SQL exposed)
    // -------------------------------------------------------------------------

    public function test_duplicate_nik_via_validation_returns_safe_error(): void
    {
        Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '3204015503850001',
            'full_name'             => 'Existing Patient',
            'date_of_birth'         => '1985-03-22',
            'address'               => 'Jl. Existing No. 1',
        ]);

        $response = $this->post(route('patient.store'), [
            'full_name'     => 'Another Patient',
            'date_of_birth' => '1990-01-01',
            'address'       => 'Jl. Baru No. 5',
            'nik'           => '3204015503850001',
        ]);

        // Follow redirect to see rendered content
        if ($response->isRedirect()) {
            $followUp = $this->get($response->headers->get('Location'));
            $this->assertResponseIsSafe($followUp->getContent());
        } else {
            $this->assertResponseIsSafe($response->getContent());
        }
    }

    // -------------------------------------------------------------------------
    // Test: QueryException from PatientService (HTML response) → safe message
    // This simulates a race condition where validation passes but DB insert fails
    // -------------------------------------------------------------------------

    public function test_query_exception_during_registration_returns_safe_html_response(): void
    {
        $this->mock(PatientService::class, function ($mock) {
            $mock->shouldReceive('createPatient')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO patients (nik, full_name) VALUES (?, ?)',
                    ['3204015503850099', 'New Patient'],
                    new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'3204015503850099\' for key \'patients_nik_unique\'')
                ));
        });

        $response = $this->from(route('patient.register'))
            ->post(route('patient.store'), [
                'full_name'     => 'New Patient',
                'date_of_birth' => '1990-01-01',
                'address'       => 'Jl. Baru No. 5',
                'nik'           => '3204015503850099',
            ]);

        // RegistrationController catches QueryException and redirects back with error
        $response->assertRedirect(route('patient.register'));
        $response->assertSessionHasErrors('general');

        // Follow the redirect and check the rendered page is safe
        $followUp = $this->get(route('patient.register'));
        $this->assertResponseIsSafe($followUp->getContent());
    }

    // -------------------------------------------------------------------------
    // Test: QueryException from PatientService (JSON response) → safe message
    // -------------------------------------------------------------------------

    public function test_query_exception_during_registration_returns_safe_json_response(): void
    {
        $this->mock(PatientService::class, function ($mock) {
            $mock->shouldReceive('createPatient')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO patients (nik, full_name) VALUES (?, ?)',
                    ['3204015503850099', 'New Patient'],
                    new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'3204015503850099\' for key \'patients_nik_unique\'')
                ));
        });

        // JSON request — the controller catches QueryException and redirects,
        // but for JSON the global handler would handle it. Let's check:
        // Actually, RegistrationController always redirects (it doesn't check expectsJson).
        // The response on a JSON POST that gets redirect will be a redirect response.
        $response = $this->postJson(route('patient.store'), [
            'full_name'     => 'New Patient',
            'date_of_birth' => '1990-01-01',
            'address'       => 'Jl. Baru No. 5',
            'nik'           => '3204015503850099',
        ]);

        $content = $response->getContent();
        $this->assertResponseIsSafe($content);
    }

    // -------------------------------------------------------------------------
    // Test: QueryException from QueueService (HTML response) → safe message
    // Simulates a queue uniqueness constraint violation
    // -------------------------------------------------------------------------

    public function test_query_exception_during_queue_generation_returns_safe_html_response(): void
    {
        $polyclinic = Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $doctor = Doctor::create(['name' => 'Dr. Test', 'polyclinic_id' => $polyclinic->id]);
        PracticeSchedule::create([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => now()->dayOfWeek,
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ]);
        $patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '3204015503850001',
            'full_name'             => 'Test Patient',
            'date_of_birth'         => '1985-03-22',
            'address'               => 'Jl. Test No. 1',
        ]);

        $this->mock(QueueService::class, function ($mock) {
            $mock->shouldReceive('issueQueueNumber')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO registrations (polyclinic_id, queue_sequence, registration_date) VALUES (?, ?, ?)',
                    [1, 1, '2025-01-01'],
                    new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1-1-2025-01-01\' for key \'registrations_polyclinic_id_queue_sequence_registration_date_unique\'')
                ));
        });

        $response = $this->withSession(['patient_id' => $patient->id])
            ->from(route('queue.select'))
            ->post(route('queue.generate'), [
                'polyclinic_id' => $polyclinic->id,
                'doctor_id'     => $doctor->id,
            ]);

        // QueueController catches QueryException and redirects back
        $response->assertRedirect();

        // Follow redirect and verify rendered content is safe
        $followUp = $this->withSession(['patient_id' => $patient->id])
            ->get(route('queue.select'));
        $this->assertResponseIsSafe($followUp->getContent());
    }

    // -------------------------------------------------------------------------
    // Test: QueryException from QueueService (JSON response) → safe message
    // -------------------------------------------------------------------------

    public function test_query_exception_during_queue_generation_returns_safe_json_response(): void
    {
        $polyclinic = Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $doctor = Doctor::create(['name' => 'Dr. Test', 'polyclinic_id' => $polyclinic->id]);
        PracticeSchedule::create([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => now()->dayOfWeek,
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ]);
        $patient = Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '3204015503850001',
            'full_name'             => 'Test Patient',
            'date_of_birth'         => '1985-03-22',
            'address'               => 'Jl. Test No. 1',
        ]);

        $this->mock(QueueService::class, function ($mock) {
            $mock->shouldReceive('issueQueueNumber')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'INSERT INTO registrations (polyclinic_id, queue_sequence, registration_date) VALUES (?, ?, ?)',
                    [1, 1, '2025-01-01'],
                    new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry \'1-1-2025-01-01\' for key \'registrations_polyclinic_id_queue_sequence_registration_date_unique\'')
                ));
        });

        $response = $this->withSession(['patient_id' => $patient->id])
            ->postJson(route('queue.generate'), [
                'polyclinic_id' => $polyclinic->id,
                'doctor_id'     => $doctor->id,
            ]);

        $content = $response->getContent();
        $this->assertResponseIsSafe($content);
    }

    // -------------------------------------------------------------------------
    // Test: Global QueryException handler (bypass controller try-catch)
    // Tests bootstrap/app.php renderable for QueryException with debug=false
    // -------------------------------------------------------------------------

    public function test_global_query_exception_handler_returns_safe_json(): void
    {
        // Register a test route that directly throws a QueryException
        // to test the global handler in bootstrap/app.php
        \Illuminate\Support\Facades\Route::post('/test-db-error', function () {
            throw new QueryException(
                'mysql',
                'INSERT INTO patients (nik) VALUES (?)',
                ['duplicate_value'],
                new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry')
            );
        });

        $response = $this->postJson('/test-db-error');

        $content = $response->getContent();
        $this->assertResponseIsSafe($content);
        $response->assertStatus(500);
        $response->assertJson(['message' => 'Terjadi kesalahan pada sistem. Silakan coba lagi.']);
    }

    public function test_global_query_exception_handler_returns_safe_html(): void
    {
        // Register a test route that directly throws a QueryException
        \Illuminate\Support\Facades\Route::post('/test-db-error', function () {
            throw new QueryException(
                'mysql',
                'INSERT INTO patients (nik) VALUES (?)',
                ['duplicate_value'],
                new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry')
            );
        });

        $response = $this->from('/previous-page')
            ->post('/test-db-error');

        // Global handler redirects back with errors for non-JSON requests
        $response->assertRedirect('/previous-page');
        $response->assertSessionHasErrors('general');

        // The session error message should be safe
        $errors = session('errors');
        $this->assertNotNull($errors);
        $generalError = $errors->first('general');
        $this->assertResponseIsSafe($generalError);
    }
}
