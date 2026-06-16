<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Comprehensive input validation property tests for patient registration.
 *
 * Property 18: Input Validation Rejects Invalid Dates and NIK Formats
 *
 * For any new patient registration form submission where:
 *   (a) date_of_birth is a future date, or
 *   (b) nik is not exactly 16 numeric digits,
 * the system shall reject the submission with a validation error and not
 * persist any patient record.
 *
 * **Validates: Requirements 8.5, 8.6**
 */
class InputValidationPropertyTest extends TestCase
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
            'full_name'     => 'Budi Santoso',
            'date_of_birth' => '1990-05-15',
            'address'       => 'Jl. Mawar No. 1, Jakarta',
            'nik'           => '1234567890123456',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Property 18 (a): Any NIK not matching /^\d{16}$/ is rejected
    //
    // For any NIK string that does not consist of exactly 16 numeric digits,
    // registration is rejected with a 302 redirect, session errors on 'nik',
    // and no patient record created in the database.
    //
    // Validates: Requirements 8.6
    // -------------------------------------------------------------------------

    /**
     * Data provider: wide variety of invalid NIK formats.
     */
    public static function invalidNikProvider(): array
    {
        return [
            // Too short
            'empty string'                     => [''],
            'single digit'                     => ['1'],
            '5 digits'                         => ['12345'],
            '10 digits'                        => ['1234567890'],
            '15 digits'                        => ['123456789012345'],

            // Too long
            '17 digits'                        => ['12345678901234567'],
            '20 digits'                        => ['12345678901234567890'],
            '32 digits'                        => ['12345678901234567890123456789012'],

            // Non-numeric characters (16 chars total)
            'all letters'                      => ['ABCDEFGHIJKLMNOP'],
            'mixed alphanumeric'               => ['1234567890ABCDEF'],
            'lowercase letters mixed'          => ['1234567890abcdef'],
            'with hyphens'                     => ['1234-5678-9012-3'],
            'with spaces (16 visible digits)'  => ['1234 5678 9012 3'],
            'with dots'                        => ['1234.5678.9012.3'],
            'with underscores'                 => ['1234_5678_9012_3'],
            'with special characters'          => ['12345678901234!@'],
            'with plus sign'                   => ['+234567890123456'],
            'with leading zero and letter'     => ['0x34567890123456'],

            // Edge cases
            'only spaces (16 chars)'           => ['                '],
            'tabs and digits'                  => ["12345678\t0123456"],
            'newline in string'                => ["123456789012345\n"],
            'unicode digits (Arabic numerals)' => ['١٢٣٤٥٦٧٨٩٠١٢٣٤٥٦'],
            'null character padding'           => ["12345678901234\x005"],
        ];
    }

    /**
     * Property 18: Any NIK not matching /^\d{16}$/ → registration rejected via HTTP.
     *
     * **Validates: Requirements 8.6**
     */
    #[DataProvider('invalidNikProvider')]
    public function test_property18_invalid_nik_rejects_registration(string $invalidNik): void
    {
        $payload = $this->validPayload(['nik' => $invalidNik]);

        $response = $this->from(route('patient.register'))
            ->post(route('patient.store'), $payload);

        // Assert redirect back (302)
        $response->assertRedirect(route('patient.register'));

        // Assert session has validation error on 'nik'
        $response->assertSessionHasErrors('nik');

        // Assert no patient record was created
        $this->assertDatabaseCount('patients', 0);
    }

    // -------------------------------------------------------------------------
    // Property 18 (b): Any future date for date_of_birth is rejected
    //
    // For any date string representing a future date, DOB validation rejects it
    // with a 302 redirect, session errors on 'date_of_birth', and no patient
    // record created in the database.
    //
    // Validates: Requirements 8.5
    // -------------------------------------------------------------------------

    /**
     * Data provider: a variety of future dates.
     */
    public static function futureDateProvider(): array
    {
        return [
            'tomorrow'                => [now()->addDay()->format('Y-m-d')],
            'day after tomorrow'      => [now()->addDays(2)->format('Y-m-d')],
            'one week from now'       => [now()->addWeek()->format('Y-m-d')],
            'one month from now'      => [now()->addMonth()->format('Y-m-d')],
            'three months from now'   => [now()->addMonths(3)->format('Y-m-d')],
            'one year from now'       => [now()->addYear()->format('Y-m-d')],
            'five years from now'     => [now()->addYears(5)->format('Y-m-d')],
            'ten years from now'      => [now()->addYears(10)->format('Y-m-d')],
            'distant future (2099)'   => ['2099-12-31'],
            'distant future (2150)'   => ['2150-06-15'],
        ];
    }

    /**
     * Property 18: Any future date_of_birth → registration rejected via HTTP.
     *
     * **Validates: Requirements 8.5**
     */
    #[DataProvider('futureDateProvider')]
    public function test_property18_future_dob_rejects_registration(string $futureDate): void
    {
        $payload = $this->validPayload(['date_of_birth' => $futureDate]);

        $response = $this->from(route('patient.register'))
            ->post(route('patient.store'), $payload);

        // Assert redirect back (302)
        $response->assertRedirect(route('patient.register'));

        // Assert session has validation error on 'date_of_birth'
        $response->assertSessionHasErrors('date_of_birth');

        // Assert no patient record was created
        $this->assertDatabaseCount('patients', 0);
    }

    // -------------------------------------------------------------------------
    // Sanity check: valid data passes (ensures test setup is correct)
    // -------------------------------------------------------------------------

    /**
     * Confirm that a valid payload is accepted, proving our test setup works.
     */
    public function test_valid_data_is_accepted_as_baseline(): void
    {
        $payload = $this->validPayload();

        $response = $this->post(route('patient.store'), $payload);

        $response->assertRedirect(route('queue.select'));
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseHas('patients', [
            'nik'       => $payload['nik'],
            'full_name' => $payload['full_name'],
        ]);
    }
}
