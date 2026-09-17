<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class SidebarMenuTest extends TestCase
{
    public function test_developer_sees_uh_index()
    {
        $user = new User([/* minimal attributes */]);
        $user->id = 1;
        $user->name = 'Dev User';
        $user->email = 'dev@example.com';
        $user->user_role = 'Developer';
        $user->role = 'Developer';

        $this->be($user);

        $html = (string) view('components.sidebar-menu')->render();
        $this->assertStringContainsString('UH Index Numbers', $html);
        $this->assertStringContainsString('Audit Log', $html);
    }

    public function test_program_admin_sees_create_user()
    {
        $user = new User();
        $user->id = 2;
        $user->name = 'Admin1';
        $user->email = 'admin1@example.com';
        $user->user_role = 'Program Administrator (level 01)';

        $this->be($user);

        $html = (string) view('components.sidebar-menu')->render();
        $this->assertStringContainsString('Create User', $html);
        $this->assertStringNotContainsString('Audit Log', $html);
    }
}
