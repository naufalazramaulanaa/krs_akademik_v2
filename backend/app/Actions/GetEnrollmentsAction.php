<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GetEnrollmentsAction
{
    public function execute(Request $request)
    {
        // 1. Ambil parameter sorting dari URL
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');

        // 2. Validasi kolom yang diizinkan (mencegah SQL Injection)
        $allowedColumns = [
            'student_nim', 'student_name', 'course_code', 
            'course_name', 'academic_year', 'semester', 'status', 'created_at'
        ];

        if (!in_array($sortBy, $allowedColumns)) {
            $sortBy = 'created_at';
        }

        if (!in_array(strtolower($sortDir), ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        // 3. Eksekusi Query dan Pagination untuk 5 juta data
        return DB::table('enrollments')
            ->orderBy($sortBy, $sortDir)
            ->paginate(50)
            ->appends($request->all());
    }
}