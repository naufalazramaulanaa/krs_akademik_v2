<?php

namespace App\Actions\Enrollment;

use App\Http\Requests\Enrollment\ExportRequest;
use App\Services\Enrollment\EnrollmentService;

class EnrollmentExportAction
{
    public function __invoke(ExportRequest $request, EnrollmentService $service)
    {
        // Ambil query berdasarkan filter dan sort aktif
        $query = $service->applyFiltersAndSort($request->validated());
        
        // Ukuran chunk diambil dari konfigurasi env atau default 10.000 baris[cite: 5]
        $chunkSize = (int) env('EXPORT_CHUNK_SIZE', 10000);

        $filename = 'enrollments_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query, $chunkSize) {
            $handle = fopen('php://output', 'w');

            // Tulis header CSV
            fputcsv($handle, [
                'ID', 
                'Student NIM', 
                'Student Name', 
                'Course Code', 
                'Course Name', 
                'Semester', 
                'Academic Year', 
                'Status'
            ]);

            // Eksekusi query dengan chunking untuk menghemat memori
            $query->chunk($chunkSize, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->student_nim,
                        $row->student_name,
                        $row->course_code,
                        $row->course_name,
                        $row->semester,
                        $row->academic_year,
                        $row->status,
                    ]);
                }
                // Paksaflush buffer memori ke output stream
                flush();
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}