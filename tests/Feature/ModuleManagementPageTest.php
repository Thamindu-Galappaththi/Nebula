<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Module;
use App\Models\ModuleManagement;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\SpecializationRegistration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModuleManagementPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'modules@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('module.management'))
            ->assertOk()
            ->assertSee('Module Registration (Elective)')
            ->assertSee('module-management-page', false)
            ->assertSee('module-students-table', false)
            ->assertSee('id="studentSearch"', false)
            ->assertSee('id="selectAll"', false)
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('Nebula Institute of Technology - Moratuwa')
            ->assertSee('Nebula Institute of Technology - Peradeniya');
    }

    public function test_intakes_load_by_course_id(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $otherCourse = $this->makeCourse('BTEC Engineering');
        $this->makeIntake($otherCourse, '2025-JAN-B01');

        $this->actingAs($this->actor)
            ->postJson(route('module.management.getIntakes'), [
                'course_id' => $course->course_id,
                'location' => 'Welisara',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.intake_id', $intake->intake_id)
            ->assertJsonPath('data.0.intake_name', '2024-JUL-B08')
            ->assertJsonCount(1, 'data');
    }

    public function test_elective_students_are_scoped_to_their_specialization(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeElectiveModule($semester, ['Network Engineering']);

        $networkStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Network Engineering');
        $softwareStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199098765V', 'Software Engineering');
        $unassignedStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199011122V');

        $students = $this->actingAs($this->actor)
            ->postJson(route('module.management.getElectiveStudents'), [
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester_id' => $semester->id,
                'location' => 'Welisara',
                'specialization' => 'Network Engineering',
                'module_id' => $module->module_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $ids = collect($students)->pluck('student_id')->all();

        $this->assertSame([$networkStudent->student_id], $ids);
        $this->assertSame('Network Engineering', $students[0]['specialization']);
        $this->assertNotContains($softwareStudent->student_id, $ids);
        $this->assertNotContains($unassignedStudent->student_id, $ids);
    }

    public function test_specific_specialization_with_no_assignments_returns_empty(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');

        $this->actingAs($this->actor)
            ->postJson(route('module.management.getElectiveStudents'), [
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester_id' => $semester->id,
                'location' => 'Welisara',
                'specialization' => 'Network Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students', []);
    }

    public function test_already_registered_flag_is_returned_for_module(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeElectiveModule($semester, ['Software Engineering']);
        $registered = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');
        $pending = $this->makeRegisteredStudent($course, $intake, $semester, '199098765V', 'Software Engineering');

        ModuleManagement::forceCreate([
            'student_id' => $registered->student_id,
            'module_id' => $module->module_id,
            'intake_id' => $intake->intake_id,
            'course_id' => $course->course_id,
            'location' => 'Welisara',
            'specialization' => 'Software Engineering',
            'semester' => $semester->name,
        ]);

        $students = $this->actingAs($this->actor)
            ->postJson(route('module.management.getElectiveStudents'), [
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester_id' => $semester->id,
                'location' => 'Welisara',
                'specialization' => 'Software Engineering',
                'module_id' => $module->module_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $byId = collect($students)->keyBy('student_id');

        $this->assertTrue($byId[$registered->student_id]['already_registered']);
        $this->assertFalse($byId[$pending->student_id]['already_registered']);
    }

    public function test_elective_modules_filter_by_specialization(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $networkModule = $this->makeElectiveModule($semester, ['Network Engineering'], 'Routing');
        $softwareModule = $this->makeElectiveModule($semester, ['Software Engineering'], 'Web Apps');

        $this->actingAs($this->actor)
            ->postJson(route('module.management.getElectiveModules'), [
                'semester_id' => $semester->id,
                'course_id' => $course->course_id,
                'specialization' => 'Network Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.module_id', $networkModule->module_id)
            ->assertJsonCount(1, 'data');

        $this->assertNotEquals($softwareModule->module_id, $networkModule->module_id);
    }

    private function makeRegisteredStudent(
        Course $course,
        Intake $intake,
        Semester $semester,
        string $idValue,
        ?string $specialization = null
    ): Student {
        $student = $this->makeStudent($idValue);

        CourseRegistration::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'intake_id'         => $intake->intake_id,
            'status'            => 'Registered',
            'approval_status'   => 'Approved by manager',
            'location'          => 'Welisara',
            'registration_date' => now()->toDateString(),
            'course_registration_id' => 'CR-' . $idValue,
        ]);

        SemesterRegistration::forceCreate([
            'student_id'        => $student->student_id,
            'semester_id'       => $semester->id,
            'course_id'         => $course->course_id,
            'intake_id'         => $intake->intake_id,
            'location'          => 'Welisara',
            'status'            => 'registered',
            'registration_date' => now()->toDateString(),
        ]);

        if ($specialization) {
            SpecializationRegistration::forceCreate([
                'student_id'     => $student->student_id,
                'course_id'      => $course->course_id,
                'intake_id'      => $intake->intake_id,
                'location'       => 'Welisara',
                'specialization' => $specialization,
                'status'         => 'registered',
            ]);
        }

        return $student;
    }

    private function makeStudent(string $idValue = '199012345V'): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name'          => 'Test Student Full',
            'id_type'            => 'NIC',
            'id_value'           => $idValue,
            'gender'             => 'Male',
            'email'              => $idValue . '@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);
    }

    private function makeCourse(string $name = 'BTEC Computing'): Course
    {
        return Course::forceCreate([
            'course_name'         => $name,
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
            'specializations'     => ['Software Engineering', 'Network Engineering'],
        ]);
    }

    private function makeIntake(Course $course, string $batch = '2024-JUL-B08'): Intake
    {
        return Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch'             => $batch,
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);
    }

    private function makeSemester(Course $course, Intake $intake): Semester
    {
        return Semester::forceCreate([
            'name'       => 'Semester 1',
            'course_id'  => $course->course_id,
            'intake_id'  => $intake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(6)->toDateString(),
            'status'     => 'active',
        ]);
    }

    private function makeElectiveModule(Semester $semester, array $specializations, string $name = 'Network Elective'): Module
    {
        $module = Module::forceCreate([
            'module_name' => $name,
            'module_code' => strtoupper(substr(md5($name . $semester->id), 0, 8)),
            'module_type' => 'elective',
            'credits'     => 15,
        ]);

        DB::table('semester_module')->insert([
            'semester_id'     => $semester->id,
            'module_id'       => $module->module_id,
            'specialization'  => count($specializations) === 1 ? $specializations[0] : null,
            'specializations' => json_encode($specializations),
        ]);

        return $module;
    }
}
