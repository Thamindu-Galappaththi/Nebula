<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SpecializationStudentScope
{
    public static function resolveCourseCommonSpecializations(?int $courseId, ?int $intakeId = null, ?array $courseSpecializations = null): array
    {
        if ($courseId === null) {
            return [];
        }

        $courseSpecializations = is_array($courseSpecializations)
            ? array_values(array_filter(array_map(function ($spec) {
                if (!is_string($spec)) {
                    return null;
                }

                $trimmed = trim($spec);
                return $trimmed === '' ? null : $trimmed;
            }, $courseSpecializations)))
            : [];

        $semesterModuleRows = DB::table('semester_module as sm')
            ->join('semesters as s', 's.id', '=', 'sm.semester_id')
            ->where('s.course_id', $courseId)
            ->when($intakeId !== null, fn ($query) => $query->where('s.intake_id', $intakeId))
            ->select('sm.specialization', 'sm.specializations')
            ->get();

        $commonSpecializations = [];

        foreach ($semesterModuleRows as $row) {
            $decoded = SemesterModuleSpecializationHelper::decodeList($row->specializations ?? null, $row->specialization ?? null);

            if (is_array($decoded) && !empty($decoded)) {
                foreach ($decoded as $spec) {
                    $trimmed = trim((string) $spec);
                    if ($trimmed !== '') {
                        $commonSpecializations[$trimmed] = true;
                    }
                }
                continue;
            }

            foreach ($courseSpecializations as $spec) {
                $commonSpecializations[$spec] = true;
            }
        }

        if (empty($commonSpecializations)) {
            return $courseSpecializations;
        }

        return array_values(array_filter(array_map(function ($spec) use ($commonSpecializations) {
            return isset($commonSpecializations[$spec]) ? $spec : null;
        }, $courseSpecializations), fn ($spec) => $spec !== null));
    }
    public static function normalize($specialization): ?string
    {
        if (!is_string($specialization)) {
            return null;
        }

        $specialization = trim($specialization);

        return $specialization === '' ? null : $specialization;
    }

    public static function resolveSelectionSpecializations(?string $specialization, ?string $moduleSpecializationsJson = null, ?string $moduleLegacySpecialization = null, ?array $courseSpecializations = null): array
    {
        $specialization = self::normalize($specialization);

        if ($specialization === null) {
            return [];
        }

        if (strcasecmp($specialization, 'Common') !== 0) {
            $moduleSpecializations = SemesterModuleSpecializationHelper::decodeList(
                $moduleSpecializationsJson,
                $moduleLegacySpecialization
            );

            // A module assigned to specific specializations (e.g. Cloud Fundamentals → AI)
            // must never include students from other tracks.
            if (is_array($moduleSpecializations) && $moduleSpecializations !== []) {
                return in_array($specialization, $moduleSpecializations, true) ? [$specialization] : [];
            }

            return [$specialization];
        }

        // For 'Common': check what specializations the module is tied to.
        $moduleSpecializations = SemesterModuleSpecializationHelper::decodeList(
            $moduleSpecializationsJson,
            $moduleLegacySpecialization
        );

        if (is_array($moduleSpecializations) && !empty($moduleSpecializations)) {
            // Module is assigned to specific specializations (e.g. ["SE","AI"]).
            // Show students from exactly those specializations.
            return array_values(array_unique(array_filter(array_map(function ($value) {
                if (!is_string($value)) {
                    return null;
                }

                $trimmed = trim($value);
                return $trimmed === '' ? null : $trimmed;
            }, $moduleSpecializations))));
        }

        // Module has null specs — it truly applies to ALL students regardless of
        // specialization. Return all course specializations so that the student-ID
        // resolver fetches students across every specialization.
        if (is_array($courseSpecializations) && !empty($courseSpecializations)) {
            return array_values(array_unique(array_filter(array_map(function ($value) {
                if (!is_string($value)) {
                    return null;
                }

                $trimmed = trim($value);
                return $trimmed === '' ? null : $trimmed;
            }, $courseSpecializations))));
        }

        return [];
    }

    public static function resolveStudentIds(?int $courseId, ?int $intakeId, ?string $location, ?string $specialization, ?string $moduleSpecializationsJson = null, ?string $moduleLegacySpecialization = null, ?array $courseSpecializations = null): array
    {
        $specialization = self::normalize($specialization);

        if ($courseId === null || $specialization === null) {
            return [];
        }

        $selectedSpecializations = self::resolveSelectionSpecializations(
            $specialization,
            $moduleSpecializationsJson,
            $moduleLegacySpecialization,
            $courseSpecializations
        );

        if (empty($selectedSpecializations)) {
            return [];
        }

        if (!Schema::hasTable('specialization_registrations')) {
            return [];
        }

        return self::baseQuery('specialization_registrations', $courseId, $intakeId, $location)
            ->whereIn('specialization', $selectedSpecializations)
            ->where('status', 'registered')
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();
    }

    public static function applyToQuery($query, string $studentColumn, ?int $courseId, ?int $intakeId, ?string $location, ?string $specialization, ?string $moduleSpecializationsJson = null, ?string $moduleLegacySpecialization = null, ?array $courseSpecializations = null)
    {
        $studentIds = self::resolveStudentIds(
            $courseId,
            $intakeId,
            $location,
            $specialization,
            $moduleSpecializationsJson,
            $moduleLegacySpecialization,
            $courseSpecializations
        );

        if (empty($studentIds)) {
            if (self::normalize($specialization) === null) {
                return $query;
            }

            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($studentColumn, $studentIds);
    }

    private static function baseQuery(string $table, ?int $courseId, ?int $intakeId, ?string $location)
    {
        return DB::table($table)
            ->when($courseId !== null, function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->when($intakeId !== null, function ($query) use ($intakeId) {
                $query->where('intake_id', $intakeId);
            })
            ->when($location !== null && $location !== '', function ($query) use ($location) {
                $query->where('location', $location);
            });
    }
}
