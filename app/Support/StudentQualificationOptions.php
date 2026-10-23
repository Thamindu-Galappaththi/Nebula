<?php

namespace App\Support;

class StudentQualificationOptions
{
    public static function campuses(): array
    {
        return [
            ['id' => 'Welisara', 'name' => 'Nebula Institute of Technology - Welisara'],
            ['id' => 'Moratuwa', 'name' => 'Nebula Institute of Technology - Moratuwa'],
            ['id' => 'Peradeniya', 'name' => 'Nebula Institute of Technology - Peradeniya'],
        ];
    }

    public static function campusIds(): array
    {
        return array_column(self::campuses(), 'id');
    }

    public static function btecCourses(): array
    {
        return [
            ['id' => '1', 'course_name' => 'BTEC Level 3 Extended Diploma in Business'],
            ['id' => '2', 'course_name' => 'BTEC Level 3 Extended Diploma in Computing'],
            ['id' => '3', 'course_name' => 'BTEC Level 3 Extended Diploma in Engineering'],
            ['id' => '4', 'course_name' => 'BTEC Level 3 Extended Diploma in Health and Social Care'],
            ['id' => '5', 'course_name' => 'BTEC Level 3 Extended Diploma in Travel and Tourism'],
        ];
    }

    public static function btecCourseValues(): array
    {
        $courses = self::btecCourses();

        return array_values(array_unique(array_merge(
            array_column($courses, 'id'),
            array_column($courses, 'course_name')
        )));
    }

    public static function btecCourseName(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (self::btecCourses() as $course) {
            if ($course['id'] === $value || $course['course_name'] === $value) {
                return $course['course_name'];
            }
        }

        return $value;
    }
}
