<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamResultsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'exam-results@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_exam_results_page_is_mobile_safe_and_keeps_tab_sections(): void
    {
        $this->actingAs($this->actor)
            ->get(route('student.exam.result.management'))
            ->assertOk()
            ->assertSee('exam-results-page', false)
            ->assertSee('col-12 col-md-3', false)
            ->assertSee('exam-results-column-actions', false)
            ->assertSee('exam-results-table-scroll', false)
            ->assertSee('sweetalert2@11.22.0', false);

        $source = file_get_contents(resource_path('views/exam_&_results/exam_results.blade.php'));

        $this->assertStringContainsString('specialization: degreeSpecialization.value', $source);
        $this->assertStringContainsString("getActiveTab() + '-' + type + 'ColumnHeader'", $source);
        $this->assertStringNotContainsString("getElementById('certResultsTableSection').style.display = 'none'", $source);
        $this->assertStringNotContainsString('col-sm-2 col-form-label', $source);
        $this->assertStringContainsString('Swal.fire', $source);
        $this->assertStringContainsString('exam-results-tabs', $source);
        $this->assertStringContainsString('No intakes are included for this course and location.', $source);
        $this->assertStringContainsString('No semesters are included for this course and intake.', $source);
        $this->assertStringContainsString('No modules are included for this', $source);
        $this->assertStringNotContainsString('degree_intake_hint', $source);
        $this->assertStringNotContainsString('exam-results-empty-hint', $source);
        $this->assertStringContainsString('registration_id: data.registration_id || \'\'', $source);
        $this->assertStringNotContainsString('registration_id: resolvedId', $source);
        $this->assertStringContainsString("new Option('All', '')", $source);
        $this->assertStringContainsString('return degreeSpecializationsLoaded;', $source);
        $this->assertStringContainsString('scheduleStudentNameLookup', $source);
        $this->assertStringContainsString('nameInput.value = data.name || \'\'', $source);
        $this->assertStringContainsString('idCell.textContent = result.registration_id || \'\'', $source);
    }

    public function test_student_name_lookup_accepts_nic(): void
    {
        $student = Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'S. Silva',
            'full_name'          => 'Sam Silva',
            'id_type'            => 'NIC',
            'id_value'           => '200416003270',
            'gender'             => 'Male',
            'email'              => 'sam-exam@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('get.student.name'), [
                'student_id' => '200416003270',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('name', 'Sam Silva')
            ->assertJsonPath('student_id', $student->student_id)
            ->assertJsonPath('registration_id', '');
    }

    public function test_student_name_lookup_returns_course_registration_number(): void
    {
        $student = Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'S. Silva',
            'full_name'          => 'Sam Silva',
            'id_type'            => 'NIC',
            'id_value'           => '200416003270',
            'gender'             => 'Male',
            'email'              => 'sam-reg-exam@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        $course = Course::forceCreate([
            'course_name'         => 'BTEC Computing',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
        ]);

        $intake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch'             => '2026-August',
            'batch_size'        => 40,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
        ]);

        CourseRegistration::forceCreate([
            'student_id'             => $student->student_id,
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'course_registration_id' => 'NIT/DEG/2026/001',
            'status'                 => 'Registered',
            'approval_status'        => 'Approved by manager',
            'location'               => 'Welisara',
            'registration_date'      => now()->toDateString(),
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('get.student.name'), [
                'student_id' => '200416003270',
                'course_id'  => $course->course_id,
                'intake_id'  => $intake->intake_id,
                'location'   => 'Welisara',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('name', 'Sam Silva')
            ->assertJsonPath('student_id', $student->student_id)
            ->assertJsonPath('registration_id', 'NIT/DEG/2026/001')
            ->assertJsonMissing(['registration_id' => $student->student_id]);

        $this->actingAs($this->actor)
            ->postJson(route('get.student.name'), [
                'student_id' => 'NIT/DEG/2026/001',
                'course_id'  => $course->course_id,
                'intake_id'  => $intake->intake_id,
                'location'   => 'Welisara',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student_id', $student->student_id)
            ->assertJsonPath('registration_id', 'NIT/DEG/2026/001');

        $this->actingAs($this->actor)
            ->postJson(route('get.student.name'), [
                'student_id' => (string) $student->student_id,
                'course_id'  => $course->course_id + 99,
                'intake_id'  => $intake->intake_id + 99,
                'location'   => 'Welisara',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('name', 'Sam Silva')
            ->assertJsonPath('student_id', $student->student_id)
            ->assertJsonPath('registration_id', 'NIT/DEG/2026/001');
    }

    public function test_certificate_template_download_does_not_require_semester(): void
    {
        $courseId = \Illuminate\Support\Facades\DB::table('courses')->insertGetId([
            'course_name'         => 'Certificate Template Course',
            'course_type'         => 'certificate',
            'location'            => 'Welisara',
            'no_of_semesters'     => 1,
            'duration'            => '1 year',
            'min_credits'         => 30,
            'entry_qualification' => 'A/L Pass',
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $intakeId = \Illuminate\Support\Facades\DB::table('intakes')->insertGetId([
            'location'          => 'Welisara',
            'course_id'         => $courseId,
            'course_name'       => 'Certificate Template Course',
            'batch'             => '2026-01',
            'batch_size'        => 20,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson(route('download.exam.results.template'), [
                'course_type' => 'certificate',
                'course_id'   => $courseId,
                'intake_id'   => $intakeId,
                'location'    => 'Welisara',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Student Name', $response->getContent());
        $this->assertStringContainsString('Marks', $response->getContent());
    }

    public function test_degree_template_download_returns_csv(): void
    {
        $courseId = \Illuminate\Support\Facades\DB::table('courses')->insertGetId([
            'course_name'         => 'BTEC Computing',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
            'course_medium'       => 'English',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $intakeId = \Illuminate\Support\Facades\DB::table('intakes')->insertGetId([
            'location'          => 'Welisara',
            'course_id'         => $courseId,
            'course_name'       => 'BTEC Computing',
            'batch'             => '2026-August',
            'batch_size'        => 40,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $moduleId = \Illuminate\Support\Facades\DB::table('modules')->insertGetId([
            'module_name'       => 'Programming',
            'module_code'       => 'PRG101',
            'module_type'       => 'core',
            'credits'           => 15,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $semesterId = \Illuminate\Support\Facades\DB::table('semesters')->insertGetId([
            'name'       => '1',
            'course_id'  => $courseId,
            'intake_id'  => $intakeId,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(6)->toDateString(),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson(route('download.exam.results.template'), [
                'course_type' => 'degree',
                'course_id'   => $courseId,
                'intake_id'   => $intakeId,
                'location'    => 'Welisara',
                'semester'    => $semesterId,
                'module_id'   => $moduleId,
            ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('Student Name,Course Name,Module Name', $response->getContent());
    }
}
