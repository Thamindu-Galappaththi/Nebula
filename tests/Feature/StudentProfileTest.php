<?php

namespace Tests\Feature;

use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentProfileTest extends TestCase
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
            'birthday'           => '2000-01-15',
        ], $studentAttrs));
    }

    public function test_blank_profile_page_renders_without_student(): void
    {
        $this->actingAs($this->actor)
            ->get('/student/profile/0')
            ->assertOk()
            ->assertSee('Student Profile')
            ->assertSee('Enter NIC number')
            ->assertDontSee('Trying to get property');
    }

    public function test_profile_page_renders_student_with_array_exam_subjects(): void
    {
        $student = $this->makeStudent('199012345V');
        StudentExam::forceCreate([
            'student_id'       => $student->student_id,
            'ol_exam_type'     => 'Local',
            'ol_exam_year'     => '2016',
            'ol_exam_subjects' => [
                ['subject' => 'Maths', 'result' => 'A'],
            ],
        ]);

        $this->actingAs($this->actor)
            ->get('/student/profile/' . $student->student_id)
            ->assertOk()
            ->assertSee('Test Student Full')
            ->assertSee('Maths')
            ->assertSee('2000-01-15');
    }

    public function test_profile_search_matches_nic_only(): void
    {
        $student = $this->makeStudent('199088877V');
        ParentGuardian::forceCreate([
            'student_id'               => $student->student_id,
            'guardian_name'            => 'Parent Name',
            'guardian_profession'      => 'Teacher',
            'guardian_contact_number'  => '0771234567',
            'guardian_email'           => 'parent@test.lk',
            'guardian_address'         => 'Colombo',
            'emergency_contact_number' => '0777654321',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student-details-by-nic?nic=' . $student->student_id)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No student profile found for this NIC.');

        $this->actingAs($this->actor)
            ->getJson('/api/student-details-by-nic?nic=199088877V')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.student_id', $student->student_id)
            ->assertJsonPath('student.parent.guardian_name', 'Parent Name')
            ->assertJsonPath('student.birthday', '2000-01-15');
    }

    public function test_unknown_nic_returns_not_found_message(): void
    {
        $this->actingAs($this->actor)
            ->getJson('/api/student-details-by-nic?nic=999999999V')
            ->assertNotFound()
            ->assertJsonPath('message', 'No student profile found for this NIC.');
    }
}
