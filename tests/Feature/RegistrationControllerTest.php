<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature tests for the new patient registration flow (RegistrationController).
 *
 * Covers:
 *   - Valid data → 302 redirect to queue.select, patient in DB     – Requirements 3.1, 3.5
 *   - Duplicate NIK → redirect back with error, no new record      – Requirement 3.3
 *   - Missing fields → redirect back with validation errors         – Requirement 3.2
 *   - Property 7: Registration Persists All Patient Data (Round-Trip)
 *   - Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
 */
class RegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return a fully valid patient registration payload.
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name'     => 'Siti Aminah',
            'date_of_birth' => '1985-03-22',
            'address'       => 'Jl. Kenanga No. 10, Bandung',
            'nik'           => '3204015503850001',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Test: Valid data → 302 to queue.select, patient record in DB
    // Validates: Requirements 3.1, 3.4, 3.5
    // -------------------------------------------------------------------------

    public function test_valid_registration_redirects_to_queue_select(): void
    {
        $payload = $this->validPayload();

        $response = $this->post(route('patient.store'), $payload);

        $response->assertRedirect(route('queue.select'));
    }

    public function test_valid_registration_creates_patient_in_database(): void
    {
        $payload = $this->validPayload();

        $this->post(route('patient.store'), $payload);

        $this->assertDatabaseHas('patients', [
            'nik'       => $payload['nik'],
            'full_name' => $payload['full_name'],
            'address'   => $payload['address'],
        ]);
    }

    public function test_valid_registration_generates_mr_number(): void
    {
        $payload = $this->validPayload();

        $this->post(route('patient.store'), $payload);

        $patient = Patient::where('nik', $payload['nik'])->first();

        $this->assertNotNull($patient);
        $this->assertMatchesRegularExpression('/^RM-\d{6}$/', $patient->medical_record_number);
    }

    public function test_valid_registration_stores_patient_id_in_session(): void
    {
        $payload = $this->validPayload();

        $response = $this->post(route('patient.store'), $payload);

        $patient = Patient::where('nik', $payload['nik'])->first();

        $response->assertSessionHas('patient_id', $patient->id);
    }

    // -------------------------------------------------------------------------
    // Test: Duplicate NIK → redirect back with error, no new record
    // Validates: Requirement 3.3
    // -------------------------------------------------------------------------

    public function test_duplicate_nik_redirects_back_with_error(): void
    {
        // Create a patient with a specific NIK first
        Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '3204015503850001',
            'full_name'             => 'Existing Patient',
            'date_of_birth'         => '1985-03-22',
            'address'               => 'Jl. Existing No. 1',
        ]);

        // Attempt to register with the same NIK
        $payload = $this->validPayload(['nik' => '3204015503850001']);

        $response = $this->from(route('patient.register'))
            ->post(route('patient.store'), $payload);

        $response->assertRedirect(route('patient.register'));
        $response->assertSessionHasErrors('nik');
    }

    public function test_duplicate_nik_does_not_create_new_record(): void
    {
        Patient::create([
            'medical_record_number' => 'RM-000001',
            'nik'                   => '3204015503850001',
            'full_name'             => 'Existing Patient',
            'date_of_birth'         => '1985-03-22',
            'address'               => 'Jl. Existing No. 1',
        ]);

        $payload = $this->validPayload(['nik' => '3204015503850001']);

        $this->from(route('patient.register'))
            ->post(route('patient.store'), $payload);

        $this->assertDatabaseCount('patients', 1);
    }

    // -------------------------------------------------------------------------
    // Test: Missing fields → redirect back with validation errors
    // Validates: Requirement 3.2
    // -------------------------------------------------------------------------

    #[DataProvider('missingFieldProvider')]
    public function test_missing_required_field_redirects_back_with_errors(string $field): void
    {
        $payload = $this->validPayload();
        unset($payload[$field]);

        $response = $this->from(route('patient.register'))
            ->post(route('patient.store'), $payload);

        $response->assertRedirect(route('patient.register'));
        $response->assertSessionHasErrors($field);
    }

    public static function missingFieldProvider(): array
    {
        return [
            'missing full_name'     => ['full_name'],
            'missing date_of_birth' => ['date_of_birth'],
            'missing address'       => ['address'],
            'missing nik'           => ['nik'],
        ];
    }

    #[DataProvider('missingFieldProvider')]
    public function test_missing_required_field_does_not_create_patient(string $field): void
    {
        $payload = $this->validPayload();
        unset($payload[$field]);

        $this->from(route('patient.register'))
            ->post(route('patient.store'), $payload);

        $this->assertDatabaseCount('patients', 0);
    }

    // -------------------------------------------------------------------------
    // Property 7: Registration Persists All Patient Data (Round-Trip)
    //
    // For any valid new patient form submission, after the patient is created,
    // querying the database by the returned Medical_Record_Number shall retrieve
    // a record with identical values for full name, date of birth, address, and
    // NIK to those submitted in the form.
    //
    // Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6
    // -------------------------------------------------------------------------

    #[DataProvider('roundTripPatientDataProvider')]
    public function test_property7_registration_persists_all_patient_data_round_trip(
        string $fullName,
        string $dateOfBirth,
        string $address,
        string $nik
    ): void {
        $payload = [
            'full_name'     => $fullName,
            'date_of_birth' => $dateOfBirth,
            'address'       => $address,
            'nik'           => $nik,
        ];

        $response = $this->post(route('patient.store'), $payload);

        // Should redirect successfully
        $response->assertRedirect(route('queue.select'));

        // Query the DB and assert all submitted fields match
        $patient = Patient::where('nik', $nik)->first();

        $this->assertNotNull($patient, 'Patient record should exist in the database');
        $this->assertEquals($fullName, $patient->full_name);
        $this->assertEquals($dateOfBirth, (string) $patient->date_of_birth);
        $this->assertEquals($address, $patient->address);
        $this->assertEquals($nik, $patient->nik);
        $this->assertMatchesRegularExpression('/^RM-\d{6}$/', $patient->medical_record_number);
    }

    /**
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
     *
     * Data provider for Property 7 round-trip test covering varied inputs.
     */
    public static function roundTripPatientDataProvider(): array
    {
        return [
            'standard name and address' => [
                'Budi Santoso',
                '1990-05-15',
                'Jl. Mawar No. 1, Jakarta Selatan',
                '3175021505900001',
            ],
            'long name with multiple words' => [
                'Muhammad Rizki Adi Pratama Putra',
                '2000-12-31',
                'Perumahan Griya Indah Blok C-12, RT 003/RW 005, Kelurahan Sukamaju, Kecamatan Cibeunying, Kota Bandung, Jawa Barat 40123',
                '3273011231000002',
            ],
            'name with apostrophe' => [
                "Ni Luh Made Dewi A'isyah",
                '1975-01-01',
                'Br. Kawan, Desa Adat Sesetan, Denpasar',
                '5171024101750003',
            ],
            'minimum date boundary (today)' => [
                'Bayi Baru Lahir',
                now()->format('Y-m-d'),
                'RS Harapan Kita, Jakarta',
                '3174010101240004',
            ],
            'short address' => [
                'Andi',
                '1999-06-15',
                'Makassar',
                '7371011506990005',
            ],
        ];
    }
}
