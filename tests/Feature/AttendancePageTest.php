<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Module;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\SpecializationRegistration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendancePageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'attendance@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_marking_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('attendance'))
            ->assertOk()
            ->assertSee('Attendance')
            ->assertSee('attendance-page', false)
            ->assertSee('attendance-table', false)
            ->assertSee('attendance-bulk-actions', false)
            ->assertSee('attendance-file-picker', false)
            ->assertSee('modal-dialog-centered', false)
            ->assertSee('session-time-row', false)
            ->assertSee('sweetalert2@11.22.0', false)
            ->assertSee('Swal.fire', false)
            ->assertDontSee('window.confirm', false)
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('Nebula Institute of Technology - Moratuwa')
            ->assertSee('Nebula Institute of Technology - Peradeniya');
    }

    public function test_overall_page_renders_with_mobile_layout_and_exports(): void
    {
        $this->actingAs($this->actor)
            ->get(route('overall.attendance'))
            ->assertOk()
            ->assertSee('Overall Attendance')
            ->assertSee('overall-attendance-page', false)
            ->assertSee('overall-summary-table', false)
            ->assertSee('overall-export-actions', false)
            ->assertSee('Export to PDF')
            ->assertSee('Export to Excel')
            ->assertSee('Manage Sessions')
            ->assertSee('modal-dialog-centered', false)
            ->assertSee('session-time-row', false)
            ->assertSee('sweetalert2@11.22.0', false)
            ->assertSee('Swal.fire', false)
            ->assertDontSee('window.confirm', false)
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('Nebula Institute of Technology - Moratuwa')
            ->assertSee('Nebula Institute of Technology - Peradeniya');
    }

    public function test_overall_attendance_counts_lecture_and_tutorial_on_the_same_day_as_two_sessions(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $presentStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');
        $partialStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199098765V', 'Software Engineering');
        $date = now()->toDateString();

        $this->insertAttendance($presentStudent, $course, $intake, $semester, $module, $date, 'lectures', true);
        $this->insertAttendance($presentStudent, $course, $intake, $semester, $module, $date, 'tutorials', true);
        $this->insertAttendance($partialStudent, $course, $intake, $semester, $module, $date, 'lectures', true);
        $this->insertAttendance($partialStudent, $course, $intake, $semester, $module, $date, 'tutorials', false);

        $payload = $this->actingAs($this->actor)
            ->postJson(route('get.overall.attendance'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Software Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json();

        $rows = collect($payload['attendance'])->keyBy('student_id');
        $this->assertSame(2, $rows[$presentStudent->student_id]['total_sessions']);
        $this->assertSame(2, $rows[$presentStudent->student_id]['attended_sessions']);
        $this->assertEquals(100, $rows[$presentStudent->student_id]['percentage']);
        $this->assertSame(2, $rows[$partialStudent->student_id]['total_sessions']);
        $this->assertSame(1, $rows[$partialStudent->student_id]['attended_sessions']);
        $this->assertEquals(50, $rows[$partialStudent->student_id]['percentage']);

        $matrixDates = collect($payload['matrix']['rows'])->pluck('date')->all();
        $this->assertCount(2, $matrixDates);
        $this->assertTrue(collect($matrixDates)->contains(fn ($label) => str_contains((string) $label, 'Lecture')));
        $this->assertTrue(collect($matrixDates)->contains(fn ($label) => str_contains((string) $label, 'Tutorial')));
    }

    public function test_overall_attendance_counts_two_lectures_on_the_same_day_as_two_sessions(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $student = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');
        $date = now()->toDateString();

        $this->insertAttendance($student, $course, $intake, $semester, $module, $date, 'lectures', true, '09:00:00', '11:00:00');
        $this->insertAttendance($student, $course, $intake, $semester, $module, $date, 'lectures', true, '12:00:00', '14:00:00');

        $payload = $this->actingAs($this->actor)
            ->postJson(route('get.overall.attendance'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Software Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json();

        $row = collect($payload['attendance'])->firstWhere('student_id', $student->student_id);
        $this->assertSame(2, $row['total_sessions']);
        $this->assertSame(2, $row['attended_sessions']);
        $this->assertEquals(100, $row['percentage']);

        $matrixDates = collect($payload['matrix']['rows'])->pluck('date')->all();
        $this->assertCount(2, $matrixDates);
        $this->assertTrue(collect($matrixDates)->contains(fn ($label) => str_contains((string) $label, '09:00')));
        $this->assertTrue(collect($matrixDates)->contains(fn ($label) => str_contains((string) $label, '12:00')));
    }

    public function test_staff_can_correct_a_session_saved_on_the_wrong_date_and_type(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $student = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');
        $wrongDate = now()->subDay()->toDateString();
        $correctDate = now()->toDateString();

        $this->insertAttendance($student, $course, $intake, $semester, $module, $wrongDate, 'labs', true);

        $this->actingAs($this->actor)
            ->postJson(route('attendance.session.update'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'original_date' => $wrongDate,
                'original_attendance_type' => 'labs',
                'original_session_start' => '',
                'date' => $correctDate,
                'attendance_type' => 'lectures',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->student_id,
            'module_id' => $module->module_id,
            'date' => $correctDate,
            'attendance_type' => 'lectures',
            'status' => 1,
        ]);
        $this->assertDatabaseMissing('attendance', [
            'student_id' => $student->student_id,
            'module_id' => $module->module_id,
            'date' => $wrongDate,
            'attendance_type' => 'labs',
        ]);
    }

    public function test_staff_can_delete_an_attendance_session(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $student = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');
        $date = now()->toDateString();

        $this->insertAttendance($student, $course, $intake, $semester, $module, $date, 'labs', true);

        $this->actingAs($this->actor)
            ->postJson(route('attendance.session.delete'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'original_date' => $date,
                'original_attendance_type' => 'labs',
                'original_session_start' => '',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('sessions', []);

        $this->assertDatabaseMissing('attendance', [
            'student_id' => $student->student_id,
            'module_id' => $module->module_id,
            'date' => $date,
        ]);
    }

    public function test_correcting_a_session_does_not_overwrite_another_existing_session(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $student = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');
        $date = now()->toDateString();

        $this->insertAttendance($student, $course, $intake, $semester, $module, $date, 'lectures', true);
        $this->insertAttendance($student, $course, $intake, $semester, $module, $date, 'labs', false);

        $this->actingAs($this->actor)
            ->postJson(route('attendance.session.update'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'original_date' => $date,
                'original_attendance_type' => 'labs',
                'original_session_start' => '',
                'date' => $date,
                'attendance_type' => 'lectures',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('attendance', [
            'student_id' => $student->student_id,
            'attendance_type' => 'labs',
            'status' => 0,
        ]);
    }

    public function test_overall_attendance_does_not_count_duplicate_lecture_resaves_as_two_sessions(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $student = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');
        $date = now()->toDateString();

        $this->insertAttendance($student, $course, $intake, $semester, $module, $date, 'lectures', true);
        $this->insertAttendance($student, $course, $intake, $semester, $module, $date, 'lectures', true);

        $payload = $this->actingAs($this->actor)
            ->postJson(route('get.overall.attendance'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Software Engineering',
            ])
            ->assertOk()
            ->json();

        $row = collect($payload['attendance'])->firstWhere('student_id', $student->student_id);
        $this->assertSame(1, $row['total_sessions']);
        $this->assertSame(1, $row['attended_sessions']);
    }

    public function test_specific_specialization_with_no_assignments_returns_empty_students(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');

        $this->actingAs($this->actor)
            ->postJson(route('get.students.for.attendance'), [
                'location' => 'Welisara',
                'course_type' => 'degree',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Network Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students', []);
    }

    public function test_core_attendance_falls_back_to_course_registration_when_semester_registration_is_missing(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeSpecializedCompulsoryModule($semester, ['Software Engineering', 'Network Engineering'], 'Networking In The Cloud');
        $student = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering', false);

        $students = $this->actingAs($this->actor)
            ->postJson(route('get.students.for.attendance'), [
                'location' => 'Welisara',
                'course_type' => 'diploma',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Software Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $this->assertSame([$student->student_id], collect($students)->pluck('student_id')->all());
        $this->assertSame('CR-199012345V', $students[0]['course_registration_id']);
    }

    public function test_core_module_students_are_scoped_to_specialization(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);

        $networkStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Network Engineering');
        $softwareStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199098765V', 'Software Engineering');

        $students = $this->actingAs($this->actor)
            ->postJson(route('get.students.for.attendance'), [
                'location' => 'Welisara',
                'course_type' => 'degree',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Network Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $ids = collect($students)->pluck('student_id')->all();

        $this->assertSame([$networkStudent->student_id], $ids);
        $this->assertSame('CR-199012345V', $students[0]['course_registration_id']);
        $this->assertNotContains($softwareStudent->student_id, $ids);
    }

    public function test_specialized_compulsory_module_only_lists_students_for_that_track(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeSpecializedCompulsoryModule($semester, ['Network Engineering'], 'Cloud Fundamentals');

        $networkStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Network Engineering');
        $softwareStudent = $this->makeRegisteredStudent($course, $intake, $semester, '199098765V', 'Software Engineering');

        $aiStudents = $this->actingAs($this->actor)
            ->postJson(route('get.students.for.attendance'), [
                'location' => 'Welisara',
                'course_type' => 'degree',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Network Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $this->assertSame([$networkStudent->student_id], collect($aiStudents)->pluck('student_id')->all());
        $this->assertNotContains($softwareStudent->student_id, collect($aiStudents)->pluck('student_id')->all());

        $this->actingAs($this->actor)
            ->postJson(route('get.students.for.attendance'), [
                'location' => 'Welisara',
                'course_type' => 'degree',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Software Engineering',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students', []);

        $commonStudents = $this->actingAs($this->actor)
            ->postJson(route('get.students.for.attendance'), [
                'location' => 'Welisara',
                'course_type' => 'degree',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Common',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $this->assertSame([$networkStudent->student_id], collect($commonStudents)->pluck('student_id')->all());
    }

    public function test_template_download_requires_specialization_for_specialized_course(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);

        $this->actingAs($this->actor)
            ->get(route('attendance.download.template', [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'date' => now()->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonPath('error', 'Specialization is required for this course.');
    }

    public function test_template_download_returns_xlsx_for_valid_filters(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Network Engineering');

        $response = $this->actingAs($this->actor)
            ->get(route('attendance.download.template', [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Network Engineering',
                'date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString('CR-199012345V', $this->xlsxText($response->streamedContent()));
    }

    public function test_excel_export_returns_json_when_specialization_missing(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);

        $this->actingAs($this->actor)
            ->postJson(route('download.attendance.excel'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Specialization is required for this course.');
    }

    public function test_excel_export_returns_xlsx_with_course_registration_id(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeCoreModule($semester);
        $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Network Engineering');

        $response = $this->actingAs($this->actor)
            ->post(route('download.attendance.excel'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Network Engineering',
            ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString('CR-199012345V', $this->xlsxText($response->streamedContent()));
    }

    public function test_elective_template_does_not_include_unregistered_students(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeElectiveModule($semester, ['Network Engineering']);
        $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Network Engineering');

        $response = $this->actingAs($this->actor)
            ->get(route('attendance.download.template', [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Network Engineering',
                'date' => now()->toDateString(),
            ]));

        $response->assertOk();
        $this->assertStringNotContainsString('CR-199012345V', $this->xlsxText($response->streamedContent()));
    }

    public function test_semester_assigned_elective_without_enrolments_lists_class_students(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
        $module = $this->makeElectiveModule($semester, [], 'Calculus To Solve Engineering Problems');
        $student = $this->makeRegisteredStudent($course, $intake, $semester, '199012345V', 'Software Engineering');

        $students = $this->actingAs($this->actor)
            ->postJson(route('get.students.for.attendance'), [
                'location' => 'Welisara',
                'course_type' => 'diploma',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester' => $semester->id,
                'module_id' => $module->module_id,
                'specialization' => 'Common',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $this->assertSame([$student->student_id], collect($students)->pluck('student_id')->all());
    }

    private function insertAttendance(
        Student $student,
        Course $course,
        Intake $intake,
        Semester $semester,
        Module $module,
        string $date,
        string $type,
        bool $present,
        ?string $sessionStart = null,
        ?string $sessionEnd = null
    ): void {
        $row = [
            'location' => 'Welisara',
            'course_id' => $course->course_id,
            'module_id' => $module->module_id,
            'intake_id' => $intake->intake_id,
            'semester' => $semester->name,
            'date' => $date,
            'student_id' => $student->student_id,
            'status' => $present ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('attendance', 'attendance_type')) {
            $row['attendance_type'] = $type;
        }

        if (Schema::hasColumn('attendance', 'session_start')) {
            $row['session_start'] = $sessionStart;
            $row['session_end'] = $sessionEnd;
        }

        DB::table('attendance')->insert($row);
    }

    private function xlsxText(string $binary): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($tmp, $binary);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmp) === true, 'Download was not a valid XLSX archive.');

        $contents = ($zip->getFromName('xl/sharedStrings.xml') ?: '')
            . ($zip->getFromName('xl/worksheets/sheet1.xml') ?: '');
        $zip->close();
        unlink($tmp);

        return $contents;
    }

    private function makeRegisteredStudent(
        Course $course,
        Intake $intake,
        Semester $semester,
        string $idValue,
        ?string $specialization = null,
        bool $withSemesterRegistration = true
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

        if ($withSemesterRegistration) {
            SemesterRegistration::forceCreate([
                'student_id'        => $student->student_id,
                'semester_id'       => $semester->id,
                'course_id'         => $course->course_id,
                'intake_id'         => $intake->intake_id,
                'location'          => 'Welisara',
                'status'            => 'registered',
                'registration_date' => now()->toDateString(),
            ]);
        }

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

    private function makeCoreModule(Semester $semester, string $name = 'Programming Fundamentals'): Module
    {
        $module = Module::forceCreate([
            'module_name' => $name,
            'module_code' => strtoupper(substr(md5($name . $semester->id), 0, 8)),
            'module_type' => 'core',
            'credits'     => 15,
        ]);

        DB::table('semester_module')->insert([
            'semester_id'     => $semester->id,
            'module_id'       => $module->module_id,
            'specialization'  => null,
            'specializations' => null,
        ]);

        return $module;
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

    private function makeSpecializedCompulsoryModule(Semester $semester, array $specializations, string $name = 'Cloud Fundamentals'): Module
    {
        $module = Module::forceCreate([
            'module_name' => $name,
            'module_code' => strtoupper(substr(md5($name . $semester->id), 0, 8)),
            'module_type' => 'special_unit_compulsory',
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
