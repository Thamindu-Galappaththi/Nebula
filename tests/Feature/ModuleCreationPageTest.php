<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModuleCreationPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'module-create@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_is_responsive_and_uses_sweetalert_edit_modal_and_ten_per_page(): void
    {
        Module::forceCreate([
            'module_name'     => 'Intro Programming',
            'module_code'     => 'CS101_INTRO_001',
            'module_category' => 'degree',
            'module_type'     => 'core',
            'credits'         => 15,
        ]);

        $html = $this->actingAs($this->actor)
            ->get(route('module.creation'))
            ->assertOk()
            ->assertSee('Create New Module')
            ->assertSee('Existing Modules')
            ->assertSee('Intro Programming')
            ->assertSee('module-creation-page', false)
            ->assertSee('editModuleModal', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('sweetalert2@11.22.0', false)
            ->assertSee('Swal.fire', false)
            ->assertSee('10 per page')
            ->assertSee('@media (max-width: 991.98px)', false)
            ->assertSee('data-label="Module Name"', false)
            ->assertSee('resetFilterSelect', false)
            ->getContent();

        $this->assertStringContainsString('value="10"', $html);
        $this->assertStringNotContainsString('Show All', $html);
        $this->assertDoesNotMatchRegularExpression('/id="degreeCancelEditBtn"/', $html);
    }

    public function test_modules_are_paginated_ten_per_page_without_full_html_on_ajax(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            Module::forceCreate([
                'module_name'     => sprintf('Alpha Module %02d', $i),
                'module_code'     => sprintf('CS101_UNIT_%03d', $i),
                'module_category' => 'degree',
                'module_type'     => 'core',
                'credits'         => 15,
            ]);
        }

        $this->actingAs($this->actor)
            ->get(route('module.creation'))
            ->assertOk()
            ->assertSee('Alpha Module 01')
            ->assertSee('Alpha Module 10')
            ->assertDontSee('Alpha Module 11')
            ->assertSee('Showing 1–10 of 11');

        $pageTwo = $this->actingAs($this->actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('module.creation', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html', 'pagination']);

        $this->assertStringContainsString('Alpha Module 11', $pageTwo->json('html'));
        $this->assertStringNotContainsString('Alpha Module 01', $pageTwo->json('html'));
        $this->assertStringContainsString('Showing 11–11 of 11', $pageTwo->json('pagination'));
    }

    public function test_module_can_be_updated(): void
    {
        $module = Module::forceCreate([
            'module_name'     => 'Old Module',
            'module_code'     => 'CS101_OLD_001',
            'module_category' => 'degree',
            'module_type'     => 'core',
            'credits'         => 10,
        ]);

        $this->actingAs($this->actor)
            ->patch(route('module.update', $module->module_id), [
                'module_name'     => 'Updated Module',
                'module_code'     => 'CS101_NEW_002',
                'module_category' => 'degree',
                'module_type'     => 'elective',
                'credits'         => 20,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('modules', [
            'module_id'   => $module->module_id,
            'module_name' => 'Updated Module',
            'module_code' => 'CS101_NEW_002',
            'module_type' => 'elective',
            'credits'     => 20,
        ]);
    }
}
