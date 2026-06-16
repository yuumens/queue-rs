<?php

namespace Tests\Feature;

use App\Livewire\PatientSearch;
use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Livewire component tests for PatientSearch.
 *
 * Covers:
 *   - MR number search returns the correct patient            – Requirement 2.2
 *   - Name search with partial string returns matching patients – Requirement 2.3
 *   - Name search is case-insensitive                          – Requirement 2.3
 *   - Unknown query returns empty results                      – Requirement 2.6
 *   - Property 1: MR Number Search Returns Exactly One Match
 *   - Property 2: Name Search Returns All Case-Insensitive Substring Matches
 *   - Property 3: Search Result Records Contain Required Fields
 *
 * Validates: Requirements 2.2, 2.3, 2.4, 2.6
 */
class PatientSearchTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a patient with given attributes merged with defaults.
     */
    private function createPatient(array $attributes = []): Patient
    {
        static $counter = 0;
        $counter++;

        return Patient::create(array_merge([
            'medical_record_number' => 'RM-' . str_pad((string) $counter, 6, '0', STR_PAD_LEFT),
            'nik'                   => str_pad((string) $counter, 16, '0', STR_PAD_LEFT),
            'full_name'             => "Patient {$counter}",
            'date_of_birth'         => '1990-01-01',
            'address'               => "Address {$counter}",
        ], $attributes));
    }

    // -------------------------------------------------------------------------
    // MR Number Search Tests
    // Validates: Requirement 2.2
    // -------------------------------------------------------------------------

    public function test_mr_number_search_returns_matching_patient(): void
    {
        $patient = $this->createPatient([
            'medical_record_number' => 'RM-000042',
            'full_name'             => 'Siti Rahayu',
        ]);

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', 'RM-000042')
            ->call('search')
            ->assertSet('results', function (array $results) use ($patient) {
                return count($results) === 1
                    && $results[0]['id'] === $patient->id;
            });
    }

    public function test_mr_number_search_with_unknown_number_returns_empty(): void
    {
        $this->createPatient(['medical_record_number' => 'RM-000001']);

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', 'RM-999999')
            ->call('search')
            ->assertSet('results', []);
    }

    // -------------------------------------------------------------------------
    // Name Search Tests
    // Validates: Requirement 2.3
    // -------------------------------------------------------------------------

    public function test_name_search_with_partial_string_returns_all_matching(): void
    {
        $p1 = $this->createPatient(['full_name' => 'Ahmad Suryadi']);
        $p2 = $this->createPatient(['full_name' => 'Siti Suryana']);
        $this->createPatient(['full_name' => 'Budi Santoso']);

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'name')
            ->set('query', 'Surya')
            ->call('search')
            ->assertSet('results', function (array $results) use ($p1, $p2) {
                $ids = array_column($results, 'id');
                return count($results) === 2
                    && in_array($p1->id, $ids)
                    && in_array($p2->id, $ids);
            });
    }

    public function test_name_search_is_case_insensitive(): void
    {
        $patient = $this->createPatient(['full_name' => 'Muhammad Rizki']);

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'name')
            ->set('query', 'muhammad rizki')
            ->call('search')
            ->assertSet('results', function (array $results) use ($patient) {
                return count($results) === 1
                    && $results[0]['id'] === $patient->id;
            });
    }

    public function test_name_search_with_uppercase_query_finds_lowercase_name(): void
    {
        $patient = $this->createPatient(['full_name' => 'dewi lestari']);

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'name')
            ->set('query', 'DEWI')
            ->call('search')
            ->assertSet('results', function (array $results) use ($patient) {
                return count($results) === 1
                    && $results[0]['id'] === $patient->id;
            });
    }

    // -------------------------------------------------------------------------
    // Unknown Query Tests
    // Validates: Requirement 2.6
    // -------------------------------------------------------------------------

    public function test_name_search_with_unknown_name_returns_empty(): void
    {
        $this->createPatient(['full_name' => 'Existing Patient']);

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'name')
            ->set('query', 'Nonexistent Person')
            ->call('search')
            ->assertSet('results', []);
    }

    public function test_empty_query_returns_empty_results(): void
    {
        $this->createPatient();

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', '')
            ->call('search')
            ->assertSet('results', []);
    }

    // =========================================================================
    // Property 1: MR Number Search Returns Exactly One Match
    //
    // For any Medical_Record_Number that exists in the patient database, searching
    // by that exact MR number shall return exactly one patient record — that
    // specific patient — and no others. For any MR number not present in the
    // database, the search shall return an empty result set.
    //
    // Validates: Requirements 2.2
    // =========================================================================

    #[DataProvider('mrNumberSearchProvider')]
    public function test_property1_mr_number_search_returns_exactly_one_match(
        string $targetMr,
        array $otherMrs,
        bool $shouldFind
    ): void {
        // Create the target patient
        $target = $this->createPatient(['medical_record_number' => $targetMr]);

        // Create other patients that should NOT appear in results
        foreach ($otherMrs as $mr) {
            $this->createPatient(['medical_record_number' => $mr]);
        }

        $component = Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', $targetMr)
            ->call('search');

        if ($shouldFind) {
            $component->assertSet('results', function (array $results) use ($target) {
                return count($results) === 1
                    && $results[0]['id'] === $target->id
                    && $results[0]['medical_record_number'] === $target->medical_record_number;
            });
        }
    }

    /**
     * **Validates: Requirements 2.2**
     */
    public static function mrNumberSearchProvider(): array
    {
        return [
            'single patient in DB' => [
                'RM-000010', [], true,
            ],
            'multiple patients, find specific one' => [
                'RM-000020', ['RM-000021', 'RM-000022', 'RM-000023'], true,
            ],
            'target among many similar MR numbers' => [
                'RM-000100', ['RM-000101', 'RM-000099', 'RM-001000'], true,
            ],
            'sequential MR numbers' => [
                'RM-000050', ['RM-000049', 'RM-000051'], true,
            ],
        ];
    }

    /**
     * Property 1 (negative case): non-existent MR numbers return empty.
     *
     * **Validates: Requirements 2.2**
     */
    #[DataProvider('mrNumberNotFoundProvider')]
    public function test_property1_nonexistent_mr_number_returns_empty(string $searchMr): void
    {
        // Create some patients that don't match
        $this->createPatient(['medical_record_number' => 'RM-000001']);
        $this->createPatient(['medical_record_number' => 'RM-000002']);

        Livewire::test(PatientSearch::class)
            ->set('searchMode', 'mr_number')
            ->set('query', $searchMr)
            ->call('search')
            ->assertSet('results', []);
    }

    public static function mrNumberNotFoundProvider(): array
    {
        return [
            'completely different number' => ['RM-999999'],
            'partial match'               => ['RM-0000'],
            'wrong prefix'                => ['XX-000001'],
            'empty-like value'            => ['RM-000000'],
        ];
    }

    // =========================================================================
    // Property 2: Name Search Returns All Case-Insensitive Substring Matches
    //
    // For any patient database state and any non-empty search string, the name
    // search shall return all and only those patients whose full_name contains
    // the search string as a case-insensitive substring. No matching patient
    // shall be omitted, and no non-matching patient shall be included.
    //
    // Validates: Requirements 2.3
    // =========================================================================

    #[DataProvider('nameSubstringSearchProvider')]
    public function test_property2_name_search_returns_all_case_insensitive_substring_matches(
        string $searchQuery,
        array $patientNames,
        array $expectedMatchIndices
    ): void {
        $patients = [];
        foreach ($patientNames as $name) {
            $patients[] = $this->createPatient(['full_name' => $name]);
        }

        $component = Livewire::test(PatientSearch::class)
            ->set('searchMode', 'name')
            ->set('query', $searchQuery)
            ->call('search');

        $component->assertSet('results', function (array $results) use ($patients, $expectedMatchIndices) {
            $resultIds = array_column($results, 'id');

            // Count must match expected
            if (count($results) !== count($expectedMatchIndices)) {
                return false;
            }

            // All expected patients must be in results
            foreach ($expectedMatchIndices as $index) {
                if (!in_array($patients[$index]->id, $resultIds)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * **Validates: Requirements 2.3**
     */
    public static function nameSubstringSearchProvider(): array
    {
        return [
            'exact full name match (case-insensitive)' => [
                'siti rahayu',
                ['Siti Rahayu', 'Budi Santoso', 'Ahmad Wijaya'],
                [0],
            ],
            'partial name substring matches multiple' => [
                'an',
                ['Andi Firmansyah', 'Budi Santoso', 'Dewi Anggraini', 'Rizki Pratama'],
                [0, 1, 2],  // Andi (An), Santoso (an), Anggraini (An)
            ],
            'mixed case query finds all matches' => [
                'DewI',
                ['Dewi Lestari', 'DEWI ANGGRAINI', 'dewi sari', 'Budi Pratama'],
                [0, 1, 2],
            ],
            'single character matches all containing it' => [
                'i',
                ['Siti', 'Budi', 'Ahmad'],
                [0, 1],
            ],
            'no matches returns empty' => [
                'xyz',
                ['Ahmad Suryadi', 'Budi Santoso', 'Dewi Lestari'],
                [],
            ],
            'full name with spaces' => [
                'Budi San',
                ['Budi Santoso', 'Budi Saputra', 'Ahmad Budi'],
                [0],
            ],
        ];
    }

    // =========================================================================
    // Property 3: Search Result Records Contain Required Fields
    //
    // For any set of patients returned by a search query (whether by MR number
    // or by name), each result record shall expose the patient's full name,
    // Medical_Record_Number, and date of birth.
    //
    // Validates: Requirements 2.4
    // =========================================================================

    #[DataProvider('requiredFieldsProvider')]
    public function test_property3_search_results_contain_required_fields(
        string $searchMode,
        string $fullName,
        string $mrNumber,
        string $dateOfBirth
    ): void {
        $this->createPatient([
            'full_name'             => $fullName,
            'medical_record_number' => $mrNumber,
            'date_of_birth'         => $dateOfBirth,
        ]);

        $query = $searchMode === 'mr_number' ? $mrNumber : $fullName;

        $component = Livewire::test(PatientSearch::class)
            ->set('searchMode', $searchMode)
            ->set('query', $query)
            ->call('search');

        $component->assertSet('results', function (array $results) use ($fullName, $mrNumber, $dateOfBirth) {
            if (empty($results)) {
                return false;
            }

            $record = $results[0];

            // Each record must have the required fields
            return isset($record['full_name'])
                && isset($record['medical_record_number'])
                && isset($record['date_of_birth'])
                && $record['full_name'] === $fullName
                && $record['medical_record_number'] === $mrNumber
                && $record['date_of_birth'] === $dateOfBirth;
        });
    }

    /**
     * **Validates: Requirements 2.4**
     */
    public static function requiredFieldsProvider(): array
    {
        return [
            'MR search - standard patient' => [
                'mr_number', 'Siti Rahayu', 'RM-000077', '1990-05-15',
            ],
            'MR search - patient with long name' => [
                'mr_number', 'Muhammad Rizki Adi Pratama', 'RM-000078', '2000-12-31',
            ],
            'name search - standard patient' => [
                'name', 'Budi Santoso', 'RM-000079', '1985-03-22',
            ],
            'name search - patient born today' => [
                'name', 'Bayi Baru', 'RM-000080', now()->format('Y-m-d'),
            ],
            'MR search - elderly patient' => [
                'mr_number', 'Haji Abdullah', 'RM-000081', '1940-01-01',
            ],
        ];
    }
}
