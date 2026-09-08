<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ClearanceStudentDetailsRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Clearance Developer',
            'email' => 'clearance-routes@example.com',
            'password' => Hash::make('password'),
            'user_role' => 'Developer',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_clearance_student_detail_routes_are_namespaced(): void
    {
        $routes = [
            'library.getStudentDetails' => '/library/get-student-details',
            'hostel.clearance.getStudentDetails' => '/hostel/get-student-details',
            'project.clearance.getStudentDetails' => '/project/get-student-details',
        ];

        foreach ($routes as $name => $uri) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route: {$name}");
            $this->assertSame($uri, '/' . ltrim($route->uri(), '/'));
        }
    }

    public function test_library_student_detail_lookup_keeps_redirect_response(): void
    {
        $response = $this->actingAs($this->actor)->get(route('library.getStudentDetails', [
            'student_id' => 'missing-library-student',
        ]));

        $response->assertRedirect();
    }

    public function test_hostel_student_detail_lookup_returns_hostel_json_schema(): void
    {
        $response = $this->actingAs($this->actor)->getJson(route('hostel.clearance.getStudentDetails', [
            'student_id' => 'missing-hostel-student',
        ]));

        $response->assertOk()->assertJson([
            'success' => false,
            'message' => 'Student not found.',
        ]);
    }

    public function test_project_student_detail_lookup_returns_project_json_schema(): void
    {
        $response = $this->actingAs($this->actor)->getJson(route('project.clearance.getStudentDetails', [
            'student_id' => 'missing-project-student',
        ]));

        $response->assertOk()->assertJson([
            'success' => false,
            'message' => 'Student not found.',
        ]);
    }
}
