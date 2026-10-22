<?php

namespace Tests\Feature;

use App\Mail\StudentDocumentSubmissionLinkMail;
use App\Models\Student;
use App\Models\StudentDocumentFile;
use App\Models\StudentDocumentRequest;
use App\Models\User;
use App\Notifications\StudentDocumentsSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentDocumentCollectionTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('student_documents.disk'));

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'docs-admin@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->student = Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'A. Student',
            'full_name'          => 'Alpha Student',
            'id_type'            => 'National id',
            'id_value'           => '200012345678',
            'gender'             => 'Male',
            'email'              => 'alpha.student@nebula.lk',
            'mobile_phone'       => '0712345678',
            'status'             => 'Unmarried',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);
    }

    public function test_authorized_staff_can_open_document_collection_page(): void
    {
        $this->actingAs($this->actor)
            ->get(route('student.documents.index'))
            ->assertOk()
            ->assertSee('Document Collection')
            ->assertSee('Find a student')
            ->assertSee('doc-tabs', false)
            ->assertSee('data-status="pending"', false)
            ->assertSee('loadTab', false)
            ->assertSee('doc-pagination', false)
            ->assertSee('doc-search-row', false)
            ->assertSee('doc-tab', false)
            ->assertSee('doc-hero-art', false)
            ->assertSee('Send email to student');
    }

    public function test_status_tabs_load_by_ajax_without_full_page_markup(): void
    {
        $this->actingAs($this->actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('student.documents.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('doc-list', false)
            ->assertSee('doc-pagination', false)
            ->assertDontSee('Find a student');
    }

    public function test_document_list_paginates_ten_per_page_with_refresh_links(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            StudentDocumentRequest::forceCreate([
                'student_id' => $this->student->student_id,
                'created_by' => $this->actor->user_id,
                'status' => StudentDocumentRequest::STATUS_PENDING,
                'token_hash' => hash('sha256', 'pending-link-' . $i),
                'link_expires_at' => now()->addDays(14),
                'notification_status' => StudentDocumentRequest::NOTIFICATION_PENDING,
                'document_deletion_status' => StudentDocumentRequest::DELETION_RETAINED,
            ]);
        }

        $this->actingAs($this->actor)
            ->get(route('student.documents.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertSee('Showing 1–10 of 11')
            ->assertSee('doc-pagination-nav', false);
    }

    public function test_unauthorized_staff_cannot_open_document_collection_page(): void
    {
        $librarian = User::forceCreate([
            'name'          => 'Librarian',
            'email'         => 'librarian-docs@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Librarian',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($librarian)
            ->get(route('student.documents.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_staff_can_search_students_and_generate_a_unique_link(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('student.documents.search'), ['q' => 'Alpha'])
            ->assertOk()
            ->assertJsonPath('students.0.student_id', $this->student->student_id);

        $response = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $url = $response->json('url');
        $this->assertNotEmpty($url);
        $this->assertMatchesRegularExpression('#/document-submit/[a-f0-9]{64}$#', $url);
        $this->assertNotEmpty($response->json('share.whatsapp'));
        $this->assertDatabaseHas('student_document_requests', [
            'student_id' => $this->student->student_id,
            'created_by' => $this->actor->user_id,
            'status' => StudentDocumentRequest::STATUS_PENDING,
        ]);
    }

    public function test_student_can_submit_documents_through_the_unique_link(): void
    {
        Notification::fake();

        $url = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->json('url');

        $token = basename(parse_url($url, PHP_URL_PATH));

        $this->get(route('student.documents.portal', ['token' => $token]))
            ->assertOk()
            ->assertSee('Submit your documents')
            ->assertSee('National ID', false)
            ->assertSee('value="200012345678"', false)
            ->assertSee('National Identity Card')
            ->assertSee('sweetalert2@11.22.0', false)
            ->assertSee('showNotice', false)
            ->assertDontSee('window.alert', false);

        $this->post(route('student.documents.portal.submit', ['token' => $token]), [
            'full_name' => 'Alpha Student',
            'id_value' => '200012345678',
            'email' => 'alpha.student@nebula.lk',
            'phone' => '0712345678',
            'notes' => 'Clear copies attached',
            'nic_document' => UploadedFile::fake()->image('nic.jpg'),
            'birth_certificate' => UploadedFile::fake()->image('birth.jpg'),
        ])->assertRedirect(route('student.documents.portal', ['token' => $token]));

        $this->get(route('student.documents.portal', ['token' => $token]))
            ->assertOk()
            ->assertSee('Documents submitted');

        $request = StudentDocumentRequest::first();
        $this->assertSame(StudentDocumentRequest::STATUS_SUBMITTED, $request->status);
        $this->assertSame(StudentDocumentRequest::NOTIFICATION_SENT, $request->notification_status);
        $this->assertSame(2, $request->files()->count());
        $this->assertNotNull($request->retention_expires_at);

        Notification::assertSentTo($this->actor, StudentDocumentsSubmittedNotification::class);
    }

    public function test_student_cannot_submit_with_a_mismatched_identification_number(): void
    {
        $url = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->json('url');
        $token = basename(parse_url($url, PHP_URL_PATH));

        $this->from(route('student.documents.portal', ['token' => $token]))
            ->post(route('student.documents.portal.submit', ['token' => $token]), [
                'full_name' => 'Alpha Student',
                'id_value' => '999999999999',
                'email' => 'alpha.student@nebula.lk',
                'phone' => '0712345678',
                'nic_document' => UploadedFile::fake()->image('nic.jpg'),
                'birth_certificate' => UploadedFile::fake()->image('birth.jpg'),
            ])
            ->assertRedirect(route('student.documents.portal', ['token' => $token]));

        $this->assertSame(StudentDocumentRequest::STATUS_PENDING, StudentDocumentRequest::first()->status);
    }

    public function test_invalid_or_guessable_links_do_not_open_a_submission_page(): void
    {
        $this->get(route('student.documents.portal', ['token' => 'abc']))
            ->assertNotFound()
            ->assertSee('This link is not valid');
    }

    public function test_staff_can_review_and_download_submitted_documents_but_other_roles_cannot(): void
    {
        $url = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->json('url');
        $token = basename(parse_url($url, PHP_URL_PATH));

        $this->post(route('student.documents.portal.submit', ['token' => $token]), [
            'full_name' => 'Alpha Student',
            'id_value' => '200012345678',
            'email' => 'alpha.student@nebula.lk',
            'phone' => '0712345678',
            'nic_document' => UploadedFile::fake()->image('nic.jpg'),
            'birth_certificate' => UploadedFile::fake()->image('birth.jpg'),
        ]);

        $request = StudentDocumentRequest::first();
        $file = $request->files()->first();

        $this->actingAs($this->actor)
            ->get(route('student.documents.show', $request))
            ->assertOk()
            ->assertSee('Alpha Student')
            ->assertSee('Mark as reviewed')
            ->assertSee('doc-show-hero', false)
            ->assertSee('doc-info-grid', false)
            ->assertSee('Back to collection')
            ->assertDontSee('btn-sm', false);

        $this->actingAs($this->actor)
            ->get(route('student.documents.file', $file))
            ->assertOk();

        $librarian = User::forceCreate([
            'name'          => 'Librarian Two',
            'email'         => 'librarian-docs-2@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Librarian',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($librarian)
            ->get(route('student.documents.file', $file))
            ->assertRedirect(route('dashboard'));
    }

    public function test_submitted_documents_are_purged_after_seven_days(): void
    {
        $url = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->json('url');
        $token = basename(parse_url($url, PHP_URL_PATH));

        $this->post(route('student.documents.portal.submit', ['token' => $token]), [
            'full_name' => 'Alpha Student',
            'id_value' => '200012345678',
            'email' => 'alpha.student@nebula.lk',
            'phone' => '0712345678',
            'nic_document' => UploadedFile::fake()->image('nic.jpg'),
            'birth_certificate' => UploadedFile::fake()->image('birth.jpg'),
        ]);

        $request = StudentDocumentRequest::first();
        $paths = $request->files()->pluck('stored_path')->all();
        $this->assertNotEmpty($paths);

        $this->travel(8)->days();
        $this->artisan('student-documents:purge')->assertSuccessful();

        $request->refresh();
        $this->assertSame(StudentDocumentRequest::STATUS_PURGED, $request->status);
        $this->assertSame(StudentDocumentRequest::DELETION_PURGED, $request->document_deletion_status);
        $this->assertNull($request->submitted_email);
        $this->assertSame(0, StudentDocumentFile::count());

        foreach ($paths as $path) {
            Storage::disk(config('student_documents.disk'))->assertMissing($path);
        }

        $this->get(route('student.documents.portal', ['token' => $token]))
            ->assertNotFound();
    }

    public function test_clicking_the_same_student_reuses_the_existing_active_link(): void
    {
        $first = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->assertOk()
            ->assertJsonPath('created', true);

        $second = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->assertOk()
            ->assertJsonPath('created', false)
            ->assertJsonPath('reused', true);

        $this->assertSame($first->json('url'), $second->json('url'));
        $this->assertSame(1, StudentDocumentRequest::count());

        $token = basename(parse_url($first->json('url'), PHP_URL_PATH));
        $this->get(route('student.documents.portal', ['token' => $token]))
            ->assertOk()
            ->assertSee('Submit your documents');
    }

    public function test_staff_can_replace_an_active_link_only_when_forced(): void
    {
        $firstUrl = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->json('url');

        $second = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), [
                'student_id' => $this->student->student_id,
                'force_new' => true,
            ])
            ->assertOk()
            ->assertJsonPath('created', true)
            ->assertJsonPath('replaced', true);

        $this->assertNotSame($firstUrl, $second->json('url'));
        $this->assertSame(1, StudentDocumentRequest::count());

        $oldToken = basename(parse_url($firstUrl, PHP_URL_PATH));
        $newToken = basename(parse_url($second->json('url'), PHP_URL_PATH));

        $this->get(route('student.documents.portal', ['token' => $oldToken]))
            ->assertNotFound();
        $this->get(route('student.documents.portal', ['token' => $newToken]))
            ->assertOk()
            ->assertSee('Submit your documents');
    }

    public function test_share_by_email_sends_the_submission_link_to_the_student(): void
    {
        Mail::fake();

        $url = $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id])
            ->json('url');

        $request = StudentDocumentRequest::first();

        $this->actingAs($this->actor)
            ->postJson(route('student.documents.email', $request))
            ->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(StudentDocumentSubmissionLinkMail::class, function (StudentDocumentSubmissionLinkMail $mail) use ($url) {
            return $mail->hasTo($this->student->email) && $mail->submissionUrl === $url;
        });
    }

    public function test_share_by_email_requires_a_student_email_address(): void
    {
        Mail::fake();

        $this->student->email = null;
        $this->student->save();

        $this->actingAs($this->actor)
            ->postJson(route('student.documents.generate'), ['student_id' => $this->student->student_id]);

        $request = StudentDocumentRequest::first();

        $this->actingAs($this->actor)
            ->postJson(route('student.documents.email', $request))
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        Mail::assertNothingSent();
    }
}
