<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentOtherInformation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentOtherInformationPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Counselor',
            'email'         => 'other-info@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Student Counselor',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_search_controls_stack_on_mobile(): void
    {
        $this->actingAs($this->actor)
            ->get(route('student_management.other.information'))
            ->assertOk()
            ->assertSee('student-other-info-page', false)
            ->assertSee('student-other-search-controls', false)
            ->assertSee('fillOtherInformationForm', false)
            ->assertSee('Enter Student ID (NIC)')
            ->assertSee('col-12 col-md-3', false)
            ->assertDontSee('col-sm-8', false)
            ->assertDontSee('col-sm-2', false);
    }

    public function test_other_information_saves_by_student_id(): void
    {
        $student = $this->makeStudent();

        $response = $this->actingAs($this->actor)
            ->post(route('student_management.store.other.informations'), [
                'studentName'        => 'A completely different name',
                'studentID'          => (string) $student->student_id,
                'disciplinaryIssues' => 'Late to class',
                'continueStudies'    => 'true',
                'institute'          => 'University of Colombo',
                'fieldOfStudy'       => 'Computer Science',
                'currentlyEmployee'  => 'false',
                'otherInformation'   => 'Needs follow up',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('other_information', [
            'student_id'              => $student->student_id,
            'disciplinary_issues'     => 'Late to class',
            'continue_higher_studies' => 1,
            'institute'               => 'University of Colombo',
            'field_of_study'          => 'Computer Science',
            'currently_employee'      => 0,
            'other_information'       => 'Needs follow up',
        ]);
    }

    public function test_search_returns_saved_other_information(): void
    {
        $student = $this->makeStudent();
        StudentOtherInformation::forceCreate([
            'student_id'              => $student->student_id,
            'disciplinary_issues'     => 'Warning issued',
            'continue_higher_studies' => true,
            'institute'               => 'UoM',
            'field_of_study'          => 'Engineering',
            'currently_employee'      => true,
            'job_title'               => 'Technician',
            'workplace'               => 'SLT',
            'other_information'       => 'Call parent',
        ]);

        $this->actingAs($this->actor)
            ->post(route('student_management.retrieve.details'), [
                'identificationType' => 'nic',
                'idValue'            => $student->id_value,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.other_information.institute', 'UoM')
            ->assertJsonPath('data.other_information.job_title', 'Technician')
            ->assertJsonPath('data.other_information.other_information', 'Call parent');
    }

    private function makeStudent(): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'A. Perera',
            'full_name'          => 'Amal Perera',
            'id_type'            => 'NIC',
            'id_value'           => '199512345678',
            'gender'             => 'Male',
            'email'              => 'amal-other@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);
    }
}
