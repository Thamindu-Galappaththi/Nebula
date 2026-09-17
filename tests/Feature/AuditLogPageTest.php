<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeDeveloper(): User
    {
        return User::forceCreate([
            'name'          => 'Nebula Developer',
            'email'         => 'developer-audit@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Developer',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    private function makeProgramAdmin(): User
    {
        return User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'pa-audit@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Moratuwa',
        ]);
    }

    public function test_developer_can_open_audit_log_and_sees_sidebar_link(): void
    {
        $developer = $this->makeDeveloper();

        $html = $this->actingAs($developer)
            ->get(route('audit.log'))
            ->assertOk()
            ->assertSee('Audit Log')
            ->assertSee('Asia/Colombo')
            ->assertSee('audit-log-page', false)
            ->assertSee('nav-small-cap-text">AUDIT', false)
            ->getContent();

        $this->assertStringContainsString('Times are Asia/Colombo', $html);
        $this->assertStringContainsString('@media (max-width: 1199.98px)', $html);
        $this->assertStringContainsString('word-break: normal', $html);
        $this->assertStringNotContainsString('word-break: break-all', $html);
        $this->assertStringContainsString('id="auditPagination"', $html);
        $this->assertStringContainsString('id="audit-table-body"', $html);
        $this->assertStringContainsString('id="bulkDeleteAuditBtn"', $html);
        $this->assertStringContainsString('delete-audit-btn', $html);
        $this->assertStringContainsString('sweetalert2@11.22.0', $html);
        $this->assertStringContainsString('$.ajax', $html);
        $this->assertStringContainsString('X-Requested-With', $html);
        $this->assertStringNotContainsString('window.location.assign', $html);
    }

    public function test_non_developer_cannot_open_audit_log(): void
    {
        $admin = $this->makeProgramAdmin();

        $this->actingAs($admin)
            ->get(route('audit.log'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_staff_create_update_and_delete_are_summarized_with_request_context(): void
    {
        $admin = $this->makeProgramAdmin();
        $this->actingAs($admin);

        $course = Course::forceCreate([
            'course_name'         => 'Audit Course',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '3-0-0',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 'Pearson',
            'semester_format'     => 'numerical',
        ]);

        $intake = Intake::forceCreate([
            'location'                       => 'Welisara',
            'course_id'                      => $course->course_id,
            'course_name'                    => $course->course_name,
            'batch'                          => 'AUDIT-BATCH-01',
            'batch_size'                     => 30,
            'intake_mode'                    => 'Physical',
            'intake_type'                    => 'Fulltime',
            'registration_fee'               => 5000,
            'franchise_payment'              => 10000,
            'franchise_payment_currency'     => 'LKR',
            'course_fee'                     => 250000,
            'sscl_tax'                       => 15,
            'bank_charges'                   => 500,
            'start_date'                     => '2026-01-01',
            'end_date'                       => '2026-12-31',
            'enrollment_end_date'            => '2025-12-15',
            'course_registration_id_pattern' => 'REG-AUDIT-001',
        ]);

        $intake->update(['batch' => 'AUDIT-BATCH-02']);
        $intake->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'item_label' => 'Intake AUDIT-BATCH-01',
            'user_name' => 'Program Admin',
            'user_role' => 'Program Administrator (level 01)',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'item_label' => 'Intake AUDIT-BATCH-02',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'item_label' => 'Intake AUDIT-BATCH-02',
        ]);

        $created = AuditLog::query()->where('item_label', 'Intake AUDIT-BATCH-01')->where('action', 'created')->first();
        $this->assertNotNull($created);
        $this->assertStringContainsString('Program Admin', $created->summary);
        $this->assertStringContainsString('created', $created->summary);
        $this->assertStringContainsString('Moratuwa', (string) $created->location);
        $this->assertNotEmpty($created->occurredAtSriLanka()->format('d M Y, h:i A'));

        $html = $this->actingAs($this->makeDeveloper())
            ->get(route('audit.log'))
            ->assertOk()
            ->assertSee('Program Admin')
            ->assertSee('AUDIT-BATCH-01')
            ->assertSee('created Intake', false)
            ->getContent();

        $this->assertStringContainsString('Asia/Colombo', $html);
    }

    public function test_audit_log_filter_keeps_matching_staff_actions(): void
    {
        $admin = $this->makeProgramAdmin();
        $this->actingAs($admin);

        AuditLog::create([
            'user_id' => $admin->user_id,
            'user_name' => $admin->name,
            'user_role' => $admin->user_role,
            'action' => 'created',
            'summary' => 'Program Admin created Intake KEEP-ME.',
            'item_label' => 'Intake KEEP-ME',
            'path' => '/intake-creation',
            'ip_address' => '10.0.0.8',
            'location' => 'Moratuwa',
        ]);
        AuditLog::create([
            'user_id' => $admin->user_id,
            'user_name' => $admin->name,
            'user_role' => $admin->user_role,
            'action' => 'deleted',
            'summary' => 'Program Admin deleted Intake HIDE-ME.',
            'item_label' => 'Intake HIDE-ME',
            'path' => '/intake-creation',
            'ip_address' => '10.0.0.8',
            'location' => 'Moratuwa',
        ]);

        $ajax = $this->actingAs($this->makeDeveloper())
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('audit.log', ['action' => 'created', 'search' => 'KEEP-ME']))
            ->assertOk()
            ->assertJsonStructure(['html', 'pagination']);

        $this->assertStringContainsString('KEEP-ME', $ajax->json('html'));
        $this->assertStringNotContainsString('HIDE-ME', $ajax->json('html'));
    }

    public function test_audit_log_paginates_ten_per_page_and_reloads_on_page_two(): void
    {
        $admin = $this->makeProgramAdmin();
        $this->actingAs($admin);

        for ($i = 1; $i <= 11; $i++) {
            AuditLog::create([
                'user_id' => $admin->user_id,
                'user_name' => $admin->name,
                'user_role' => $admin->user_role,
                'action' => 'created',
                'summary' => sprintf('Program Admin created Intake PAGE-ITEM-%02d.', $i),
                'item_label' => sprintf('Intake PAGE-ITEM-%02d', $i),
                'path' => '/intake-creation',
                'ip_address' => '10.0.0.8',
                'location' => 'Moratuwa',
            ]);
        }

        $developer = $this->makeDeveloper();

        $pageOne = $this->actingAs($developer)
            ->get(route('audit.log'))
            ->assertOk()
            ->assertSee('PAGE-ITEM-11')
            ->assertDontSee('PAGE-ITEM-01')
            ->assertSee('Showing 1–10 of', false)
            ->getContent();

        $this->assertStringContainsString('page-link', $pageOne);
        $this->assertStringContainsString('page=2', $pageOne);
        $this->assertStringContainsString('$.ajax', $pageOne);
        $this->assertStringContainsString('X-Requested-With', $pageOne);
        $this->assertStringNotContainsString('window.location.assign', $pageOne);

        $pageTwo = $this->actingAs($developer)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('audit.log', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html', 'pagination']);

        $this->assertStringContainsString('PAGE-ITEM-01', $pageTwo->json('html'));
        $this->assertStringNotContainsString('PAGE-ITEM-11', $pageTwo->json('html'));
        $this->assertStringContainsString('Showing 11–', $pageTwo->json('pagination'));
    }

    public function test_lookup_ajax_posts_are_not_stored_as_submitted_duplicates(): void
    {
        $developer = $this->makeDeveloper();
        $this->actingAs($developer);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('payment.discount.get.discounts.by.category'), [
                'category' => 'local_course_fee',
                'page' => 1,
                'per_page' => 10,
            ])->assertOk();
        }

        $this->assertSame(0, AuditLog::query()->where('action', 'submitted')->count());
        $this->assertSame(0, AuditLog::query()->where('path', '/payment-discount/get-discounts-by-category')->count());
    }

    public function test_saving_a_discount_stores_one_created_log_not_a_submitted_copy(): void
    {
        $developer = $this->makeDeveloper();
        $this->actingAs($developer);

        $this->postJson(route('payment.discount.save.discount'), [
            'name' => 'Audit Unique Discount',
            'type' => 'amount',
            'discount_category' => 'local_course_fee',
            'value' => 1500,
        ])->assertOk()->assertJsonPath('success', true);

        $created = AuditLog::query()
            ->where('action', 'created')
            ->where('item_label', 'like', '%Audit Unique Discount%')
            ->get();

        $this->assertCount(1, $created);
        $this->assertSame(0, AuditLog::query()
            ->where('action', 'submitted')
            ->where('path', '/payment-discount/save-discount')
            ->count());
    }

    public function test_developer_can_delete_and_bulk_delete_audit_entries(): void
    {
        $developer = $this->makeDeveloper();
        $this->actingAs($developer);

        $keep = AuditLog::create([
            'user_name' => $developer->name,
            'user_role' => $developer->user_role,
            'action' => 'created',
            'summary' => 'Keep this audit row.',
            'item_label' => 'Keep Item',
        ]);
        $remove = AuditLog::create([
            'user_name' => $developer->name,
            'user_role' => $developer->user_role,
            'action' => 'updated',
            'summary' => 'Remove this audit row.',
            'item_label' => 'Remove Item',
        ]);
        $bulkOne = AuditLog::create([
            'user_name' => $developer->name,
            'user_role' => $developer->user_role,
            'action' => 'deleted',
            'summary' => 'Bulk one.',
            'item_label' => 'Bulk One',
        ]);
        $bulkTwo = AuditLog::create([
            'user_name' => $developer->name,
            'user_role' => $developer->user_role,
            'action' => 'login',
            'summary' => 'Bulk two.',
            'item_label' => 'Bulk Two',
        ]);

        $this->deleteJson(route('audit.destroy', $remove->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('audit_logs', ['id' => $remove->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $keep->id]);

        $this->postJson(route('audit.bulkDestroy'), [
            'ids' => [$bulkOne->id, $bulkTwo->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseMissing('audit_logs', ['id' => $bulkOne->id]);
        $this->assertDatabaseMissing('audit_logs', ['id' => $bulkTwo->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $keep->id]);
    }

    public function test_non_developer_cannot_delete_audit_entries(): void
    {
        $admin = $this->makeProgramAdmin();
        $this->actingAs($admin);

        $log = AuditLog::create([
            'user_name' => $admin->name,
            'user_role' => $admin->user_role,
            'action' => 'created',
            'summary' => 'Admin created something.',
            'item_label' => 'Admin Item',
        ]);

        $this->deleteJson(route('audit.destroy', $log->id))
            ->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_audit_logging_can_be_paused_with_env_flag(): void
    {
        config(['audit.enabled' => false]);

        $admin = $this->makeProgramAdmin();
        $this->actingAs($admin);

        Course::forceCreate([
            'course_name'         => 'Paused Audit Course',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '3-0-0',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 'Pearson',
            'semester_format'     => 'numerical',
        ]);

        $this->assertDatabaseCount('audit_logs', 0);

        $this->actingAs($this->makeDeveloper())
            ->get(route('audit.log'))
            ->assertOk()
            ->assertSee('Recording is paused')
            ->assertSee('AUDIT_LOGGING=true');
    }
}
