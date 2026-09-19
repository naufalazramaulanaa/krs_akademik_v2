<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Mode entitas
            'student_mode' => 'sometimes|in:new,existing',
            'student_id'   => 'required_if:student_mode,existing|exists:students,id',

            // Validasi data baru Student (Gunakan required_if tanpa nullable)
            'student.nim'   => 'required_if:student_mode,new|string|regex:/^\d{8,12}$/|unique:students,nim',
            'student.name'  => 'required_if:student_mode,new|string|min:3|max:100',
            'student.email' => 'required_if:student_mode,new|email|max:150|unique:students,email',

            'course_mode' => 'sometimes|in:new,existing',
            'course_id'   => 'required_if:course_mode,existing|exists:courses,id',

            // Validasi data baru Course (Regex diperluas ke Alfanumerik 2-10 karakter)
            'course.code'    => 'required_if:course_mode,new|string|regex:/^[A-Z0-9]{2,10}$/|unique:courses,code',
            'course.name'    => 'required_if:course_mode,new|string|min:3|max:120',
            'course.credits' => 'required_if:course_mode,new|integer|min:1|max:6',

            // Validasi Enrollment
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{4}$/',
                function ($attribute, $value, $fail) {
                    $years = explode('/', $value);
                    if (count($years) === 2) {
                        $startYear = (int)$years[0];
                        $endYear = (int)$years[1];
                        if ($endYear !== $startYear + 1) {
                            $fail('Format Tahun Ajaran harus berurutan 1 tahun (contoh: 2025/2026).');
                        }
                    }
                },
            ],
            'semester' => 'required|in:GANJIL,GENAP',
            'status'   => 'required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ];
    }
}