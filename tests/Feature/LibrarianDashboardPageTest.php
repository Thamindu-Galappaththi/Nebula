<?php

namespace Tests\Feature;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LibrarianDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    private User $librarian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->librarian = User::forceCreate([
            'name'          => 'Librarian',
            'email'         => 'librarian-dashboard@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Librarian',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_pending_status_is_not_the_same_blue_as_review(): void
    {
        $this->actingAs($this->librarian)
            ->get(route('librarian.dashboard'))
            ->assertOk()
            ->assertSee('.badge-pending { background: #f59e0b; color: #1f2937; }', false)
            ->assertDontSee('.badge-pending { background: #0d6efd; color: white; }', false)
            ->assertSee('badge bg-warning text-dark', false)
            ->assertSee('id="librarianPendingList"', false)
            ->assertSee('loadPendingPage', false)
            ->assertSee('librarian-pending-page', false);
    }

    public function test_sidebar_and_student_list_are_available(): void
    {
        $this->actingAs($this->librarian)
            ->get(route('librarian.dashboard'))
            ->assertOk()
            ->assertSee('STUDENT MANAGEMENT')
            ->assertSee('Student Lists')
            ->assertSee(route('student_management.list', [], false), false);

        $this->actingAs($this->librarian)
            ->get(route('student_management.list'))
            ->assertOk()
            ->assertSee('Student Lists');
    }

    public function test_pending_pagination_uses_ajax_partial_without_full_page(): void
    {
        $this->seedPendingRequests(11);

        $this->actingAs($this->librarian)
            ->get(route('librarian.dashboard'))
            ->assertOk()
            ->assertSee('LIBRARY PENDING 01')
            ->assertSee('LIBRARY PENDING 10')
            ->assertDontSee('LIBRARY PENDING 11')
            ->assertSee('Showing 1 to 10 of 11 pending requests');

        $pageTwo = $this->actingAs($this->librarian)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('librarian.dashboard', ['pending_page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html']);

        $html = $pageTwo->json('html');
        $this->assertStringContainsString('LIBRARY PENDING 11', $html);
        $this->assertStringNotContainsString('LIBRARY PENDING 01', $html);
        $this->assertStringContainsString('Showing 11 to 11 of 11 pending requests', $html);
        $this->assertStringNotContainsString('Librarian Dashboard', $html);
        $this->assertStringNotContainsString('Recent Library Clearance Updates', $html);
    }

    private function seedPendingRequests(int $count): void
    {
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
            'batch'             => '2024-JUL-B08',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);

        for ($i = 1; $i <= $count; $i++) {
            $student = Student::forceCreate([
                'title'              => 'Mr',
                'name_with_initials' => sprintf('Library Pending %02d', $i),
                'full_name'          => sprintf('Library Pending %02d Full', $i),
                'id_type'            => 'NIC',
                'id_value'           => sprintf('1991%06dV', $i),
                'gender'             => 'Male',
                'email'              => sprintf('library-pending-%02d@test.lk', $i),
                'status'             => 'Registered',
                'academic_status'    => Student::ACADEMIC_ACTIVE,
                'institute_location' => 'Welisara',
            ]);

            ClearanceRequest::forceCreate([
                'clearance_type' => ClearanceRequest::TYPE_LIBRARY,
                'location'       => 'Welisara',
                'course_id'      => $course->course_id,
                'intake_id'      => $intake->intake_id,
                'student_id'     => $student->student_id,
                'status'         => ClearanceRequest::STATUS_PENDING,
                'requested_at'   => now()->subDays($count - $i),
            ]);
        }
    }
}
