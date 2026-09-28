<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for SemesterCreationController::getFilteredModules()
 *
 * Guards against the bug where the endpoint validated four filters
 * (course_id, location, intake_id, semester) then ignored them all
 * and returned every module in the database.
 */
class SemesterFilteredModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private int $courseId;
    private int $intakeId;
    private int $semesterId;
    private int $moduleId;
    private int $otherModuleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'padmin@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        // Course
        $this->courseId = DB::table('courses')->insertGetId([
            'course_name'         => 'BSc CS',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'entry_qualification' => 'A/L Pass',
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Intake
        $this->intakeId = DB::table('intakes')->insertGetId([
            'course_id'        => $this->courseId,
            'course_name'      => 'BSc CS',
            'batch'            => '2024-01',
            'batch_size'       => 50,
            'intake_mode'      => 'Physical',
            'intake_type'      => 'Fulltime',
            'registration_fee' => '1000',
            'franchise_payment'=> '0',
            'course_fee'       => '500000',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYears(3)->toDateString(),
            'location'         => 'Welisara',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Semester
        $this->semesterId = DB::table('semesters')->insertGetId([
            'name'       => 'Semester 1',
            'course_id'  => $this->courseId,
            'intake_id'  => $this->intakeId,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(6)->toDateString(),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Module that belongs to this semester
        $this->moduleId = DB::table('modules')->insertGetId([
            'module_name' => 'Algorithms',
            'module_code' => 'CS101',
            'module_type' => 'core',
            'credits'     => 3,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        DB::table('semester_module')->insert([
            'semester_id' => $this->semesterId,
            'module_id'   => $this->moduleId,
        ]);

        // A different module in a completely different semester (must not appear)
        $this->otherModuleId = DB::table('modules')->insertGetId([
            'module_name' => 'Unrelated Subject',
            'module_code' => 'XX999',
            'module_type' => 'elective',
            'credits'     => 2,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    private function postFiltered(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->actor)->postJson('/semester/get-filtered-modules', array_merge([
            'course_id'  => $this->courseId,
            'location'   => 'Welisara',
            'intake_id'  => $this->intakeId,
            'semester'   => $this->semesterId,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Core bug: endpoint must NOT return all modules
    // -------------------------------------------------------------------------

    public function test_endpoint_does_not_return_all_modules_in_database(): void
    {
        $response = $this->postFiltered();

        $response->assertOk();
        $moduleIds = collect($response->json('modules'))->pluck('module_id');

        $this->assertNotContains(
            $this->otherModuleId,
            $moduleIds,
            'Module not assigned to this semester must not be returned'
        );
    }

    public function test_endpoint_returns_only_modules_for_the_given_semester(): void
    {
        $response = $this->postFiltered();

        $response->assertOk();
        $moduleIds = collect($response->json('modules'))->pluck('module_id');

        $this->assertContains($this->moduleId, $moduleIds);
        $this->assertCount(1, $moduleIds);
    }

    public function test_module_response_includes_required_fields(): void
    {
        $response = $this->postFiltered();

        $response->assertOk();
        $module = collect($response->json('modules'))->first();

        $this->assertArrayHasKey('module_id',   $module);
        $this->assertArrayHasKey('module_name',  $module);
        $this->assertArrayHasKey('module_code',  $module);
        $this->assertArrayHasKey('module_type',  $module);
        $this->assertArrayHasKey('credits',      $module);
    }

    // -------------------------------------------------------------------------
    // Ownership validation: semester must belong to given course AND intake
    // -------------------------------------------------------------------------

    public function test_cross_cohort_request_is_rejected(): void
    {
        // Create a second course — the semester belongs to courseId, not this one
        $otherCourseId = DB::table('courses')->insertGetId([
            'course_name'         => 'BBA',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'entry_qualification' => 'A/L Pass',
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $response = $this->postFiltered(['course_id' => $otherCourseId]);

        // Endpoint must reject: semester does not belong to that course
        $response->assertStatus(422);
        $this->assertEmpty($response->json('modules'));
    }

    public function test_wrong_intake_is_rejected(): void
    {
        $otherIntakeId = DB::table('intakes')->insertGetId([
            'course_id'        => $this->courseId,
            'course_name'      => 'BSc CS',
            'batch'            => '2025-01',
            'batch_size'       => 50,
            'intake_mode'      => 'Physical',
            'intake_type'      => 'Fulltime',
            'registration_fee' => '1000',
            'franchise_payment'=> '0',
            'course_fee'       => '500000',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYears(3)->toDateString(),
            'location'         => 'Welisara',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $response = $this->postFiltered(['intake_id' => $otherIntakeId]);

        $response->assertStatus(422);
        $this->assertEmpty($response->json('modules'));
    }

    // -------------------------------------------------------------------------
    // Validation: non-existent semester ID is rejected before DB query
    // -------------------------------------------------------------------------

    public function test_nonexistent_semester_id_fails_validation(): void
    {
        $response = $this->postFiltered(['semester' => 99999]);

        $response->assertStatus(422);
    }

    public function test_create_mode_loads_course_modules_by_semester_number(): void
    {
        $semesterOneModuleId = DB::table('modules')->insertGetId([
            'module_name' => 'Programming Fundamentals',
            'module_code' => 'CS100',
            'module_type' => 'core',
            'credits'     => 3,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        $semesterTwoModuleId = DB::table('modules')->insertGetId([
            'module_name' => 'Databases',
            'module_code' => 'CS200',
            'module_type' => 'core',
            'credits'     => 3,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        DB::table('course_modules')->insert([
            [
                'course_id'  => $this->courseId,
                'module_id'  => $semesterOneModuleId,
                'semester'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_id'  => $this->courseId,
                'module_id'  => $semesterTwoModuleId,
                'semester'   => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->postFiltered([
            'semester' => 1,
            'creating' => true,
        ]);

        $response->assertOk();
        $moduleIds = collect($response->json('modules'))->pluck('module_id');

        $this->assertContains($semesterOneModuleId, $moduleIds);
        $this->assertNotContains($semesterTwoModuleId, $moduleIds);
        $this->assertNotContains($this->otherModuleId, $moduleIds);
    }

    public function test_create_mode_rejects_semester_number_beyond_course_length(): void
    {
        $response = $this->postFiltered([
            'semester' => 99,
            'creating' => true,
        ]);

        $response->assertStatus(422);
        $this->assertEmpty($response->json('modules'));
    }

    public function test_create_mode_falls_back_to_degree_catalog_when_course_modules_empty(): void
    {
        $certificateModuleId = DB::table('modules')->insertGetId([
            'module_name'      => 'Certificate Unit',
            'module_code'      => 'CERT001',
            'module_category'  => 'certificate',
            'module_type'      => 'core',
            'credits'          => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $response = $this->postFiltered([
            'semester' => 1,
            'creating' => true,
        ]);

        $response->assertOk();
        $moduleIds = collect($response->json('modules'))->pluck('module_id');

        $this->assertContains($this->moduleId, $moduleIds);
        $this->assertContains($this->otherModuleId, $moduleIds);
        $this->assertNotContains($certificateModuleId, $moduleIds);
    }
}
