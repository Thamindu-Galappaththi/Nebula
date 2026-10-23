<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentRegistrationCaitBtecQualificationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Student Counselor',
            'email'         => 'counselor-cait@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Student Counselor',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_registration_page_shows_cait_and_btec_qualification_fields(): void
    {
        $this->actingAs($this->actor)
            ->get(route('student_management.registration'))
            ->assertOk()
            ->assertSee('Institute')
            ->assertSee('Select an Institute Location')
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('Foundation program (CAIT)')
            ->assertSee('BTEC Level 3')
            ->assertSee('BTEC Course')
            ->assertSee('name="foundationComplete"', false)
            ->assertSee('name="btecCompleted"', false)
            ->assertSee('Please fill out this field.');
    }

    public function test_registration_requires_cait_and_btec_qualification_fields(): void
    {
        $payload = $this->validPayload();
        unset($payload['institute_location'], $payload['foundationComplete'], $payload['btecCompleted']);

        $this->actingAs($this->actor)
            ->postJson(route('student_management.register'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.institute_location.0', 'Please select an institute location.')
            ->assertJsonPath('errors.foundationComplete.0', 'Please fill out this field.')
            ->assertJsonPath('errors.btecCompleted.0', 'Please fill out this field.');
    }

    public function test_registration_saves_cait_and_btec_qualification_values(): void
    {
        $payload = $this->validPayload([
            'foundationComplete' => '1',
            'btecCompleted'      => '1',
            'btec_course'        => '2',
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('student_management.register'), $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Student registered successfully!');

        $student = Student::where('id_value', $payload['idValue'])->first();
        $this->assertNotNull($student);
        $this->assertTrue($student->foundation_program);
        $this->assertTrue($student->btec_completed);
        $this->assertSame('Welisara', $student->institute_location);
        $this->assertSame('BTEC Level 3 Extended Diploma in Computing', $student->btec_course);
    }

    public function test_registration_saves_not_completed_qualification_values(): void
    {
        $payload = $this->validPayload([
            'idValue'            => '200012345679',
            'email'              => 'jane.cait@nebula.lk',
            'foundationComplete' => '0',
            'btecCompleted'      => '0',
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('student_management.register'), $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        $student = Student::where('id_value', $payload['idValue'])->first();
        $this->assertNotNull($student);
        $this->assertFalse($student->foundation_program);
        $this->assertFalse($student->btec_completed);
        $this->assertNull($student->btec_course);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title'              => 'Mr',
            'nameWithInitials'   => 'J. A. Smith',
            'fullName'           => 'John Adam Smith',
            'gender'             => 'Male',
            'identificationType' => 'National id',
            'idValue'            => '200012345678',
            'address'            => '123 Main Street, Colombo',
            'district'           => 'Colombo',
            'email'              => 'john.cait@nebula.lk',
            'mobilePhone'        => '0771234567',
            'whatsappPhone'      => '0771234567',
            'birthday'           => '2000-01-15',
            'institute_location' => 'Welisara',
            'foundationComplete' => '0',
            'btecCompleted'      => '0',
            'pending_result'     => 'yes',
            'parentName'         => 'Jane Smith',
            'parentContactNo'    => '0771234568',
            'parentAddress'      => '123 Main Street, Colombo',
            'emergencyContactNo' => '0771234569',
        ], $overrides);
    }
}
