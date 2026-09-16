<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepeatStudentsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'repeat-students@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_repeat_students_page_is_mobile_safe(): void
    {
        $this->actingAs($this->actor)
            ->get(route('repeat.students.management'))
            ->assertOk()
            ->assertSee('repeat-students-page', false)
            ->assertSee('col-12 col-md-3', false)
            ->assertSee('exam-results-table-scroll', false)
            ->assertSee('sweetalert2@11.22.0', false);

        $source = file_get_contents(resource_path('views/exam_&_results/repeat_students.blade.php'));

        $this->assertStringContainsString('exam-results-tabs', $source);
        $this->assertStringContainsString('Swal.fire', $source);
        $this->assertStringContainsString('No intakes are included for this course and location.', $source);
        $this->assertStringContainsString('No semesters are included for this course and intake.', $source);
        $this->assertStringNotContainsString('col-sm-2 col-form-label', $source);
        $this->assertStringNotContainsString('payment-tab', $source);
        $this->assertStringNotContainsString('cloneNode', $source);
        $this->assertStringContainsString('Enter NIC or Student ID', $source);
        $this->assertStringContainsString('colSpan = 4', $source);
    }

    public function test_repeat_student_lookup_accepts_student_id(): void
    {
        $student = Student::forceCreate([
            'title'              => 'Ms',
            'name_with_initials' => 'B. Jayawardana',
            'full_name'          => 'Bhagya Jayawardana',
            'id_type'            => 'NIC',
            'id_value'           => '200752903319',
            'gender'             => 'Female',
            'email'              => 'bhagya-repeat@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/repeat-student-by-nic?nic=' . $student->student_id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.full_name', 'Bhagya Jayawardana');

        $this->actingAs($this->actor)
            ->getJson('/api/repeat-student-by-nic?nic=200752903319')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.student_id', $student->student_id);
    }
}
