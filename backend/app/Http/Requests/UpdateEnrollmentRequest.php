<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'      => 'required|in:GANJIL,GENAP',
            'status'        => 'required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ];
    }
}