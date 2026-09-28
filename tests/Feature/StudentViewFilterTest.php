<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for StudentViewController filter logic.
 *
 * Specifically guards against the two logic errors that were fixed:
 *   1. student_id search bypassing course/intake/status/location constraints
 *      (was: where(student_id)->orWhere(id_value) without grouping)
 *   2. Course and intake using separate whereHas so a student could match
 *      course A on one registration and intake B on another
 */
class StudentViewFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'admin@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    private function makeStudent(string $idValue, array $studentAttrs = []): Student
    {
        return Student::forceCreate(array_merge([
            'title'              => 'Mr',
            'name_with_initials' => 'Test Student',
            'full_name'          => 'Test Student Full',
            'id_type'            => 'NIC',
            'id_value'           => $idValue,
            'gender'             => 'Male',
            'email'              => $idValue . '@test.lk',
            'status'             => 'Registered',
            'academic_status'    => 'active',
            'institute_location' => 'Welisara',
        ], $studentAttrs));
    }

    private function makeRegistration(int $studentId, int $courseId, int $intakeId, string $location = 'Welisara'): CourseRegistration
    {
        $course = Course::find($courseId);
        if (!$course) {
            $course = Course::forceCreate([
                'course_id'           => $courseId,
                'course_name'         => 'Course ' . $courseId,
                'course_type'         => 'degree',
                'duration'            => '3 years',
                'no_of_semesters'     => 6,
                'min_credits'         => 120,
                'conducted_by'        => 1,
                'course_medium'       => 'English',
                'entry_qualification' => 'A/L',
                'location'            => $location,
            ]);
        }

        $intake = Intake::find($intakeId);
        if (!$intake) {
            $intake = Intake::forceCreate([
                'intake_id'         => $intakeId,
                'batch'             => 'Batch ' . $intakeId,
                'course_id'         => $course->course_id,
                'course_name'       => $course->course_name,
                'batch_size'        => 50,
                'intake_mode'       => 'Physical',
                'intake_type'       => 'Fulltime',
                'registration_fee'  => '1000',
                'franchise_payment' => '0',
                'course_fee'        => '50000',
                'location'          => $location,
                'start_date'        => now()->toDateString(),
                'end_date'          => now()->addYear()->toDateString(),
            ]);
        }

        return CourseRegistration::forceCreate([
            'student_id'        => $studentId,
            'course_id'         => $courseId,
            'intake_id'         => $intakeId,
            'status'            => 'Registered',
            'approval_status'   => 'Approved by manager',
            'location'          => $location,
            'registration_date' => now()->toDateString(),
        ]);
    }

    private function route(): string
    {
        return '/students/filter';
    }

    public function test_view_page_renders_export_actions(): void
    {
        $this->actingAs($this->actor)
            ->get('/students/view')
            ->assertOk()
            ->assertSee('All Students View')
            ->assertSee('Export Excel')
            ->assertSee('Export PDF')
            ->assertSee('Per page')
            ->assertSee("new Option('Common (No Specialization)', 'Common')", false)
            ->assertDontSee('Export CSV');
    }

    public function test_student_id_search_without_course_filter_returns_student(): void
    {
        $student = $this->makeStudent('199012345678');

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), ['student_id' => '199012345678']);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('student_id');
        $this->assertContains($student->student_id, $ids);
    }

    public function test_student_id_search_with_mismatched_course_does_not_return_student(): void
    {
        $student = $this->makeStudent('199099999999');
        $this->makeRegistration($student->student_id, 1, 1);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'student_id' => '199099999999',
                'course_id'  => 2,
            ]);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('student_id');
        $this->assertNotContains(
            $student->student_id,
            $ids,
            'A student_id match must not bypass the course_id constraint'
        );
    }

    public function test_student_with_course_a_intake_b_on_separate_registrations_is_excluded(): void
    {
        $student = $this->makeStudent('200011112222');
        $this->makeRegistration($student->student_id, 10, 100);
        $this->makeRegistration($student->student_id, 20, 200);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'course_id'  => 10,
                'intake_id'  => 200,
            ]);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('student_id');
        $this->assertNotContains(
            $student->student_id,
            $ids,
            'Student must not appear when no single registration satisfies both course and intake'
        );
    }

    public function test_student_with_matching_course_and_intake_on_same_registration_is_included(): void
    {
        $student = $this->makeStudent('200099998888');
        $this->makeRegistration($student->student_id, 10, 100);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'course_id'  => 10,
                'intake_id'  => 100,
            ]);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('student_id');
        $this->assertContains(
            $student->student_id,
            $ids,
            'Student should appear when a single registration satisfies both course and intake'
        );
    }

    public function test_all_specializations_is_optional_for_courses_with_specializations(): void
    {
        $student = $this->makeStudent('200077776666');
        $registration = $this->makeRegistration($student->student_id, 30, 300);
        $registration->course->update([
            'specializations' => ['Software Engineering', 'Networking'],
        ]);

        foreach (['all', ''] as $specialization) {
            $response = $this->actingAs($this->actor)
                ->postJson($this->route(), [
                    'course_id' => $registration->course_id,
                    'specialization' => $specialization,
                ]);

            $response->assertOk()->assertJsonPath('success', true);
        }
    }

    public function test_filter_returns_specialization_from_specialization_registrations(): void
    {
        $student = $this->makeStudent('200055554444');
        $registration = $this->makeRegistration($student->student_id, 40, 400);

        DB::table('specialization_registrations')->insert([
            'student_id' => $student->student_id,
            'course_id' => $registration->course_id,
            'intake_id' => $registration->intake_id,
            'location' => 'Welisara',
            'specialization' => 'Software Engineering',
            'status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'course_id' => $registration->course_id,
                'intake_id' => $registration->intake_id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.0.student_id', $student->student_id)
            ->assertJsonPath('data.0.specialization', 'Software Engineering');
    }

    public function test_specialization_is_not_copied_from_another_course_registration(): void
    {
        $student = $this->makeStudent('200528805146');
        $degree = $this->makeRegistration($student->student_id, 45, 40);
        $foundation = $this->makeRegistration($student->student_id, 47, 71);
        $degree->course->update([
            'course_name' => 'B.Eng. (Hons) Electrical & Electronic Engineering',
            'specializations' => ['Electrical & Electronic Engineering'],
        ]);
        $foundation->course->update([
            'course_name' => 'Pearson BTEC International Level 03 Foundation Diploma in Engineering',
            'specializations' => null,
        ]);
        $foundation->intake->update(['batch' => 'BTEC Foundation B04']);

        DB::table('specialization_registrations')->insert([
            'student_id' => $student->student_id,
            'course_id' => $degree->course_id,
            'intake_id' => $degree->intake_id,
            'location' => 'Welisara',
            'specialization' => 'Electrical & Electronic Engineering',
            'status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), ['student_id' => '200528805146']);

        $response->assertOk();
        $rows = collect($response->json('data'));
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn ($row) => $row['student_id'] === $student->student_id));

        $byCourse = $rows->keyBy('course');
        $this->assertSame('-', $byCourse['Pearson BTEC International Level 03 Foundation Diploma in Engineering']['specialization']);
        $this->assertSame('BTEC Foundation B04', $byCourse['Pearson BTEC International Level 03 Foundation Diploma in Engineering']['intake']);
        $this->assertSame(
            'Electrical & Electronic Engineering',
            $byCourse['B.Eng. (Hons) Electrical & Electronic Engineering']['specialization']
        );
    }

    public function test_all_courses_filter_returns_one_row_per_course_registration(): void
    {
        $student = $this->makeStudent('200416003270');
        $dataScience = $this->makeRegistration($student->student_id, 46, 41);
        $foundation = $this->makeRegistration($student->student_id, 47, 71);
        $dataScience->course->update(['course_name' => 'B.Sc. (Hons) Data Science']);
        $dataScience->intake->update(['batch' => '2025-JUl-B09-DS']);
        $foundation->course->update(['course_name' => 'Pearson BTEC International Level 03 Foundation Diploma in Engineering']);
        $foundation->intake->update(['batch' => 'BTEC Foundation B04']);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), ['student_id' => '200416003270']);

        $response->assertOk();
        $rows = collect($response->json('data'));

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [
                'B.Sc. (Hons) Data Science',
                'Pearson BTEC International Level 03 Foundation Diploma in Engineering',
            ],
            $rows->pluck('course')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['2025-JUl-B09-DS', 'BTEC Foundation B04'],
            $rows->pluck('intake')->all()
        );
    }

    public function test_named_course_filter_still_shows_that_course_specialization(): void
    {
        $student = $this->makeStudent('200528805147');
        $degree = $this->makeRegistration($student->student_id, 48, 80);
        $foundation = $this->makeRegistration($student->student_id, 49, 81);
        $degree->course->update([
            'course_name' => 'B.Eng. (Hons) Electrical & Electronic Engineering',
            'specializations' => ['Electrical & Electronic Engineering'],
        ]);
        $foundation->course->update([
            'course_name' => 'Pearson BTEC International Level 03 Foundation Diploma in Engineering',
            'specializations' => null,
        ]);

        DB::table('specialization_registrations')->insert([
            'student_id' => $student->student_id,
            'course_id' => $degree->course_id,
            'intake_id' => $degree->intake_id,
            'location' => 'Welisara',
            'specialization' => 'Electrical & Electronic Engineering',
            'status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'student_id' => '200528805147',
                'course_id' => $degree->course_id,
                'intake_id' => $degree->intake_id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.0.student_id', $student->student_id)
            ->assertJsonPath('data.0.course', 'B.Eng. (Hons) Electrical & Electronic Engineering')
            ->assertJsonPath('data.0.specialization', 'Electrical & Electronic Engineering');
    }

    public function test_common_filter_excludes_students_assigned_to_a_track(): void
    {
        $unassigned = $this->makeStudent('2000406913503');
        $assigned = $this->makeStudent('200528805148');
        $unassignedReg = $this->makeRegistration($unassigned->student_id, 62, 620);
        $this->makeRegistration($assigned->student_id, 62, 620);
        $unassignedReg->course->update([
            'specializations' => ['Electrical & Electronic Engineering'],
        ]);

        DB::table('specialization_registrations')->insert([
            'student_id' => $assigned->student_id,
            'course_id' => 62,
            'intake_id' => 620,
            'location' => 'Welisara',
            'specialization' => 'Electrical & Electronic Engineering',
            'status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'course_id' => 62,
                'intake_id' => 620,
                'specialization' => 'Common',
            ]);

        $response->assertOk();
        $students = collect($response->json('data'));
        $ids = $students->pluck('student_id');

        $this->assertContains($unassigned->student_id, $ids);
        $this->assertNotContains($assigned->student_id, $ids);
        $this->assertSame('-', $students->firstWhere('student_id', $unassigned->student_id)['specialization']);
    }

    public function test_named_specialization_filter_does_not_return_unassigned_students(): void
    {
        $unassigned = $this->makeStudent('2000406913510');
        $assigned = $this->makeStudent('200528805160');
        $unassignedReg = $this->makeRegistration($unassigned->student_id, 63, 630);
        $this->makeRegistration($assigned->student_id, 63, 630);
        $unassignedReg->course->update([
            'specializations' => ['Data Analytics'],
        ]);

        DB::table('specialization_registrations')->insert([
            'student_id' => $assigned->student_id,
            'course_id' => 63,
            'intake_id' => 630,
            'location' => 'Welisara',
            'specialization' => 'Data Analytics',
            'status' => 'registered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'course_id' => 63,
                'intake_id' => 630,
                'specialization' => 'Data Analytics',
            ]);

        $response->assertOk();
        $students = collect($response->json('data'));
        $ids = $students->pluck('student_id');

        $this->assertContains($assigned->student_id, $ids);
        $this->assertNotContains($unassigned->student_id, $ids);
        $this->assertSame('Data Analytics', $students->first()['specialization']);
    }

    public function test_named_specialization_with_no_assignments_returns_no_students(): void
    {
        $student = $this->makeStudent('2000406913511');
        $registration = $this->makeRegistration($student->student_id, 64, 640);
        $registration->course->update([
            'specializations' => ['Data Analytics'],
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson($this->route(), [
                'course_id' => 64,
                'intake_id' => 640,
                'specialization' => 'Data Analytics',
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame([], $response->json('data'));
    }

    private function excelSheetFromResponse($response)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'svx');
        file_put_contents($tmp, $response->streamedContent());
        $sheet = (new \PhpOffice\PhpSpreadsheet\Reader\Xlsx())->load($tmp)->getActiveSheet();
        unlink($tmp);

        return $sheet;
    }

    public function test_pdf_omits_specialization_when_course_has_no_tracks(): void
    {
        $html = view('student_management.student_view_pdf', [
            'students' => [[
                'full_name' => 'BTEC Student',
                'id_value' => '2000406913503',
                'course' => 'Pearson BTEC International Level 03 Foundation Diploma in Engineering',
                'intake' => 'BTEC Foundation B04',
                'specialization' => '-',
                'location' => 'Welisara',
                'academic_status' => 'active',
            ]],
            'columns' => ['student', 'nic', 'course', 'intake', 'location', 'status'],
            'columnLabels' => [
                'student' => 'Student',
                'nic' => 'NIC',
                'course' => 'Course',
                'intake' => 'Intake',
                'specialization' => 'Specialization',
                'location' => 'Location',
                'status' => 'Status',
            ],
            'meta' => [
                'studentId' => 'All',
                'courseText' => 'Pearson BTEC International Level 03 Foundation Diploma in Engineering',
                'intakeText' => 'BTEC Foundation B04',
                'specializationText' => null,
                'statusText' => 'All',
            ],
            'total_count' => 1,
        ])->render();

        $this->assertStringNotContainsString('<th>Specialization</th>', $html);
        $this->assertStringNotContainsString('<strong>Specialization:</strong>', $html);
    }

    public function test_excel_export_omits_specialization_heading_when_common_selected(): void
    {
        $student = $this->makeStudent('199033330000');
        $registration = $this->makeRegistration($student->student_id, 70, 700);
        $registration->course->update([
            'specializations' => ['Electrical & Electronic Engineering'],
        ]);

        $response = $this->actingAs($this->actor)
            ->post('/students/view/export-excel', [
                'course_id' => $registration->course_id,
                'intake_id' => $registration->intake_id,
                'specialization' => 'Common',
                'columns' => ['student', 'nic', 'course', 'intake', 'specialization', 'location', 'status'],
            ]);

        $response->assertOk();
        $sheet = $this->excelSheetFromResponse($response);

        $this->assertNotContains('Specialization', $sheet->rangeToArray('A4:H4')[0]);
        $this->assertStringNotContainsString('Specialization: Common', (string) $sheet->getCell('A2')->getValue());
    }

    public function test_excel_export_omits_specialization_heading_when_course_has_no_tracks(): void
    {
        $student = $this->makeStudent('199044440000');
        $registration = $this->makeRegistration($student->student_id, 71, 701);
        $registration->course->update([
            'course_name' => 'Pearson BTEC International Level 03 Foundation Diploma in Engineering',
            'specializations' => null,
        ]);

        $response = $this->actingAs($this->actor)
            ->post('/students/view/export-excel', [
                'course_id' => $registration->course_id,
                'intake_id' => $registration->intake_id,
                'specialization' => 'all',
                'columns' => ['student', 'nic', 'course', 'intake', 'specialization', 'location', 'status'],
            ]);

        $response->assertOk();
        $sheet = $this->excelSheetFromResponse($response);

        $this->assertNotContains('Specialization', $sheet->rangeToArray('A4:H4')[0]);
        $this->assertStringNotContainsString('Specialization:', (string) $sheet->getCell('A2')->getValue());
    }

    public function test_excel_export_downloads_without_leaving_the_page(): void
    {
        $this->makeStudent('199011110000');

        $response = $this->actingAs($this->actor)
            ->post('/students/view/export-excel', [
                'student_id' => '199011110000',
            ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheet',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString(
            '.xlsx',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_pdf_export_includes_the_matching_student(): void
    {
        $this->makeStudent('199022220000');

        $response = $this->actingAs($this->actor)
            ->post('/students/view/export-pdf', [
                'student_id' => '199022220000',
            ]);

        $response->assertOk();
        $this->assertStringContainsString(
            'pdf',
            strtolower((string) $response->headers->get('content-type'))
        );
        $this->assertStringContainsString(
            '.pdf',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertNotSame('', $response->getContent());
    }

    public function test_filter_paginates_results(): void
    {
        foreach (range(0, 11) as $i) {
            $this->makeStudent('1990000000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $page1 = $this->actingAs($this->actor)
            ->postJson($this->route(), ['per_page' => 10, 'page' => 1]);

        $page1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 12)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('from', 1)
            ->assertJsonPath('to', 10);
        $this->assertCount(10, $page1->json('data'));

        $page2 = $this->actingAs($this->actor)
            ->postJson($this->route(), ['per_page' => 10, 'page' => 2]);

        $page2->assertOk()
            ->assertJsonPath('total', 12)
            ->assertJsonPath('from', 11)
            ->assertJsonPath('to', 12);
        $this->assertCount(2, $page2->json('data'));
    }

    public function test_course_intakes_are_limited_to_the_selected_course_location(): void
    {
        $welisaraCourse = Course::forceCreate([
            'course_id'           => 501,
            'course_name'         => 'B.Eng. Electrical',
            'course_type'         => 'degree',
            'duration'            => '3 years',
            'no_of_semesters'     => 6,
            'min_credits'         => 120,
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L',
            'location'            => 'Welisara',
        ]);
        $moratuwaCourse = Course::forceCreate([
            'course_id'           => 502,
            'course_name'         => 'B.Eng. Electrical',
            'course_type'         => 'degree',
            'duration'            => '3 years',
            'no_of_semesters'     => 6,
            'min_credits'         => 120,
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L',
            'location'            => 'Moratuwa',
        ]);

        Intake::forceCreate([
            'intake_id'         => 601,
            'batch'             => '2026-Sep-Welisara',
            'course_id'         => $welisaraCourse->course_id,
            'course_name'       => $welisaraCourse->course_name,
            'batch_size'        => 50,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'location'          => 'Welisara',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
        ]);
        Intake::forceCreate([
            'intake_id'         => 602,
            'batch'             => '2026-Sep-Moratuwa',
            'course_id'         => null,
            'course_name'       => $moratuwaCourse->course_name,
            'batch_size'        => 50,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'location'          => 'Moratuwa',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($this->actor)
            ->get('/students/intakes?course_id=' . $welisaraCourse->course_id);

        $response->assertOk()->assertJsonPath('success', true);
        $batches = collect($response->json('intakes'))->pluck('batch');
        $this->assertContains('2026-Sep-Welisara', $batches);
        $this->assertNotContains('2026-Sep-Moratuwa', $batches);
    }
}
