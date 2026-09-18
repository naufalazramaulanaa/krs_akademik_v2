<?php

namespace App\Actions;

use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class CreateEnrollmentAction
{
    public function execute(array $data): Enrollment
    {
        return DB::transaction(function () use ($data) {
            $student = Student::firstOrCreate(
                ['nim' => $data['student']['nim']],
                [
                    'name'  => $data['student']['name'],
                    'email' => $data['student']['email']
                ]
            );

            $course = Course::firstOrCreate(
                ['code' => $data['course']['code']],
                [
                    'name'    => $data['course']['name'],
                    'credits' => $data['course']['credits']
                ]
            );

            return Enrollment::create([
                'student_id'    => $student->id,
                'course_id'     => $course->id,
                'student_nim'   => $student->nim,
                'student_name'  => $student->name,
                'course_code'   => $course->code,
                'course_name'   => $course->name,
                'academic_year' => $data['academic_year'],
                'semester'      => $data['semester'],
                'status'        => $data['status'],
            ]);
        });
    }
}