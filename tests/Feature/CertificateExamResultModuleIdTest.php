<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificateExamResultModuleIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_exam_result_uses_resolved_module_id(): void
    {
        $user = User::forceCreate([
            'name' => 'Program Admin',
            'email' => 'padmin@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Program Administrator (level 01)',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);

        $courseId = DB::table('courses')->insertGetId([
            'course_name' => 'Certificate Course',
            'course_type' => 'certificate',
            'location' => 'Welisara',
            'no_of_semesters' => 1,
            'duration' => '1 year',
            'training_period' => null,
            'min_credits' => 30,
            'entry_qualification' => 'A/L Pass',
            'conducted_by' => 1,
            'course_medium' => 'English',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $intakeId = DB::table('intakes')->insertGetId([
            'location' => 'Welisara',
            'course_id' => $courseId,
            'course_name' => 'Certificate Course',
            'batch' => '2024-01',
            'batch_size' => 20,
            'intake_mode' => 'Physical',
            'intake_type' => 'Fulltime',
            'registration_fee' => '1000',
            'franchise_payment' => '0',
            'course_fee' => '50000',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentId = DB::table('students')->insertGetId([
            'title' => 'Mr',
            'name_with_initials' => 'S. Silva',
            'full_name' => 'Sam Silva',
            'gender' => 'Male',
            'id_type' => 'National id',
            'id_value' => '123456789V',
            'address' => '123 Main St',
            'email' => 'sam@example.com',
            'mobile_phone' => '0771234567',
            'home_phone' => null,
            'whatsapp_phone' => null,
            'birthday' => '2000-01-01',
            'institute_location' => 'Welisara',
            'special_needs' => null,
            'extracurricular_activities' => null,
            'future_potentials' => null,
            'other_document_upload' => null,
            'remarks' => null,
            'status' => 'Unmarried',
            'marketing_survey' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $moduleId = DB::table('modules')->insertGetId([
            'module_name' => 'Certificate Module',
            'module_code' => 'CERT101',
            'module_type' => 'core',
            'module_cordinator' => null,
            'credits' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('intake_modules')->insert([
            'intake_id' => $intakeId,
            'module_id' => $moduleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/store/result', [
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'location' => 'Welisara',
            'course_type' => 'certificate',
            'results' => [
                [
                    'student_id' => $studentId,
                    'marks' => 80,
                    'grade' => 'A',
                    'remarks' => 'Excellent',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('exam_results', [
            'student_id' => $studentId,
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'location' => 'Welisara',
            'module_id' => $moduleId,
        ]);
    }

    public function test_certificate_exam_result_accepts_decimal_marks(): void
    {
        $user = User::forceCreate([
            'name' => 'Program Admin',
            'email' => 'padmin2@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Program Administrator (level 01)',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);

        $courseId = DB::table('courses')->insertGetId([
            'course_name' => 'Certificate Course 2',
            'course_type' => 'certificate',
            'location' => 'Welisara',
            'no_of_semesters' => 1,
            'duration' => '1 year',
            'training_period' => null,
            'min_credits' => 30,
            'entry_qualification' => 'A/L Pass',
            'conducted_by' => 1,
            'course_medium' => 'English',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $intakeId = DB::table('intakes')->insertGetId([
            'location' => 'Welisara',
            'course_id' => $courseId,
            'course_name' => 'Certificate Course 2',
            'batch' => '2024-02',
            'batch_size' => 20,
            'intake_mode' => 'Physical',
            'intake_type' => 'Fulltime',
            'registration_fee' => '1000',
            'franchise_payment' => '0',
            'course_fee' => '50000',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $studentId = DB::table('students')->insertGetId([
            'title' => 'Ms',
            'name_with_initials' => 'N. Perera',
            'full_name' => 'Nimali Perera',
            'gender' => 'Female',
            'id_type' => 'National id',
            'id_value' => '987654321V',
            'address' => '456 Main Rd',
            'email' => 'nimali@example.com',
            'mobile_phone' => '0777654321',
            'home_phone' => null,
            'whatsapp_phone' => null,
            'birthday' => '2001-01-01',
            'institute_location' => 'Welisara',
            'special_needs' => null,
            'extracurricular_activities' => null,
            'future_potentials' => null,
            'other_document_upload' => null,
            'remarks' => null,
            'status' => 'Unmarried',
            'marketing_survey' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $moduleId = DB::table('modules')->insertGetId([
            'module_name' => 'Certificate Module 2',
            'module_code' => 'CERT102',
            'module_type' => 'core',
            'module_cordinator' => null,
            'credits' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('intake_modules')->insert([
            'intake_id' => $intakeId,
            'module_id' => $moduleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/store/result', [
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'location' => 'Welisara',
            'course_type' => 'certificate',
            'results' => [
                [
                    'student_id' => $studentId,
                    'marks' => 87.5,
                    'grade' => 'A',
                    'remarks' => 'Very good',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('exam_results', [
            'student_id' => $studentId,
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'location' => 'Welisara',
            'module_id' => $moduleId,
            'marks' => 87.5,
        ]);
    }
}
