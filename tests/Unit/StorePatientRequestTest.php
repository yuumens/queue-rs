<?php

namespace Tests\Unit;

use App\Http\Requests\StorePatientRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for StorePatientRequest validation rules.
 *
 * Covers:
 *   - Required fields: full_name, date_of_birth, address, nik  – Requirement 3.2
 *   - Future date_of_birth is rejected                          – Requirement 8.5
 *   - NIK with fewer/more than 16 digits is rejected            – Requirement 8.6
 *   - NIK with non-numeric characters is rejected               – Requirement 8.6
 *   - Property 4: New Patient Registration Rejects Incomplete Forms     – Validates: Requirements 3.2
 *   - Property 18: Input Validation Rejects Invalid Dates and NIK Formats – Validates: Requirements 8.5, 8.6
 */
class StorePatientRequestTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return a fully valid set of patient input data.
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'full_name'     => 'Budi Santoso',
            'date_of_birth' => '1990-05-15',
            'address'       => 'Jl. Mawar No. 1, Jakarta',
            'nik'           => '1234567890123456',
        ], $overrides);
    }

    /**
     * Run the StorePatientRequest rules via Laravel's Validator facade.
     *
     * Returns true when validation passes, false when it fails.
     */
    private function validate(array $data): bool
    {
        $rules    = (new StorePatientRequest())->rules();
        $messages = (new StorePatientRequest())->messages();

        return Validator::make($data, $rules, $messages)->passes();
    }

    /**
     * Retrieve the validation error messages for the given data.
     *
     * @return array<string, array<string>>
     */
    private function errors(array $data): array
    {
        $rules    = (new StorePatientRequest())->rules();
        $messages = (new StorePatientRequest())->messages();

        return Validator::make($data, $rules, $messages)->errors()->toArray();
    }

    // -------------------------------------------------------------------------
    // Valid data passes
    // -------------------------------------------------------------------------

    /**
     * A complete, valid submission should pass all rules.
     * Validates: Requirements 3.2
     */
    public function test_valid_data_passes_validation(): void
    {
        $this->assertTrue($this->validate($this->validData()));
    }

    // -------------------------------------------------------------------------
    // Required fields
    // -------------------------------------------------------------------------

    /**
     * Each required field, when absent, must produce a validation failure.
     * Validates: Requirements 3.2
     */
    #[DataProvider('requiredFieldProvider')]
    public function test_missing_required_field_fails_validation(string $field): void
    {
        $data = $this->validData();
        unset($data[$field]);

        $this->assertFalse(
            $this->validate($data),
            "Validation should fail when '{$field}' is missing"
        );

        $errors = $this->errors($data);
        $this->assertArrayHasKey(
            $field,
            $errors,
            "Expected error for field '{$field}' but got: " . implode(', ', array_keys($errors))
        );
    }

    public static function requiredFieldProvider(): array
    {
        return [
            'missing full_name'     => ['full_name'],
            'missing date_of_birth' => ['date_of_birth'],
            'missing address'       => ['address'],
            'missing nik'           => ['nik'],
        ];
    }

    /**
     * Each required field, when empty string, must produce a validation failure.
     * Validates: Requirements 3.2
     */
    #[DataProvider('requiredFieldProvider')]
    public function test_empty_required_field_fails_validation(string $field): void
    {
        $data          = $this->validData();
        $data[$field]  = '';

        $this->assertFalse(
            $this->validate($data),
            "Validation should fail when '{$field}' is empty"
        );
    }

    // -------------------------------------------------------------------------
    // date_of_birth: future dates must be rejected
    // -------------------------------------------------------------------------

    /**
     * A date_of_birth in the future must be rejected.
     * Validates: Requirements 8.5
     */
    public function test_future_date_of_birth_is_rejected(): void
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $data   = $this->validData(['date_of_birth' => $tomorrow]);
        $errors = $this->errors($data);

        $this->assertFalse(
            $this->validate($data),
            "A future date_of_birth ({$tomorrow}) should fail validation"
        );

        $this->assertArrayHasKey('date_of_birth', $errors);
    }

    /**
     * Today's date must be accepted (boundary: before_or_equal:today).
     * Validates: Requirements 8.5
     */
    public function test_today_date_of_birth_is_accepted(): void
    {
        $today = now()->format('Y-m-d');

        $this->assertTrue(
            $this->validate($this->validData(['date_of_birth' => $today])),
            "Today ({$today}) should be a valid date_of_birth"
        );
    }

    /**
     * A clearly past date_of_birth must be accepted.
     * Validates: Requirements 8.5
     */
    public function test_past_date_of_birth_is_accepted(): void
    {
        $this->assertTrue(
            $this->validate($this->validData(['date_of_birth' => '1970-01-01'])),
            "A past date of birth should pass validation"
        );
    }

    /**
     * An invalid (non-date) string for date_of_birth must be rejected.
     * Validates: Requirements 8.5
     */
    public function test_non_date_string_for_date_of_birth_is_rejected(): void
    {
        $this->assertFalse(
            $this->validate($this->validData(['date_of_birth' => 'not-a-date'])),
            "A non-date string should fail date_of_birth validation"
        );
    }

    // -------------------------------------------------------------------------
    // NIK: length validation
    // -------------------------------------------------------------------------

    /**
     * NIK with exactly 16 digits must pass.
     * Validates: Requirements 8.6
     */
    public function test_nik_with_exactly_16_digits_passes(): void
    {
        $this->assertTrue(
            $this->validate($this->validData(['nik' => '1234567890123456'])),
            "A 16-digit NIK should pass validation"
        );
    }

    /**
     * NIK shorter than 16 digits must be rejected.
     * Validates: Requirements 8.6
     */
    #[DataProvider('shortNikProvider')]
    public function test_nik_shorter_than_16_digits_is_rejected(string $nik): void
    {
        $data = $this->validData(['nik' => $nik]);

        $this->assertFalse(
            $this->validate($data),
            "NIK '{$nik}' (" . strlen($nik) . " digits) should fail — fewer than 16 digits"
        );

        $errors = $this->errors($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public static function shortNikProvider(): array
    {
        return [
            '1 digit'   => ['1'],
            '10 digits' => ['1234567890'],
            '15 digits' => ['123456789012345'],
        ];
    }

    /**
     * NIK longer than 16 digits must be rejected.
     * Validates: Requirements 8.6
     */
    #[DataProvider('longNikProvider')]
    public function test_nik_longer_than_16_digits_is_rejected(string $nik): void
    {
        $data = $this->validData(['nik' => $nik]);

        $this->assertFalse(
            $this->validate($data),
            "NIK '{$nik}' (" . strlen($nik) . " digits) should fail — more than 16 digits"
        );

        $errors = $this->errors($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public static function longNikProvider(): array
    {
        return [
            '17 digits' => ['12345678901234567'],
            '20 digits' => ['12345678901234567890'],
            '32 digits' => ['12345678901234567890123456789012'],
        ];
    }

    // -------------------------------------------------------------------------
    // NIK: non-numeric characters must be rejected
    // -------------------------------------------------------------------------

    /**
     * NIK containing letters must be rejected.
     * Validates: Requirements 8.6
     */
    #[DataProvider('nonNumericNikProvider')]
    public function test_nik_with_non_numeric_characters_is_rejected(string $nik): void
    {
        $data = $this->validData(['nik' => $nik]);

        $this->assertFalse(
            $this->validate($data),
            "NIK '{$nik}' containing non-numeric characters should fail validation"
        );

        $errors = $this->errors($data);
        $this->assertArrayHasKey('nik', $errors);
    }

    public static function nonNumericNikProvider(): array
    {
        return [
            'letters only (16 chars)'           => ['ABCDEFGHIJKLMNOP'],
            'alphanumeric (16 chars)'            => ['1234567890ABCDEF'],
            'with hyphen (looks like formatted)' => ['1234-5678-9012-34'],
            'with spaces (16 visible digits)'    => ['1234 5678 9012 34'],
            'with dot'                           => ['123456789012345.'],
            'with special chars'                 => ['12345678901234!@'],
        ];
    }

    // -------------------------------------------------------------------------
    // Property 4: New Patient Registration Rejects Incomplete Forms
    //
    // For any submission missing one or more required fields, the system must
    // reject the submission and not create a patient record.
    //
    // Validates: Requirements 3.2
    // -------------------------------------------------------------------------

    /**
     * Property 4 data provider: all possible subsets of required fields removed.
     *
     * Each entry removes at least one of the four required fields.
     */
    public static function incompleteFormProvider(): array
    {
        $requiredFields = ['full_name', 'date_of_birth', 'address', 'nik'];

        // Generate every non-empty subset of fields to omit (1, 2, 3, or all 4 missing)
        $cases = [];
        for ($mask = 1; $mask < (1 << count($requiredFields)); $mask++) {
            $missing = [];
            foreach ($requiredFields as $i => $field) {
                if ($mask & (1 << $i)) {
                    $missing[] = $field;
                }
            }
            $label          = 'missing ' . implode(' + ', $missing);
            $cases[$label]  = [$missing];
        }

        return $cases;
    }

    /**
     * Property 4: New Patient Registration Rejects Incomplete Forms.
     *
     * For any combination of missing required fields the form must be rejected.
     *
     * Validates: Requirements 3.2
     */
    #[DataProvider('incompleteFormProvider')]
    public function test_property4_incomplete_forms_are_always_rejected(array $missingFields): void
    {
        $data = $this->validData();
        foreach ($missingFields as $field) {
            unset($data[$field]);
        }

        $this->assertFalse(
            $this->validate($data),
            'Validation should fail when these fields are missing: ' . implode(', ', $missingFields)
        );

        // Each missing field must appear in the error bag
        $errors = $this->errors($data);
        foreach ($missingFields as $field) {
            $this->assertArrayHasKey(
                $field,
                $errors,
                "Expected a validation error for missing field '{$field}'"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Property 18: Input Validation Rejects Invalid Dates and NIK Formats
    //
    // For any submission where (a) date_of_birth is a future date, or
    // (b) nik is not exactly 16 numeric digits, the system must reject the
    // submission with a validation error.
    //
    // Validates: Requirements 8.5, 8.6
    // -------------------------------------------------------------------------

    /**
     * Property 18 data provider: varied future dates (scenario a).
     */
    public static function futureDateProvider(): array
    {
        return [
            'tomorrow'          => [now()->addDay()->format('Y-m-d')],
            'one month from now' => [now()->addMonth()->format('Y-m-d')],
            'one year from now'  => [now()->addYear()->format('Y-m-d')],
            '10 years from now'  => [now()->addYears(10)->format('Y-m-d')],
            'year 2099'          => ['2099-12-31'],
        ];
    }

    /**
     * Property 18: Any future date_of_birth must be rejected.
     *
     * Validates: Requirements 8.5
     */
    #[DataProvider('futureDateProvider')]
    public function test_property18_future_date_of_birth_is_always_rejected(string $futureDate): void
    {
        $data = $this->validData(['date_of_birth' => $futureDate]);

        $this->assertFalse(
            $this->validate($data),
            "date_of_birth '{$futureDate}' is in the future and should fail validation"
        );

        $errors = $this->errors($data);
        $this->assertArrayHasKey(
            'date_of_birth',
            $errors,
            "Expected date_of_birth error for future date '{$futureDate}'"
        );
    }

    /**
     * Property 18 data provider: invalid NIK values (scenario b).
     *
     * Includes: wrong length, non-numeric characters, empty string.
     */
    public static function invalidNikProvider(): array
    {
        return [
            // Too short
            '1 digit'             => ['1'],
            '8 digits'            => ['12345678'],
            '15 digits'           => ['123456789012345'],
            // Too long
            '17 digits'           => ['12345678901234567'],
            '20 digits'           => ['12345678901234567890'],
            // Non-numeric
            'letters 16 chars'    => ['ABCDEFGHIJKLMNOP'],
            'alphanumeric'        => ['1234567890ABCDEF'],
            'with hyphens'        => ['1234-5678-9012-34'],
            'with spaces'         => ['1234 5678 9012 34'],
            // Empty
            'empty string'        => [''],
        ];
    }

    /**
     * Property 18: Any NIK that is not exactly 16 numeric digits must be rejected.
     *
     * Validates: Requirements 8.6
     */
    #[DataProvider('invalidNikProvider')]
    public function test_property18_invalid_nik_is_always_rejected(string $invalidNik): void
    {
        $data = $this->validData(['nik' => $invalidNik]);

        $this->assertFalse(
            $this->validate($data),
            "NIK '{$invalidNik}' should fail validation (not exactly 16 numeric digits)"
        );

        $errors = $this->errors($data);
        $this->assertArrayHasKey(
            'nik',
            $errors,
            "Expected NIK validation error for value '{$invalidNik}'"
        );
    }

    // -------------------------------------------------------------------------
    // Custom Indonesian error messages
    // -------------------------------------------------------------------------

    /**
     * The custom Indonesian message for a missing NIK must be present.
     * Validates: Requirements 8.5, 8.6
     */
    public function test_missing_nik_returns_indonesian_error_message(): void
    {
        $data = $this->validData();
        unset($data['nik']);

        $errors = $this->errors($data);

        $this->assertNotEmpty($errors['nik']);
        $this->assertStringContainsString(
            'wajib',
            strtolower($errors['nik'][0]),
            "Error message for missing NIK should be in Indonesian and contain 'wajib'"
        );
    }

    /**
     * The custom Indonesian message for an invalid NIK length must be present.
     * Validates: Requirements 8.6
     */
    public function test_wrong_nik_length_returns_indonesian_error_message(): void
    {
        $data   = $this->validData(['nik' => '12345']);
        $errors = $this->errors($data);

        $this->assertNotEmpty($errors['nik']);
        $this->assertStringContainsString(
            '16',
            $errors['nik'][0],
            "Error message for wrong NIK length should mention '16'"
        );
    }

    /**
     * The custom Indonesian message for a future date_of_birth must be present.
     * Validates: Requirements 8.5
     */
    public function test_future_date_of_birth_returns_indonesian_error_message(): void
    {
        $future = now()->addYear()->format('Y-m-d');
        $data   = $this->validData(['date_of_birth' => $future]);
        $errors = $this->errors($data);

        $this->assertNotEmpty($errors['date_of_birth']);
        // The custom message mentions "hari ini" (today)
        $this->assertStringContainsString(
            'hari ini',
            strtolower($errors['date_of_birth'][0]),
            "Error message for future DOB should be in Indonesian and mention 'hari ini'"
        );
    }
}
