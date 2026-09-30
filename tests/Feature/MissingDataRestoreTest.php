<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Module;
use App\Models\Semester;
use App\Models\SemesterModule;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MissingDataRestoreTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Course $course;
    private Intake $intake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Developer',
            'email'         => 'restore@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Developer',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->course = Course::forceCreate([
            'course_name'         => 'B.Eng. (Hons) Data Science',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
            'specializations'     => ['DS'],
        ]);

        $this->intake = Intake::forceCreate([
            'location'                         => 'Welisara',
            'course_id'                        => $this->course->course_id,
            'course_name'                      => $this->course->course_name,
            'batch'                            => '2024-JUl-B08-DS',
            'batch_size'                       => 40,
            'intake_mode'                      => 'Physical',
            'intake_type'                      => 'Fulltime',
            'registration_fee'                 => '5000',
            'franchise_payment'                => '0',
            'course_fee'                       => '50000',
            'start_date'                       => now()->subMonth()->toDateString(),
            'end_date'                         => now()->addYears(2)->toDateString(),
            'course_registration_id_pattern'   => 'UH-DS-08',
        ]);
    }

    public function test_page_renders_for_developer(): void
    {
        $this->actingAs($this->actor)
            ->get(route('missing.data.restore'))
            ->assertOk()
            ->assertSee('Restore Missing Data')
            ->assertSee('Insert-only upload')
            ->assertSee('Download template');
    }

    public function test_program_admin_is_denied(): void
    {
        $admin = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'pa1-restore@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($admin)
            ->get(route('missing.data.restore'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_librarian_is_denied(): void
    {
        $librarian = User::forceCreate([
            'name'          => 'Librarian',
            'email'         => 'lib-restore@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Librarian',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($librarian)
            ->get(route('missing.data.restore'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_template_download_returns_xlsx(): void
    {
        $this->actingAs($this->actor)
            ->get(route('missing.data.restore.template', ['type' => 'students']))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_preview_and_commit_inserts_new_student_without_updating_existing(): void
    {
        $existing = Student::forceCreate([
            'title'              => 'Miss',
            'name_with_initials' => 'D.T. Jayaweera',
            'full_name'          => 'Dinuli Thinodha Jayaweera',
            'id_type'            => 'Passport',
            'id_value'           => 'N9227247',
            'gender'             => 'Female',
            'email'              => 'dinuli@test.lk',
            'status'             => 'Unmarried',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        $file = $this->spreadsheetFile(
            ['title', 'name_with_initials', 'full_name', 'gender', 'id_type', 'id_value', 'email', 'specialization'],
            [
                ['Mr', 'H.Hanan', 'Hasbullah Hanan', 'Male', 'National id', '200313413524', 'hananhasbullah123@gmail.com', 'DS'],
                ['Miss', 'Changed Name', 'Changed Name Should Skip', 'Female', 'Passport', 'N9227247', 'dinuli@test.lk', 'DS'],
            ]
        );

        $preview = $this->actingAs($this->actor)
            ->post(route('missing.data.restore.preview'), [
                'type' => 'students',
                'intake_id' => $this->intake->intake_id,
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.insert', 2)
            ->assertJsonPath('counts.skip', 0)
            ->json();

        $actions = collect($preview['rows'])->pluck('action')->all();
        $this->assertContains('insert_student', $actions);
        $this->assertContains('insert_registration', $actions);

        $this->actingAs($this->actor)
            ->postJson(route('missing.data.restore.commit'), ['token' => $preview['token']])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('inserted', 2);

        $this->assertDatabaseHas('students', [
            'id_value' => '200313413524',
            'full_name' => 'Hasbullah Hanan',
        ]);
        $this->assertDatabaseHas('students', [
            'student_id' => $existing->student_id,
            'full_name' => 'Dinuli Thinodha Jayaweera',
        ]);
        $this->assertSame(1, CourseRegistration::where('student_id', $existing->student_id)->count());
        $this->assertTrue(
            CourseRegistration::where('intake_id', $this->intake->intake_id)->count() >= 2
        );
        $this->assertDatabaseHas('specialization_registrations', [
            'student_id' => $existing->student_id,
            'intake_id' => $this->intake->intake_id,
            'specialization' => 'DS',
        ]);
    }

    public function test_existing_intake_registration_is_skipped_and_not_updated(): void
    {
        $student = Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'H.Hanan',
            'full_name'          => 'Hasbullah Hanan',
            'id_type'            => 'National id',
            'id_value'           => '200313413524',
            'gender'             => 'Male',
            'email'              => 'hanan@test.lk',
            'status'             => 'Unmarried',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        CourseRegistration::forceCreate([
            'student_id'             => $student->student_id,
            'course_id'              => $this->course->course_id,
            'intake_id'              => $this->intake->intake_id,
            'course_registration_id' => 'UH-DS-08',
            'registration_date'      => now()->toDateString(),
            'registration_fee'       => 5000,
            'status'                 => 'Registered',
            'approval_status'        => 'Approved by manager',
            'location'               => 'Welisara',
            'remarks'                => 'Original',
        ]);

        $file = $this->spreadsheetFile(
            ['full_name', 'id_value', 'gender', 'name_with_initials', 'title'],
            [['Hasbullah Hanan', '200313413524', 'Male', 'H.Hanan', 'Mr']]
        );

        $this->actingAs($this->actor)
            ->post(route('missing.data.restore.preview'), [
                'type' => 'students',
                'intake_id' => $this->intake->intake_id,
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('counts.insert', 0)
            ->assertJsonPath('counts.skip', 1);

        $this->assertSame(1, CourseRegistration::where('student_id', $student->student_id)->count());
        $this->assertDatabaseHas('course_registration', [
            'student_id' => $student->student_id,
            'remarks' => 'Original',
        ]);
    }

    public function test_modules_insert_missing_and_skip_existing_by_uh_suffix(): void
    {
        Module::forceCreate([
            'module_code' => 'BENGEEE_MDCN_6FTC1158',
            'module_name' => 'Mobile & Digital Communication Networks',
            'module_type' => 'core',
            'module_category' => 'degree',
            'credits' => 15,
        ]);

        $file = $this->spreadsheetFile(
            ['module_code', 'module_name', 'module_type', 'module_category', 'credits'],
            [
                ['6FTC1158', 'Duplicate Wireless Name', 'core', 'degree', '15'],
                ['6FTC1162', 'Satellite And Terrestrial Communication Systems', 'core', 'degree', '15'],
            ]
        );

        $preview = $this->actingAs($this->actor)
            ->post(route('missing.data.restore.preview'), [
                'type' => 'modules',
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('counts.insert', 1)
            ->assertJsonPath('counts.skip', 1)
            ->json();

        $this->actingAs($this->actor)
            ->postJson(route('missing.data.restore.commit'), ['token' => $preview['token']])
            ->assertOk()
            ->assertJsonPath('inserted', 1);

        $this->assertDatabaseHas('modules', ['module_code' => '6FTC1162']);
        $this->assertSame(1, Module::where('module_code', 'like', '%6FTC1158')->count());
    }

    public function test_semester_module_links_missing_modules_only(): void
    {
        $semester = Semester::forceCreate([
            'name'       => 'Semester B',
            'course_id'  => $this->course->course_id,
            'intake_id'  => $this->intake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(6)->toDateString(),
            'status'     => 'active',
        ]);

        $existing = Module::forceCreate([
            'module_code' => 'BENGEEE_MDCN_6FTC1158',
            'module_name' => 'Mobile & Digital Communication Networks',
            'module_type' => 'core',
            'credits' => 15,
        ]);
        $new = Module::forceCreate([
            'module_code' => 'BENGEEE_STCS_6FTC1162',
            'module_name' => 'Satellite And Terrestrial Communication Systems',
            'module_type' => 'core',
            'credits' => 15,
        ]);

        SemesterModule::create([
            'semester_id' => $semester->id,
            'module_id' => $existing->module_id,
            'specialization' => 'ECME',
            'specializations' => ['ECME'],
        ]);

        $file = $this->spreadsheetFile(
            ['module_code', 'specialization'],
            [
                ['6FTC1158', 'ECME'],
                ['6FTC1162', 'ECME'],
            ]
        );

        $preview = $this->actingAs($this->actor)
            ->post(route('missing.data.restore.preview'), [
                'type' => 'semester_modules',
                'semester_id' => $semester->id,
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('counts.insert', 1)
            ->assertJsonPath('counts.skip', 1)
            ->json();

        $this->actingAs($this->actor)
            ->postJson(route('missing.data.restore.commit'), ['token' => $preview['token']])
            ->assertOk()
            ->assertJsonPath('inserted', 1);

        $this->assertDatabaseHas('semester_module', [
            'semester_id' => $semester->id,
            'module_id' => $new->module_id,
        ]);
        $this->assertSame(2, SemesterModule::where('semester_id', $semester->id)->count());
    }

    public function test_semesters_insert_missing_and_skip_existing_without_overwriting_dates(): void
    {
        $this->course->semester_format = 'alphabetical';
        $this->course->no_of_semesters = 6;
        $this->course->save();

        $existing = Semester::forceCreate([
            'name'       => 'A',
            'course_id'  => $this->course->course_id,
            'intake_id'  => $this->intake->intake_id,
            'start_date' => '2026-02-02',
            'end_date'   => '2026-05-29',
            'status'     => 'completed',
        ]);

        $file = $this->spreadsheetFile(
            ['intake', 'semester', 'start', 'end'],
            [
                ['2024-JUl-B08-DS', 'A', '2026-07-14', '2026-10-17'],
                ['2024-JUl-B08-DS', 'B', '2025-11-10', '2026-02-20'],
                ['UNKNOWN-BATCH', 'A', '2025-07-21', '2025-10-17'],
            ]
        );

        $preview = $this->actingAs($this->actor)
            ->post(route('missing.data.restore.preview'), [
                'type' => 'semesters',
                'file' => $file,
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('counts.insert', 1)
            ->assertJsonPath('counts.skip', 1)
            ->assertJsonPath('counts.error', 1)
            ->json();

        $this->actingAs($this->actor)
            ->postJson(route('missing.data.restore.commit'), ['token' => $preview['token']])
            ->assertOk()
            ->assertJsonPath('inserted', 1);

        $this->assertTrue(
            Semester::where('intake_id', $this->intake->intake_id)
                ->where('name', 'B')
                ->whereDate('start_date', '2025-11-10')
                ->whereDate('end_date', '2026-02-20')
                ->exists()
        );
        $this->assertTrue(
            Semester::where('id', $existing->id)
                ->where('name', 'A')
                ->whereDate('start_date', '2026-02-02')
                ->whereDate('end_date', '2026-05-29')
                ->exists()
        );
        $this->assertSame(1, Semester::where('intake_id', $this->intake->intake_id)->where('name', 'A')->count());
    }

    private function spreadsheetFile(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray(array_merge([$headers], $rows), null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'restore').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'restore.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
