<?php

namespace App\Services;

use App\Support\Query\QueryCompiler;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PDO;

class EnrollmentExportService
{
    public function streamCsv(array $params): StreamedResponse
    {
        set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $query = DB::table('enrollments')->whereNull('deleted_at');
        QueryCompiler::apply($query, $params);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return response()->streamDownload(function () use ($sql, $bindings) {
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $output = fopen('php://output', 'w');
            fputcsv($output, ['NIM', 'Nama Mahasiswa', 'Kode MK', 'Nama MK', 'Semester', 'Tahun Ajaran', 'Status']);

            $i = 0;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['student_nim'] ?? '',
                    $row['student_name'] ?? '',
                    $row['course_code'] ?? '',
                    $row['course_name'] ?? '',
                    $row['semester'] ?? '',
                    $row['academic_year'] ?? '',
                    $row['status'] ?? ''
                ]);

                if (++$i % 5000 === 0) {
                    flush();
                }
            }
            fclose($output);
        }, 'enrollments_export.csv', [
            'Content-Type' => 'text/csv',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}