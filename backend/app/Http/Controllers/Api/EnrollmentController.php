<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiStructure;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Services\EnrollmentQueryService;
use App\Services\EnrollmentExportService;
use App\Actions\CreateEnrollmentAction;
use App\Actions\UpdateEnrollmentAction;
use App\Actions\DeleteEnrollmentAction;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    use ApiStructure;

    public function __construct(
        protected EnrollmentQueryService $queryService,
        protected EnrollmentExportService $exportService,
        protected CreateEnrollmentAction $createAction,
        protected UpdateEnrollmentAction $updateAction,
        protected DeleteEnrollmentAction $deleteAction
    ) {}

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
        $params = $this->parseQueryParams($request);
        $result = $this->queryService->getPaginatedList($params);

        return $this->success($result['data'], 'Data KRS berhasil diambil.', 200, $result['meta']);
    }

    public function store(StoreEnrollmentRequest $request)
    {
        try {
            $enrollment = $this->createAction->execute($request->validated());
            return $this->success($enrollment, 'KRS berhasil ditambahkan.', 201);
        } catch (\Exception $e) {
            return $this->error('Gagal menyimpan data.', 500, $e->getMessage());
        }
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment)
    {
        try {
            $updated = $this->updateAction->execute($enrollment, $request->validated());
            return $this->success($updated, 'Data KRS berhasil diperbarui.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function destroy(Enrollment $enrollment)
    {
        $this->deleteAction->execute($enrollment);
        return $this->success(null, 'Data KRS berhasil dihapus.');
    }

    public function export(Request $request)
    {
        $params = $this->parseQueryParams($request);
        return $this->exportService->streamCsv($params);
    }
}