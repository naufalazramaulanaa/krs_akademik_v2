<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiStructure;
use App\Services\EnrollmentQueryService;
use App\Actions\CreateEnrollmentAction;
use App\Actions\UpdateEnrollmentAction;
use App\Actions\DeleteEnrollmentAction;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use App\Support\Query\QueryCompiler;
use Illuminate\Support\Facades\DB;
use PDO;

class EnrollmentController extends Controller
{
    use ApiStructure;

    protected EnrollmentQueryService $queryService;
    protected CreateEnrollmentAction $createAction;
    protected UpdateEnrollmentAction $updateAction;
    protected DeleteEnrollmentAction $deleteAction;

    public function __construct(
        EnrollmentQueryService $queryService,
        CreateEnrollmentAction $createAction,
        UpdateEnrollmentAction $updateAction,
        DeleteEnrollmentAction $deleteAction
    ) {
        $this->queryService = $queryService;
        $this->createAction = $createAction;
        $this->updateAction = $updateAction;
        $this->deleteAction = $deleteAction;
    }

    /**
     * Helper privat untuk decode query parameter filter & sort jika dikirim sebagai JSON string dari Frontend (Next.js)
     */
    private function parseQueryParams(Request $request): array
    {
        $params = $request->all();

        if (isset($params['filter']) && is_string($params['filter'])) {
            $params['filter'] = json_decode($params['filter'], true);
        }

        if (isset($params['sort']) && is_string($params['sort'])) {
            $params['sort'] = json_decode($params['sort'], true);
        }

        return $params;
    }

    public function index(Request $request)
    {
        // Parse params agar format filter & sort berupa Array murni
        $params = $this->parseQueryParams($request);

        $result = $this->queryService->getPaginatedList($params);

        return $this->success($result['data'], 'Data KRS berhasil diambil.', 200, $result['meta']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
            $enrollment = $this->createAction->execute($validated);
            return $this->success($enrollment, 'KRS berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error('Gagal menyimpan data.', 500, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $enrollment = Enrollment::whereNull('deleted_at')->findOrFail($id);

        $validated = $request->validate([
            'academic_year' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'      => 'required|in:GANJIL,GENAP',
            'status'        => 'required|in:DRAFT,SUBMITTED,APPROVED,REJECTED',
        ]);

        try {
            $updated = $this->updateAction->execute($enrollment, $validated);
            return $this->success($updated, 'Data KRS berhasil diperbarui.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505') {
                return $this->error('Konflik Data Unik: Kombinasi mahasiswa, mata kuliah, tahun ajaran, dan semester sudah ada.', 409);
            }
            return $this->error('Gagal memperbarui data.', 500, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $enrollment = Enrollment::whereNull('deleted_at')->findOrFail($id);
        $this->deleteAction->execute($enrollment);

        return $this->success(null, 'Data KRS berhasil dihapus.');
    }

    public function export(Request $request)
    {
        set_time_limit(0);
        @ini_set('memory_limit', '512M');

        // Parse params JSON string dari Frontend
        $params = $this->parseQueryParams($request);

        $query = DB::table('enrollments')->whereNull('deleted_at');
        
        // Terapkan QueryCompiler menggunakan array params yang sudah di-parse
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
            'Content-Type'        => 'text/csv',
            'X-Accel-Buffering'   => 'no',
        ]);
    }
}