<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Module;
use App\Models\Semester;
use App\Models\SemesterModule;
use App\Models\SpecializationRegistration;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MissingDataRestoreService
{
    public const TYPE_STUDENTS = 'students';
    public const TYPE_MODULES = 'modules';
    public const TYPE_SEMESTERS = 'semesters';
    public const TYPE_SEMESTER_MODULES = 'semester_modules';

    public function preview(UploadedFile $file, string $type, array $context): array
    {
        $rows = $this->readRows($file);

        return match ($type) {
            self::TYPE_STUDENTS => $this->previewStudents($rows, $context),
            self::TYPE_MODULES => $this->previewModules($rows),
            self::TYPE_SEMESTERS => $this->previewSemesters($rows),
            self::TYPE_SEMESTER_MODULES => $this->previewSemesterModules($rows, $context),
            default => throw new \InvalidArgumentException('Unknown import type.'),
        };
    }

    public function commit(array $preview, array $context): array
    {
        return DB::transaction(function () use ($preview, $context) {
            $inserted = 0;
            $skipped = 0;

            foreach ($preview['rows'] as $row) {
                $action = $row['action'] ?? 'skip';
                if ($action === 'skip' || $action === 'error') {
                    if ($action === 'skip') {
                        $skipped++;
                    }
                    continue;
                }

                $this->commitRow($preview['type'], $row, $context);
                $inserted++;
            }

            return [
                'inserted' => $inserted,
                'skipped' => $skipped,
                'failed' => [],
            ];
        });
    }

    private function commitRow(string $type, array $row, array $context): void
    {
        if ($type === self::TYPE_STUDENTS) {
            $this->commitStudentRow($row, $context);
            return;
        }
        if ($type === self::TYPE_MODULES) {
            Module::create([
                'module_code' => $row['module_code'],
                'module_name' => $row['module_name'],
                'module_type' => $row['module_type'] ?: 'core',
                'module_category' => $row['module_category'] ?: 'degree',
                'module_cordinator' => $row['module_cordinator'] ?: null,
                'credits' => $row['credits'] !== '' ? $row['credits'] : null,
            ]);
            return;
        }
        if ($type === self::TYPE_SEMESTERS) {
            $this->commitSemesterRow($row);
            return;
        }

        $module = $this->findModule($row['module_code']);
        if (!$module) {
            throw new \RuntimeException('Module '.$row['module_code'].' was not found.');
        }

        $exists = SemesterModule::where('semester_id', $context['semester_id'])
            ->where('module_id', $module->module_id)
            ->exists();
        if ($exists) {
            return;
        }

        $specialization = $row['specialization'] !== '' ? $row['specialization'] : null;
        SemesterModule::create([
            'semester_id' => $context['semester_id'],
            'module_id' => $module->module_id,
            'specialization' => $specialization,
            'specializations' => $specialization ? [$specialization] : null,
        ]);
    }

    private function commitStudentRow(array $row, array $context): void
    {
        $intake = Intake::findOrFail($context['intake_id']);
        $course = Course::findOrFail($intake->course_id);
        $student = $this->findStudent($row['id_value'], $row['full_name'] ?? '');

        if (!$student) {
            $student = Student::create([
                'title' => $row['title'],
                'name_with_initials' => $row['name_with_initials'],
                'full_name' => $row['full_name'],
                'gender' => $row['gender'],
                'id_type' => $row['id_type'],
                'id_value' => $row['id_value'],
                'email' => $row['email'] !== '' ? strtolower($row['email']) : null,
                'mobile_phone' => $row['mobile_phone'] !== '' ? $row['mobile_phone'] : null,
                'birthday' => $row['birthday'] !== '' ? $row['birthday'] : null,
                'address' => $row['address'] !== '' ? $row['address'] : null,
                'district' => $row['district'] !== '' ? $row['district'] : null,
                'institute_location' => $intake->location,
                'status' => $row['marital_status'] ?: 'Unmarried',
                'academic_status' => 'active',
                'remarks' => 'Inserted via Missing Data Restore',
            ]);
        }

        $alreadyRegistered = CourseRegistration::where('student_id', $student->student_id)
            ->where('intake_id', $intake->intake_id)
            ->exists();

        if (!$alreadyRegistered) {
            CourseRegistration::create([
                'student_id' => $student->student_id,
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'course_registration_id' => $this->nextRegistrationId($intake),
                'registration_date' => now()->toDateString(),
                'registration_fee' => $intake->registration_fee ?: 0,
                'status' => 'Registered',
                'approval_status' => 'Approved by manager',
                'location' => $intake->location,
                'slt_employee' => false,
                'remarks' => 'Inserted via Missing Data Restore',
            ]);
        }

        $specialization = $row['specialization'] !== ''
            ? $row['specialization']
            : $this->defaultSpecialization($course);

        if ($specialization && !SpecializationRegistration::where('student_id', $student->student_id)
            ->where('intake_id', $intake->intake_id)
            ->exists()) {
            SpecializationRegistration::create([
                'student_id' => $student->student_id,
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'location' => $intake->location,
                'specialization' => $specialization,
                'status' => 'registered',
            ]);
        }
    }

    private function previewStudents(array $rows, array $context): array
    {
        $intake = Intake::find($context['intake_id'] ?? null);
        if (!$intake) {
            throw new \InvalidArgumentException('Select a valid intake before uploading students.');
        }

        $out = [];
        foreach ($rows as $index => $raw) {
            $mapped = $this->mapStudent($raw);
            $line = $index + 2;
            if ($this->isEmptyRow($mapped)) {
                continue;
            }

            $errors = $this->studentErrors($mapped);
            if ($errors) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => implode(' ', $errors),
                ];
                continue;
            }

            $student = $this->findStudent($mapped['id_value'], $mapped['full_name']);
            if (!$student) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'insert_student',
                    'message' => 'New student + registration on '.$intake->batch,
                ];
                continue;
            }

            $onIntake = CourseRegistration::where('student_id', $student->student_id)
                ->where('intake_id', $intake->intake_id)
                ->exists();

            if ($onIntake) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'skip',
                    'message' => 'Already registered on this intake (student_id '.$student->student_id.')',
                ];
                continue;
            }

            $out[] = $mapped + [
                'row' => $line,
                'action' => 'insert_registration',
                'message' => 'Student exists (ID '.$student->student_id.'). Will add registration only.',
            ];
        }

        return ['type' => self::TYPE_STUDENTS, 'rows' => $out];
    }

    private function previewModules(array $rows): array
    {
        $out = [];
        foreach ($rows as $index => $raw) {
            $mapped = $this->mapModule($raw);
            $line = $index + 2;
            if ($mapped['module_code'] === '' && $mapped['module_name'] === '') {
                continue;
            }
            if ($mapped['module_code'] === '' || $mapped['module_name'] === '') {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => 'module_code and module_name are required.',
                ];
                continue;
            }

            $existing = $this->findModule($mapped['module_code']);
            if (!$existing) {
                $existing = Module::query()
                    ->whereRaw('LOWER(module_name) = ?', [mb_strtolower($mapped['module_name'])])
                    ->first();
            }

            if ($existing) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'skip',
                    'message' => 'Already exists as '.$existing->module_code,
                ];
                continue;
            }

            $out[] = $mapped + [
                'row' => $line,
                'action' => 'insert_module',
                'message' => 'New module catalogue row',
            ];
        }

        return ['type' => self::TYPE_MODULES, 'rows' => $out];
    }

    private function previewSemesters(array $rows): array
    {
        $out = [];
        foreach ($rows as $index => $raw) {
            $mapped = $this->mapSemester($raw);
            $line = $index + 2;
            if ($mapped['intake'] === '' && $mapped['semester'] === '' && $mapped['start_date'] === '') {
                continue;
            }

            $intake = $this->findIntakeByBatch($mapped['intake']);
            if (!$intake) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => 'No intake matches "'.$mapped['intake'].'".',
                ];
                continue;
            }

            $course = Course::find($intake->course_id);
            if (!$course) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => 'Intake '.$intake->batch.' has no course.',
                ];
                continue;
            }

            $slot = Semester::slotFromInput($mapped['semester'], $course);
            if ($slot === null) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => 'Invalid semester "'.$mapped['semester'].'" for this course.',
                ];
                continue;
            }

            if ($mapped['start_date'] === '' || $mapped['end_date'] === '') {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => 'Start and end dates are required.',
                ];
                continue;
            }

            if ($mapped['start_date'] > $mapped['end_date']) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => 'Start date is after end date.',
                ];
                continue;
            }

            $name = Semester::labelForSlot($slot, $course->semester_format);
            $mapped['name'] = $name;
            $mapped['intake_id'] = $intake->intake_id;
            $mapped['course_id'] = $course->course_id;
            $mapped['status'] = $this->statusFromDates($mapped['start_date'], $mapped['end_date']);

            $existing = $this->existingSemesterForSlot($intake, $course, $slot);
            if ($existing) {
                $existingStart = Carbon::parse($existing->start_date)->toDateString();
                $existingEnd = Carbon::parse($existing->end_date)->toDateString();
                $sameDates = $existingStart === $mapped['start_date']
                    && $existingEnd === $mapped['end_date'];
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'skip',
                    'message' => $sameDates
                        ? 'Already exists as semester '.$existing->name.' (id '.$existing->id.').'
                        : 'Already exists as id '.$existing->id.' with '.$existingStart
                            .' to '.$existingEnd
                            .'. Restore will not overwrite. Edit that semester if these dates should replace it.',
                ];
                continue;
            }

            $out[] = $mapped + [
                'row' => $line,
                'action' => 'insert_semester',
                'message' => 'Will create '.$intake->batch.' semester '.$name.' ('.$mapped['start_date'].' to '.$mapped['end_date'].')',
            ];
        }

        return ['type' => self::TYPE_SEMESTERS, 'rows' => $out];
    }

    private function commitSemesterRow(array $row): void
    {
        $exists = Semester::query()
            ->where('intake_id', $row['intake_id'])
            ->where('name', $row['name'])
            ->exists();
        if ($exists) {
            return;
        }

        Semester::create([
            'name' => $row['name'],
            'course_id' => $row['course_id'],
            'intake_id' => $row['intake_id'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'status' => $row['status'] ?? $this->statusFromDates($row['start_date'], $row['end_date']),
        ]);
    }

    private function mapSemester(array $raw): array
    {
        return [
            'intake' => $this->val($raw, ['intake', 'batch', 'intake_batch']),
            'semester' => $this->val($raw, ['semester', 'name', 'semester_name']),
            'start_date' => $this->parseDate($this->val($raw, ['start', 'start_date', 'start date'])),
            'end_date' => $this->parseDate($this->val($raw, ['end', 'end_date', 'end date'])),
        ];
    }

    private function findIntakeByBatch(string $batch): ?Intake
    {
        $batch = trim($batch);
        if ($batch === '') {
            return null;
        }

        return Intake::query()
            ->whereRaw('LOWER(TRIM(batch)) = ?', [strtolower($batch)])
            ->first();
    }

    private function existingSemesterForSlot(Intake $intake, Course $course, int $slot): ?Semester
    {
        $semesters = Semester::query()
            ->where('intake_id', $intake->intake_id)
            ->get();

        foreach ($semesters as $semester) {
            $semester->setRelation('course', $course);
            if ($semester->resolvedSlotNumber() === $slot) {
                return $semester;
            }
        }

        return null;
    }

    private function statusFromDates(string $start, string $end): string
    {
        $today = now()->toDateString();
        if ($start > $today) {
            return 'upcoming';
        }
        if ($end >= $today) {
            return 'active';
        }

        return 'completed';
    }

    private function previewSemesterModules(array $rows, array $context): array
    {
        $semester = Semester::find($context['semester_id'] ?? null);
        if (!$semester) {
            throw new \InvalidArgumentException('Select a valid semester before uploading module links.');
        }

        $out = [];
        foreach ($rows as $index => $raw) {
            $mapped = [
                'module_code' => $this->val($raw, ['module_code', 'code']),
                'specialization' => $this->val($raw, ['specialization', 'pathway']),
            ];
            $line = $index + 2;
            if ($mapped['module_code'] === '') {
                continue;
            }

            $module = $this->findModule($mapped['module_code']);
            if (!$module) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'error',
                    'message' => 'No module matches '.$mapped['module_code'].'. Import the module first.',
                ];
                continue;
            }

            $linked = SemesterModule::where('semester_id', $semester->id)
                ->where('module_id', $module->module_id)
                ->exists();
            if ($linked) {
                $out[] = $mapped + [
                    'row' => $line,
                    'action' => 'skip',
                    'message' => 'Already linked to this semester',
                ];
                continue;
            }

            $out[] = $mapped + [
                'row' => $line,
                'action' => 'insert_link',
                'message' => 'Will link '.$module->module_name.' to semester '.$semester->name,
            ];
        }

        return ['type' => self::TYPE_SEMESTER_MODULES, 'rows' => $out];
    }

    private function mapStudent(array $raw): array
    {
        $idType = $this->normalizeIdType($this->val($raw, ['id_type', 'identification_type', 'id type']));
        $idValue = $this->val($raw, ['id_value', 'nic', 'nic_no', 'national identity card no', 'passport']);

        return [
            'title' => $this->val($raw, ['title']) ?: 'Mr',
            'name_with_initials' => $this->val($raw, ['name_with_initials', 'initials', 'name with initials']),
            'full_name' => $this->val($raw, ['full_name', 'name', 'name in full', 'full name']),
            'gender' => $this->normalizeGender($this->val($raw, ['gender'])),
            'id_type' => $idType ?: $this->guessIdType($idValue),
            'id_value' => $idValue,
            'email' => strtolower($this->val($raw, ['email', 'email address'])),
            'mobile_phone' => $this->val($raw, ['mobile_phone', 'phone', 'contact', 'contact no']),
            'birthday' => $this->parseDate($this->val($raw, ['birthday', 'date_of_birth', 'dob'])),
            'address' => $this->val($raw, ['address', 'permanent address']),
            'district' => $this->val($raw, ['district']),
            'marital_status' => $this->normalizeMarital($this->val($raw, ['marital_status', 'status'])),
            'specialization' => $this->val($raw, ['specialization', 'spec', 'pathway']),
        ];
    }

    private function mapModule(array $raw): array
    {
        return [
            'module_code' => strtoupper($this->val($raw, ['module_code', 'code'])),
            'module_name' => $this->val($raw, ['module_name', 'name', 'module']),
            'module_type' => $this->val($raw, ['module_type', 'type']) ?: 'core',
            'module_category' => $this->val($raw, ['module_category', 'category']) ?: 'degree',
            'module_cordinator' => $this->val($raw, ['module_cordinator', 'coordinator', 'module_leader', 'leader']),
            'credits' => $this->val($raw, ['credits']) ?: '15',
        ];
    }

    private function studentErrors(array $mapped): array
    {
        $errors = [];
        if ($mapped['id_value'] === '') {
            $errors[] = 'id_value / NIC is required.';
        }
        $student = $this->findStudent($mapped['id_value'], $mapped['full_name']);
        if (!$student) {
            if ($mapped['full_name'] === '') {
                $errors[] = 'full_name is required for new students.';
            }
            if ($mapped['name_with_initials'] === '') {
                $errors[] = 'name_with_initials is required for new students.';
            }
            if (!in_array($mapped['gender'], ['Male', 'Female'], true)) {
                $errors[] = 'gender must be Male or Female.';
            }
            if (!in_array($mapped['title'], ['Mr', 'Mrs', 'Miss', 'Dr', 'Rev', 'Ms', 'Sir', 'Madam', 'Prof'], true)) {
                $errors[] = 'title is invalid.';
            }
        }
        if ($mapped['email'] !== '') {
            $emailOwner = Student::whereRaw('LOWER(email) = ?', [$mapped['email']])->first();
            if ($emailOwner && (!$student || (int) $emailOwner->student_id !== (int) $student->student_id)) {
                $errors[] = 'Email already belongs to another student.';
            }
        }
        return $errors;
    }

    private function findStudent(string $idValue, string $fullName): ?Student
    {
        foreach ($this->idVariants($idValue) as $variant) {
            $match = Student::whereRaw('UPPER(id_value) = ?', [strtoupper($variant)])->first();
            if ($match) {
                return $match;
            }
        }

        $name = $this->nameKey($fullName);
        if ($name === '') {
            return null;
        }

        $matches = Student::query()
            ->get(['student_id', 'full_name', 'id_value'])
            ->filter(fn ($s) => $this->nameKey($s->full_name) === $name);

        return $matches->count() === 1 ? Student::find($matches->first()->student_id) : null;
    }

    private function findModule(string $code): ?Module
    {
        $code = strtoupper(preg_replace('/\s+/', '', $code));
        if ($code === '') {
            return null;
        }

        $exact = Module::whereRaw('UPPER(module_code) = ?', [$code])->first();
        if ($exact) {
            return $exact;
        }

        return Module::where('module_code', 'like', '%'.$code)->first();
    }

    private function defaultSpecialization(Course $course): ?string
    {
        $specs = $course->specializations;
        if (is_string($specs)) {
            $specs = json_decode($specs, true);
        }
        if (!is_array($specs) || $specs === []) {
            return null;
        }
        $first = reset($specs);
        return is_string($first) && $first !== '' ? $first : null;
    }

    public function nextRegistrationId(Intake $intake): string
    {
        $pattern = $intake->course_registration_id_pattern ?: 'REG-001';
        if (!preg_match('/^(.*?)(\d+)$/', $pattern, $matches)) {
            $prefix = rtrim($pattern, '-').'-';
            $width = 2;
            $start = 1;
        } else {
            $prefix = $matches[1];
            $width = strlen($matches[2]);
            $start = (int) $matches[2];
        }

        $max = $start - 1;
        $ids = CourseRegistration::where('intake_id', $intake->intake_id)
            ->whereNotNull('course_registration_id')
            ->pluck('course_registration_id');

        foreach ($ids as $id) {
            if (preg_match('/^(.*?)(\d+)$/', (string) $id, $m) && $m[1] === $prefix) {
                $max = max($max, (int) $m[2]);
            }
        }

        return $prefix.str_pad((string) ($max + 1), $width, '0', STR_PAD_LEFT);
    }

    private function readRows(UploadedFile $file): array
    {
        $sheets = Excel::toArray(null, $file);
        $sheet = $sheets[0] ?? [];
        if ($sheet === []) {
            return [];
        }

        $headerIndex = 0;
        foreach ($sheet as $i => $row) {
            $joined = strtolower(implode(' ', array_map(fn ($c) => trim((string) $c), $row)));
            if (trim($joined) !== '') {
                $headerIndex = $i;
                break;
            }
        }

        $headers = array_map(function ($h) {
            return strtolower(trim(preg_replace('/\s+/', ' ', (string) $h)));
        }, $sheet[$headerIndex]);

        $rows = [];
        foreach (array_slice($sheet, $headerIndex + 1) as $row) {
            $assoc = [];
            foreach ($headers as $col => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = isset($row[$col]) ? trim((string) $row[$col]) : '';
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    private function val(array $raw, array $keys): string
    {
        foreach ($keys as $key) {
            $key = strtolower($key);
            if (isset($raw[$key]) && trim((string) $raw[$key]) !== '') {
                return trim((string) $raw[$key]);
            }
        }
        return '';
    }

    private function isEmptyRow(array $mapped): bool
    {
        return $mapped['id_value'] === '' && $mapped['full_name'] === '' && $mapped['email'] === '';
    }

    private function normalizeGender(string $value): string
    {
        $value = strtolower($value);
        if (in_array($value, ['m', 'male'], true)) {
            return 'Male';
        }
        if (in_array($value, ['f', 'female'], true)) {
            return 'Female';
        }
        return $value === '' ? '' : ucfirst($value);
    }

    private function normalizeMarital(string $value): string
    {
        $value = strtolower($value);
        if (in_array($value, ['married'], true)) {
            return 'Married';
        }
        return 'Unmarried';
    }

    private function normalizeIdType(string $value): string
    {
        $value = strtolower($value);
        return match (true) {
            str_contains($value, 'passport') => 'Passport',
            str_contains($value, 'postal') => 'Postal id',
            str_contains($value, 'driv') => 'Driving Licence',
            str_contains($value, 'nic') || str_contains($value, 'national') => 'National id',
            default => '',
        };
    }

    private function guessIdType(string $idValue): string
    {
        $n = strtoupper(preg_replace('/[^0-9A-Z]/', '', $idValue));
        if (preg_match('/^[A-Z]\d{6,8}$/', $n)) {
            return 'Passport';
        }
        return 'National id';
    }

    private function parseDate(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return '';
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})$/', $value, $m)) {
                $year = (int) $m[3];
                if ($year < 100) {
                    $year += 2000;
                }
                return sprintf('%04d-%02d-%02d', $year, (int) $m[2], (int) $m[1]);
            }
            return '';
        }
    }

    private function idVariants(string $value): array
    {
        $n = strtoupper(preg_replace('/[^0-9A-Z]/', '', $value));
        if ($n === '') {
            return [];
        }
        $out = [$n];
        if (strlen($n) === 13 && ctype_digit($n)) {
            $out[] = substr($n, 0, 12);
            $out[] = substr($n, 1, 12);
        }
        return array_values(array_unique($out));
    }

    private function nameKey(string $name): string
    {
        return strtolower(trim(preg_replace('/[^a-z]+/i', ' ', $name)));
    }
}
