<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\StudentOtherInformation;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentOtherInformationController extends Controller
{
    // View
    public function showStudentOtherInformation()
    {
        return view('student_management.student_other_information');
    }

    // Search by NIC or reg no (unchanged)
    public function getStudentDetails(Request $request)
    {
        try {
            $identificationType = $request->input('identificationType');
            $idValue = $request->input('idValue');

            if ($identificationType === 'nic') {
                $student = Student::query()
                    ->where(function ($query) use ($idValue) {
                        $query->where('id_value', $idValue);
                        if (ctype_digit((string) $idValue)) {
                            $query->orWhere('student_id', $idValue);
                        }
                    })
                    ->first();
            } elseif ($identificationType === 'registration_number') {
                $student = Student::join('course_registration', 'students.student_id', '=', 'course_registration.student_id')
                    ->where('course_registration.id', $idValue)
                    ->select('students.*')
                    ->first();
            } else {
                return response()->json(['success' => false, 'message' => 'Invalid identification type']);
            }

            if ($student) {
                $other = $student->otherInformation;

                return response()->json([
                    'success' => true,
                    'message' => 'Student found',
                    'data' => [
                        'student_id'       => $student->student_id,
                        'student_name'     => $student->full_name,
                        'academic_status'  => $student->academic_status,
                        'profile_url'      => route('student_management.profile', ['studentId' => $student->student_id]),
                        'other_information' => $other ? [
                            'disciplinary_issues'         => $other->disciplinary_issues,
                            'has_disciplinary_document'   => !empty($other->disciplinary_issue_document),
                            'continue_higher_studies'     => (bool) $other->continue_higher_studies,
                            'institute'                   => $other->institute,
                            'field_of_study'              => $other->field_of_study,
                            'currently_employee'          => (bool) $other->currently_employee,
                            'job_title'                   => $other->job_title,
                            'workplace'                   => $other->workplace,
                            'other_information'           => $other->other_information,
                        ] : null,
                    ],
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Student not found']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

    public function reinstateStudent(Request $request)
    {
        try {
            $request->validate([
                'studentID' => 'required|string',
                'reason'    => 'required|string',
                'document'  => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png',
            ]);

            $student = Student::where('student_id', $request->input('studentID'))->first();
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found'], 404);
            }

            $docPath = null;
            if ($request->hasFile('document')) {
                $docPath = $request->file('document')->store('public/academic_status_docs');
            }

            $student->academic_status            = 'active';
            $student->academic_status_reason     = 'Reinstated: ' . $request->input('reason');
            if ($docPath) {
                $student->academic_status_document = $docPath;
            }
            $student->academic_status_changed_at = now();
            $student->save();

            return response()->json([
                'success'          => true,
                'message'          => 'Student reinstated successfully',
                'academic_status'  => $student->academic_status,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }




    // Save other info + (optionally) terminate
    public function storeOtherInformations(Request $request)
    {
        try {
            $request->validate([
                'studentName'         => 'required|string',
                'studentID'           => 'required',
                'disciplinaryIssues'  => 'nullable|string',
                'continueStudies'     => 'required|in:true,false',
                'institute'           => 'nullable|string',
                'fieldOfStudy'        => 'nullable|string',
                'currentlyEmployee'   => 'required|in:true,false',
                'jobTitle'            => 'nullable|string',
                'workplace'           => 'nullable|string',
                'otherInformation'    => 'nullable|string',
                'disciplinary_issue_document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',

                // Termination fields (sent only if terminating)
                'terminateStudent'    => 'nullable|in:true,false',
                'terminationReason'   => 'required_if:terminateStudent,true|nullable|string',
                'termination_document' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            ]);

            $student = Student::where('student_id', $request->input('studentID'))->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student information does not exist']);
            }

            if ($request->input('terminateStudent') === 'true' && $student->isTerminated()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This student is already terminated. Open the Student Profile to re-register them, or process clearance from All Clearance / Termination Tracking.',
                    'profile_url' => route('student_management.profile', ['studentId' => $student->student_id]),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // file uploads
            $disciplinaryIssueDocumentPath = null;
            if ($request->hasFile('disciplinary_issue_document')) {
                $disciplinaryIssueDocumentPath = $request->file('disciplinary_issue_document')->store('public/disciplinary_issues');
            }

            $terminationDocumentPath = null;
            if ($request->hasFile('termination_document')) {
                $terminationDocumentPath = $request->file('termination_document')->store('public/termination_docs');
            }

            DB::transaction(function () use ($request, $student, $disciplinaryIssueDocumentPath, $terminationDocumentPath) {
                $payload = [
                    'student_id'              => $student->student_id,
                    'disciplinary_issues'     => $request->input('disciplinaryIssues'),
                    'continue_higher_studies' => $request->input('continueStudies') === 'true',
                    'institute'               => $request->input('continueStudies') === 'true' ? $request->input('institute') : null,
                    'field_of_study'          => $request->input('continueStudies') === 'true' ? $request->input('fieldOfStudy') : null,
                    'currently_employee'      => $request->input('currentlyEmployee') === 'true',
                    'job_title'               => $request->input('currentlyEmployee') === 'true' ? $request->input('jobTitle') : null,
                    'workplace'               => $request->input('currentlyEmployee') === 'true' ? $request->input('workplace') : null,
                    'other_information'       => $request->input('otherInformation'),
                ];

                if ($disciplinaryIssueDocumentPath) {
                    $payload['disciplinary_issue_document'] = $disciplinaryIssueDocumentPath;
                }

                StudentOtherInformation::updateOrCreate(
                    ['student_id' => $student->student_id],
                    $payload
                );

                // 2) if terminate flag on, update academic status on students
                if ($request->input('terminateStudent') === 'true') {
                    $student->update([
                        'academic_status'            => 'terminated',
                        'academic_status_reason'     => $request->input('terminationReason'),
                        'academic_status_document'   => $terminationDocumentPath,
                        'academic_status_changed_at' => now(),
                    ]);
                }
            });

            $msg = $request->input('terminateStudent') === 'true'
                ? 'Data stored and student terminated successfully'
                : 'Data stored successfully';

            return response()->json(['success' => true, 'message' => $msg], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error: ' . collect($e->errors())->flatten()->first()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            \Log::error('Database error in storeOtherInformations: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            \Log::error('Error in storeOtherInformations: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
