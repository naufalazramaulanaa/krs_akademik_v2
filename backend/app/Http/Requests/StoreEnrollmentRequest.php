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
            // Mode entitas: 'new' atau 'existing' (opsional, default new/create jika ID tidak ada)
            'student_mode' => 'sometimes|in:new,existing',
            'student_id'   => 'required_if:student_mode,existing|exists:students,id',
            
            // Validasi data baru Student
            'student.nim'   => 'required_if:student_mode,new|nullable|string|size:10|regex:/^\d{8,12}$/|unique:students,nim',
            'student.name'  => 'required_if:student_mode,new|nullable|string|min:3|max:100',
            'student.email' => 'required_if:student_mode,new|nullable|email|max:150|unique:students,email',

            'course_mode' => 'sometimes|in:new,existing',
            'course_id'   => 'required_if:course_mode,existing|exists:courses,id',

            // Validasi data baru Course
            'course.code'    => 'required_if:course_mode,new|nullable|string|regex:/^[A-Z]{2,4}[0-9]{3}$/|unique:courses,code',
            'course.name'    => 'required_if:course_mode,new|nullable|string|min:3|max:120',
            'course.credits' => 'required_if:course_mode,new|nullable|integer|min:1|max:6',

            // Validasi Enrollment
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'      => 'required|in:GANJIL,GENAP',
            'status'        => 'required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ];
    }
}