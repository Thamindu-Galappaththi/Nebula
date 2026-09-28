<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProgramAdminL2DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::forceCreate([
            'name'          => 'Program Admin L2',
            'email'         => 'pa2-dashboard@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 02)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_batch_student_chart_uses_mobile_horizontal_layout(): void
    {
        $this->actingAs($this->admin)
            ->get(route('program.admin.l2.dashboard'))
            ->assertOk()
            ->assertSee('Student Count by Batch')
            ->assertSee('id="batchStudentChart"', false)
            ->assertSee('batch-student-chart-container', false)
            ->assertSee('isNarrowDashboard', false)
            ->assertSee("chartType === 'horizontalBar' || narrow", false)
            ->assertSee('sizeBatchChartContainer', false)
            ->assertSee('indexAxis: useHorizontal ? \'y\' : \'x\'', false)
            ->assertSee('.batch-student-chart-container {', false);
    }
}
