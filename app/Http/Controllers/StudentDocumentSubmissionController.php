<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitStudentDocumentsRequest;
use App\Services\StudentDocumentCollectionService;
use RuntimeException;

class StudentDocumentSubmissionController extends Controller
{
    public function __construct(private StudentDocumentCollectionService $documents)
    {
    }

    public function show(string $token)
    {
        $request = $this->documents->findByToken($token);

        if (!$request) {
            return response()->view('student_documents.portal', [
                'state' => 'invalid',
                'token' => $token,
                'documentRequest' => null,
            ], 404);
        }

        if ($request->status === $request::STATUS_SUBMITTED) {
            return view('student_documents.portal', [
                'state' => 'submitted',
                'token' => $token,
                'documentRequest' => $request,
            ]);
        }

        if (!$request->isAccessibleToStudent()) {
            return view('student_documents.portal', [
                'state' => 'expired',
                'token' => $token,
                'documentRequest' => $request,
            ]);
        }

        return view('student_documents.portal', [
            'state' => 'form',
            'token' => $token,
            'documentRequest' => $request,
            'student' => $request->student,
            'maxFileKb' => (int) config('student_documents.max_file_kb', 5120),
            'allowedMimes' => config('student_documents.allowed_mimes', ['pdf', 'jpg', 'jpeg', 'png']),
            'maxOtherFiles' => (int) config('student_documents.max_other_files', 3),
        ]);
    }

    public function store(SubmitStudentDocumentsRequest $httpRequest, string $token)
    {
        $request = $this->documents->findByToken($token);

        if (!$request || !$request->isAccessibleToStudent()) {
            return redirect()
                ->route('student.documents.portal', ['token' => $token])
                ->with('error', 'This submission link is no longer available.');
        }

        $files = [
            'nic' => $httpRequest->file('nic_document'),
            'birth_certificate' => $httpRequest->file('birth_certificate'),
        ];

        if ($httpRequest->hasFile('other_documents')) {
            $files['other'] = $httpRequest->file('other_documents');
        }

        try {
            $submitted = $this->documents->submit($request, $httpRequest->only([
                'full_name',
                'id_value',
                'email',
                'phone',
                'notes',
            ]), $files);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('student.documents.portal', ['token' => $token])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $this->documents->notifyStaff($submitted);

        return redirect()
            ->route('student.documents.portal', ['token' => $token])
            ->with('success', 'Your documents were submitted successfully.');
    }
}
