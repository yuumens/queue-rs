<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature tests for the practice schedule admin (PracticeScheduleController).
 *
 * Covers:
 *   - Create schedule → record exists in DB with correct fields
 *   - Attempt duplicate (doctor, polyclinic, day) → validation error, no new record
 *   - Property 16: Practice Schedule Enforces Unique (Doctor, Polyclinic, Day) Combination
 *   - Property 17: Practice Schedule Records Are Complete
 *   - Validates: Requirements 7.2, 7.3, 7.5
 */
class PracticeScheduleAdminTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    private function createDoctor(?Polyclinic $polyclinic = null): Doctor
    {
        $polyclinic ??= Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);

        return Doctor::create([
            'name'          => 'Dr. Ahmad',
            'polyclinic_id' => $polyclinic->id,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        $polyclinic = Polyclinic::first() ?? Polyclinic::create(['name' => 'Penyakit Dalam', 'code' => 'A']);
        $doctor = Doctor::first() ?? Doctor::create(['name' => 'Dr. Ahmad', 'polyclinic_id' => $polyclinic->id]);

        return array_merge([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 1, // Monday
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // Test: Create schedule → record exists in DB with correct fields
    // Validates: Requirements 7.2, 7.3
    // -------------------------------------------------------------------------

    public function test_create_schedule_stores_record_in_database(): void
    {
        $user = $this->authenticatedUser();
        $polyclinic = Polyclinic::create(['name' => 'Anak', 'code' => 'B']);
        $doctor = Doctor::create(['name' => 'Dr. Budi', 'polyclinic_id' => $polyclinic->id]);

        $payload = [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 3, // Wednesday
            'start_time'    => '09:00',
            'end_time'      => '15:00',
        ];

        $response = $this->actingAs($user)->post(route('admin.practice-schedules.store'), $payload);

        $response->assertRedirect(route('admin.practice-schedules.index'));

        $this->assertDatabaseHas('practice_schedules', [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 3,
        ]);

        $schedule = PracticeSchedule::where('doctor_id', $doctor->id)
            ->where('polyclinic_id', $polyclinic->id)
            ->where('day_of_week', 3)
            ->first();

        $this->assertNotNull($schedule);
        $this->assertStringContainsString('09:00', $schedule->start_time);
        $this->assertStringContainsString('15:00', $schedule->end_time);
    }

    public function test_create_schedule_returns_redirect_with_success_message(): void
    {
        $user = $this->authenticatedUser();
        $payload = $this->validPayload();

        $response = $this->actingAs($user)->post(route('admin.practice-schedules.store'), $payload);

        $response->assertRedirect(route('admin.practice-schedules.index'));
        $response->assertSessionHas('success');
    }

    // -------------------------------------------------------------------------
    // Test: Attempt duplicate (doctor, polyclinic, day) → validation error
    // Validates: Requirement 7.5
    // -------------------------------------------------------------------------

    public function test_duplicate_doctor_polyclinic_day_returns_validation_error(): void
    {
        $user = $this->authenticatedUser();
        $polyclinic = Polyclinic::create(['name' => 'Mata', 'code' => 'C']);
        $doctor = Doctor::create(['name' => 'Dr. Citra', 'polyclinic_id' => $polyclinic->id]);

        // Create an existing schedule
        PracticeSchedule::create([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 2, // Tuesday
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ]);

        // Attempt to create a duplicate (same doctor, polyclinic, day)
        $payload = [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 2,
            'start_time'    => '13:00',
            'end_time'      => '17:00',
        ];

        $response = $this->actingAs($user)
            ->from(route('admin.practice-schedules.create'))
            ->post(route('admin.practice-schedules.store'), $payload);

        $response->assertRedirect(route('admin.practice-schedules.create'));
        $response->assertSessionHasErrors('day_of_week');
    }

    public function test_duplicate_doctor_polyclinic_day_does_not_create_new_record(): void
    {
        $user = $this->authenticatedUser();
        $polyclinic = Polyclinic::create(['name' => 'THT', 'code' => 'D']);
        $doctor = Doctor::create(['name' => 'Dr. Dian', 'polyclinic_id' => $polyclinic->id]);

        PracticeSchedule::create([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 4, // Thursday
            'start_time'    => '08:00',
            'end_time'      => '11:00',
        ]);

        $payload = [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 4,
            'start_time'    => '14:00',
            'end_time'      => '17:00',
        ];

        $this->actingAs($user)
            ->from(route('admin.practice-schedules.create'))
            ->post(route('admin.practice-schedules.store'), $payload);

        $this->assertDatabaseCount('practice_schedules', 1);
    }

    // -------------------------------------------------------------------------
    // Property 16: Practice Schedule Enforces Unique (Doctor, Polyclinic, Day) Combination
    //
    // For any existing PracticeSchedule record with a given (doctor_id,
    // polyclinic_id, day_of_week) combination, any attempt to create a second
    // record with the same three values shall be rejected by the system.
    //
    // Validates: Requirements 7.5
    // -------------------------------------------------------------------------

    #[DataProvider('duplicateCombinationProvider')]
    public function test_property16_unique_doctor_polyclinic_day_enforced(int $dayOfWeek): void
    {
        $user = $this->authenticatedUser();
        $polyclinic = Polyclinic::create(['name' => 'Jantung', 'code' => 'E']);
        $doctor = Doctor::create(['name' => 'Dr. Eko', 'polyclinic_id' => $polyclinic->id]);

        // Create existing schedule for the given day
        PracticeSchedule::create([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => $dayOfWeek,
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ]);

        // Attempt duplicate
        $payload = [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => $dayOfWeek,
            'start_time'    => '13:00',
            'end_time'      => '16:00',
        ];

        $response = $this->actingAs($user)
            ->from(route('admin.practice-schedules.create'))
            ->post(route('admin.practice-schedules.store'), $payload);

        // Must be rejected
        $response->assertSessionHasErrors('day_of_week');

        // No second record created
        $this->assertDatabaseCount('practice_schedules', 1);
    }

    /**
     * **Validates: Requirements 7.5**
     */
    public static function duplicateCombinationProvider(): array
    {
        return [
            'Sunday (0)'    => [0],
            'Monday (1)'    => [1],
            'Tuesday (2)'   => [2],
            'Wednesday (3)' => [3],
            'Thursday (4)'  => [4],
            'Friday (5)'    => [5],
            'Saturday (6)'  => [6],
        ];
    }

    public function test_property16_same_doctor_different_polyclinic_same_day_is_allowed(): void
    {
        $user = $this->authenticatedUser();
        $polyclinicA = Polyclinic::create(['name' => 'Bedah', 'code' => 'F']);
        $polyclinicB = Polyclinic::create(['name' => 'Saraf', 'code' => 'G']);
        $doctor = Doctor::create(['name' => 'Dr. Fani', 'polyclinic_id' => $polyclinicA->id]);

        // Schedule at polyclinic A on Monday
        PracticeSchedule::create([
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinicA->id,
            'day_of_week'   => 1,
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ]);

        // Same doctor, different polyclinic, same day → should succeed
        $payload = [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinicB->id,
            'day_of_week'   => 1,
            'start_time'    => '13:00',
            'end_time'      => '17:00',
        ];

        $response = $this->actingAs($user)->post(route('admin.practice-schedules.store'), $payload);

        $response->assertRedirect(route('admin.practice-schedules.index'));
        $this->assertDatabaseCount('practice_schedules', 2);
    }

    public function test_property16_same_polyclinic_different_doctor_same_day_is_allowed(): void
    {
        $user = $this->authenticatedUser();
        $polyclinic = Polyclinic::create(['name' => 'Kulit', 'code' => 'H']);
        $doctorA = Doctor::create(['name' => 'Dr. Gina', 'polyclinic_id' => $polyclinic->id]);
        $doctorB = Doctor::create(['name' => 'Dr. Hadi', 'polyclinic_id' => $polyclinic->id]);

        // Doctor A on Tuesday
        PracticeSchedule::create([
            'doctor_id'     => $doctorA->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 2,
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ]);

        // Different doctor, same polyclinic, same day → should succeed
        $payload = [
            'doctor_id'     => $doctorB->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 2,
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ];

        $response = $this->actingAs($user)->post(route('admin.practice-schedules.store'), $payload);

        $response->assertRedirect(route('admin.practice-schedules.index'));
        $this->assertDatabaseCount('practice_schedules', 2);
    }

    // -------------------------------------------------------------------------
    // Property 17: Practice Schedule Records Are Complete
    //
    // For any successfully created PracticeSchedule record, the stored record
    // shall have non-null values for doctor_id, polyclinic_id, day_of_week,
    // start_time, and end_time.
    //
    // Validates: Requirements 7.2, 7.3
    // -------------------------------------------------------------------------

    #[DataProvider('completeScheduleProvider')]
    public function test_property17_created_schedule_has_all_required_fields(
        int $dayOfWeek,
        string $startTime,
        string $endTime
    ): void {
        $user = $this->authenticatedUser();
        $polyclinic = Polyclinic::create(['name' => 'Paru', 'code' => 'I']);
        $doctor = Doctor::create(['name' => 'Dr. Irwan', 'polyclinic_id' => $polyclinic->id]);

        $payload = [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => $dayOfWeek,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
        ];

        $this->actingAs($user)->post(route('admin.practice-schedules.store'), $payload);

        $schedule = PracticeSchedule::where('doctor_id', $doctor->id)
            ->where('polyclinic_id', $polyclinic->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        $this->assertNotNull($schedule, 'Schedule record should exist in DB');
        $this->assertNotNull($schedule->doctor_id);
        $this->assertNotNull($schedule->polyclinic_id);
        $this->assertNotNull($schedule->day_of_week);
        $this->assertNotNull($schedule->start_time);
        $this->assertNotNull($schedule->end_time);

        // Verify correctness of stored values
        $this->assertEquals($doctor->id, $schedule->doctor_id);
        $this->assertEquals($polyclinic->id, $schedule->polyclinic_id);
        $this->assertEquals($dayOfWeek, $schedule->day_of_week);
        $this->assertStringContainsString($startTime, $schedule->start_time);
        $this->assertStringContainsString($endTime, $schedule->end_time);
    }

    /**
     * **Validates: Requirements 7.2, 7.3**
     */
    public static function completeScheduleProvider(): array
    {
        return [
            'Monday morning'        => [1, '08:00', '12:00'],
            'Wednesday afternoon'   => [3, '13:00', '17:00'],
            'Friday evening'        => [5, '18:00', '21:00'],
            'Sunday early morning'  => [0, '06:00', '10:00'],
            'Saturday full day'     => [6, '07:00', '19:00'],
        ];
    }

    // -------------------------------------------------------------------------
    // Auth middleware: unauthenticated users cannot access admin routes
    // -------------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_store_schedule(): void
    {
        $polyclinic = Polyclinic::create(['name' => 'Umum', 'code' => 'U']);
        $doctor = Doctor::create(['name' => 'Dr. Test', 'polyclinic_id' => $polyclinic->id]);

        $payload = [
            'doctor_id'     => $doctor->id,
            'polyclinic_id' => $polyclinic->id,
            'day_of_week'   => 1,
            'start_time'    => '08:00',
            'end_time'      => '12:00',
        ];

        $response = $this->post(route('admin.practice-schedules.store'), $payload);

        // Auth middleware should prevent access (redirect, 401, 403, or 500 if login route missing)
        $this->assertNotEquals(200, $response->getStatusCode());
        $this->assertDatabaseCount('practice_schedules', 0);
    }
}
