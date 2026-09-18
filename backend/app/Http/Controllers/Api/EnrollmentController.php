<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Course;
use App\Support\Query\QueryCompiler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDO;

class EnrollmentController extends Controller
{
    /**
     * Menampilkan daftar KRS dengan format response & parameter yang cocok dengan Frontend.
     */
    public function index(Request $request)
    {
        $startTime = microtime(true);

        // WAJIB menggunakan DB::table() agar menghasilkan Illuminate\Database\Query\Builder
        $query = DB::table('enrollments')->whereNull('deleted_at');

        // Terapkan QueryCompiler
        QueryCompiler::apply($query, $request->all());

        // Menangkap parameter 'page_size' dari frontend
        $pageSize = $request->input('page_size', $request->input('per_page', 25));
        $page = $request->input('page', 1);

        // Eksekusi paginasi standar
        $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

        // Hitung durasi waktu query dalam milidetik
        $queryTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'total'         => $paginator->total(),
                'current_page'  => $paginator->currentPage(),
                'last_page'     => $paginator->lastPage(),
                'per_page'      => $paginator->perPage(),
                'query_time_ms' => $queryTimeMs,
            ]
        ]);
    }

    /**
     * Menambahkan data KRS baru (Atomic 3 Tabel)
     */
    public function store(Request $request)
    {
        $request->validate([
            'student.nim'    => 'required|string',
            'student.name'   => 'required|string',
            'student.email'  => 'required|email',
            'course.code'    => 'required|string',
            'course.name'    => 'required|string',
            'course.credits' => 'required|integer|min:1|max:6',
            'academic_year'  => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'       => 'required|in:GANJIL,GENAP',
            'status'         => 'required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $student = Student::firstOrCreate(
                    ['nim' => $request->input('student.nim')],
                    [
                        'name'  => $request->input('student.name'),
                        'email' => $request->input('student.email')
                    ]
                );

                $course = Course::firstOrCreate(
                    ['code' => $request->input('course.code')],
                    [
                        'name'    => $request->input('course.name'),
                        'credits' => $request->input('course.credits')
                    ]
                );

                Enrollment::create([
                    'student_id'    => $student->id,
                    'course_id'     => $course->id,
                    'student_nim'   => $student->nim,
                    'student_name'  => $student->name,
                    'course_code'   => $course->code,
                    'course_name'   => $course->name,
                    'academic_year' => $request->input('academic_year'),
                    'semester'      => $request->input('semester'),
                    'status'        => $request->input('status'),
                ]);
            });

            return response()->json(['message' => 'KRS berhasil ditambahkan.'], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export CSV Skala Besar dengan Streaming (Aman untuk 5 Juta Data)
     */
    public function export(Request $request)
    {
        set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $query = DB::table('enrollments')->whereNull('deleted_at');
        QueryCompiler::apply($query, $request->all());

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        return response()->streamDownload(function () use ($sql, $bindings) {
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($bindings);

            $output = fopen('php://output', 'w');

            // Header CSV
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
            'Content-Type'        => 'text/csv',
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    /**
     * Memperbarui data KRS (Update)
     */
    public function update(Request $request, $id)
    {
        $enrollment = Enrollment::whereNull('deleted_at')->findOrFail($id);

        $request->validate([
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'      => 'required|in:GANJIL,GENAP',
            'status'        => 'required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ]);

        try {
            DB::transaction(function () use ($request, $enrollment) {
                $enrollment->update([
                    'academic_year' => $request->input('academic_year'),
                    'semester'      => $request->input('semester'),
                    'status'        => $request->input('status'),
                ]);
            });

            return response()->json([
                'message' => 'Data KRS berhasil diperbarui.',
                'data'    => $enrollment
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json([
                    'message' => 'Konflik Data Unik: Kombinasi mahasiswa, mata kuliah, tahun ajaran, dan semester sudah ada.',
                ], 409);
            }
            throw $e;
        }
    }

    /**
     * Menghapus data KRS (Soft Delete)
     */
    public function destroy($id)
    {
        $enrollment = Enrollment::whereNull('deleted_at')->findOrFail($id);
        $enrollment->delete();

        return response()->json([
            'message' => 'Data KRS berhasil dihapus.'
        ]);
    }
}