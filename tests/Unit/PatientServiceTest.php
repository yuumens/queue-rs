<?php

namespace Tests\Unit;

use App\Exceptions\DuplicateNikException;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for PatientService.
 *
 * Covers:
 *   - MR number format (RM-\d{6})                     – Requirement 3.4, 8.1
 *   - Uniqueness of sequential MR numbers              – Requirement 3.4, 8.1
 *   - Duplicate NIK rejection                          – Requirement 3.3, 8.2
 *   - Property 5: Duplicate NIK Is Always Rejected     – Validates: Requirements 3.3, 8.2
 *   - Property 6: Generated MR Numbers Are Unique and Correctly Formatted – Validates: Requirements 3.4, 8.1
 */
class PatientServiceTest extends TestCase
{
    use RefreshDatabase;

    private PatientService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientService();
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Build a valid patient data array, with an optional NIK override.
     */
    private function validData(string $nik = '1234567890123456'): array
    {
        return [
            'full_name'     => 'Budi Santoso',
            'date_of_birth' => '1990-01-01',
            'address'       => 'Jl. Mawar No. 1, Jakarta',
            'nik'           => $nik,
        ];
    }

    // -------------------------------------------------------------------------
    // MR Number Format
    // -------------------------------------------------------------------------

    /**
     * The first generated MR number must match the pattern RM-\d{6}.
     * Validates: Requirements 3.4, 8.1
     */
    public function test_first_patient_mr_number_matches_rm_format(): void
    {
        $patient = $this->service->createPatient($this->validData());

        $this->assertMatchesRegularExpression(
            '/^RM-\d{6}$/',
            $patient->medical_record_number,
            "MR number '{$patient->medical_record_number}' does not match RM-\\d{6}"
        );
    }

    /**
     * The very first patient should receive RM-000001.
     * Validates: Requirements 3.4, 8.1
     */
    public function test_first_patient_receives_rm_000001(): void
    {
        $patient = $this->service->createPatient($this->validData());

        $this->assertSame('RM-000001', $patient->medical_record_number);
    }

    // -------------------------------------------------------------------------
    // Sequential MR Number Uniqueness
    // -------------------------------------------------------------------------

    /**
     * Sequential MR numbers generated for multiple patients must all be unique.
     * Validates: Requirements 3.4, 8.1
     */
    public function test_sequential_mr_numbers_are_unique(): void
    {
        $mrNumbers = [];

        for ($i = 1; $i <= 5; $i++) {
            $nik = str_pad((string) $i, 16, '0', STR_PAD_LEFT);
            $patient = $this->service->createPatient($this->validData($nik));
            $mrNumbers[] = $patient->medical_record_number;
        }

        // All MR numbers must be distinct
        $this->assertSame(
            count($mrNumbers),
            count(array_unique($mrNumbers)),
            'Duplicate MR numbers were generated: ' . implode(', ', $mrNumbers)
        );
    }

    /**
     * Each subsequent MR number must differ from the previous one.
     * Validates: Requirements 3.4, 8.1
     */
    public function test_subsequent_mr_numbers_are_incrementing(): void
    {
        $created = [];

        for ($i = 1; $i <= 3; $i++) {
            $nik = str_pad((string) $i, 16, '0', STR_PAD_LEFT);
            $created[] = $this->service->createPatient($this->validData($nik))->medical_record_number;
        }

        $this->assertSame('RM-000001', $created[0]);
        $this->assertSame('RM-000002', $created[1]);
        $this->assertSame('RM-000003', $created[2]);
    }

    // -------------------------------------------------------------------------
    // Duplicate NIK Rejection
    // -------------------------------------------------------------------------

    /**
     * Submitting a NIK that is already in the database must throw DuplicateNikException.
     * Validates: Requirements 3.3, 8.2
     */
    public function test_duplicate_nik_throws_exception(): void
    {
        $nik = '9999999999999999';
        $this->service->createPatient($this->validData($nik));

        $this->expectException(DuplicateNikException::class);
        $this->service->createPatient([
            'full_name'     => 'Other Person',
            'date_of_birth' => '1985-06-15',
            'address'       => 'Jl. Lain No. 2, Bandung',
            'nik'           => $nik,  // same NIK
        ]);
    }

    /**
     * When DuplicateNikException is thrown, no new patient record is created.
     * Validates: Requirements 3.3, 8.2
     */
    public function test_duplicate_nik_does_not_create_new_record(): void
    {
        $nik = '8888888888888888';
        $this->service->createPatient($this->validData($nik));

        $countBefore = Patient::count();

        try {
            $this->service->createPatient([
                'full_name'     => 'Duplicate Person',
                'date_of_birth' => '1995-03-10',
                'address'       => 'Jl. Duplikat No. 5',
                'nik'           => $nik,
            ]);
        } catch (DuplicateNikException) {
            // expected
        }

        $this->assertSame($countBefore, Patient::count(), 'A new record was created despite duplicate NIK.');
    }

    // =========================================================================
    // Property 5: Duplicate NIK Is Always Rejected
    //
    // For any NIK value that is already stored in the patients table, any
    // attempt to register a new patient with that same NIK shall be rejected,
    // and no new patient record shall be created.
    //
    // Validates: Requirements 3.3, 8.2
    // =========================================================================

    /**
     * Property 5 data provider: varied NIK values to test across multiple inputs.
     *
     * Each entry is [firstNik, secondNik] where firstNik == secondNik (duplicate).
     */
    public static function duplicateNikProvider(): array
    {
        return [
            'all zeros'                  => ['0000000000000000', '0000000000000000'],
            'all nines'                  => ['9999999999999999', '9999999999999999'],
            'sequential digits'          => ['1234567890123456', '1234567890123456'],
            'alternating digits'         => ['1010101010101010', '1010101010101010'],
            'leading zeros'              => ['0001234567890000', '0001234567890000'],
            'high-value number'          => ['9876543210987654', '9876543210987654'],
        ];
    }

    /**
     * Property 5: Duplicate NIK Is Always Rejected.
     *
     * For any NIK already in the system, re-registering with the same NIK
     * must always throw DuplicateNikException and must not persist a new record.
     *
     * Validates: Requirements 3.3, 8.2
     */
    #[DataProvider('duplicateNikProvider')]
    public function test_property5_duplicate_nik_is_always_rejected(
        string $firstNik,
        string $duplicateNik
    ): void {
        // Register first patient
        $this->service->createPatient($this->validData($firstNik));

        $countBefore = Patient::count();
        $exceptionThrown = false;

        try {
            $this->service->createPatient([
                'full_name'     => 'Second Person',
                'date_of_birth' => '2000-01-01',
                'address'       => 'Somewhere',
                'nik'           => $duplicateNik,
            ]);
        } catch (DuplicateNikException) {
            $exceptionThrown = true;
        }

        // Assert the exception was thrown
        $this->assertTrue(
            $exceptionThrown,
            "DuplicateNikException was NOT thrown for NIK '{$duplicateNik}'"
        );

        // Assert no new record was created
        $this->assertSame(
            $countBefore,
            Patient::count(),
            "A new patient record was created despite duplicate NIK '{$duplicateNik}'"
        );
    }

    // =========================================================================
    // Property 6: Generated MR Numbers Are Unique and Correctly Formatted
    //
    // For any sequence of new patient registrations, each generated
    // Medical_Record_Number shall:
    //   (a) match the format RM-NNNNNN (prefix "RM-" + exactly 6 zero-padded digits)
    //   (b) be distinct from all previously issued Medical_Record_Numbers
    //
    // Validates: Requirements 3.4, 8.1
    // =========================================================================

    /**
     * Property 6 data provider: batch sizes to simulate varying registration volumes.
     */
    public static function batchSizeProvider(): array
    {
        return [
            '1 patient'   => [1],
            '3 patients'  => [3],
            '5 patients'  => [5],
            '10 patients' => [10],
            '20 patients' => [20],
        ];
    }

    /**
     * Property 6: Generated MR Numbers Are Unique and Correctly Formatted.
     *
     * For any batch of N patients created sequentially, every MR number must
     * match RM-\d{6} and every MR number must be unique within the batch.
     *
     * Validates: Requirements 3.4, 8.1
     */
    #[DataProvider('batchSizeProvider')]
    public function test_property6_mr_numbers_are_unique_and_correctly_formatted(int $batchSize): void
    {
        $mrNumbers = [];

        for ($i = 1; $i <= $batchSize; $i++) {
            // Construct a distinct 16-digit NIK for each patient
            $nik = str_pad((string) $i, 16, '0', STR_PAD_LEFT);

            $patient = $this->service->createPatient([
                'full_name'     => "Patient $i",
                'date_of_birth' => '1990-01-01',
                'address'       => "Address $i",
                'nik'           => $nik,
            ]);

            $mr = $patient->medical_record_number;

            // (a) Must match RM-NNNNNN format
            $this->assertMatchesRegularExpression(
                '/^RM-\d{6}$/',
                $mr,
                "MR number '{$mr}' for patient {$i} does not match RM-\\d{6}"
            );

            // (b) Must not duplicate any previously issued MR number
            $this->assertNotContains(
                $mr,
                $mrNumbers,
                "MR number '{$mr}' was issued more than once"
            );

            $mrNumbers[] = $mr;
        }

        // Sanity: total unique count must equal batch size
        $this->assertCount($batchSize, array_unique($mrNumbers));
    }

    /**
     * Property 6 extension: MR numbers assigned to different patients stored
     * in the database are all distinct (DB-level uniqueness check).
     *
     * Validates: Requirements 8.1
     */
    public function test_property6_mr_numbers_unique_in_database(): void
    {
        $n = 8;

        for ($i = 1; $i <= $n; $i++) {
            $nik = str_pad((string) $i, 16, '0', STR_PAD_LEFT);
            $this->service->createPatient($this->validData($nik));
        }

        $allMrNumbers = Patient::pluck('medical_record_number')->toArray();

        $this->assertCount(
            $n,
            array_unique($allMrNumbers),
            'Duplicate MR numbers found in the database: ' . implode(', ', $allMrNumbers)
        );
    }
}
