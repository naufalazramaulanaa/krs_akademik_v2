<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Support\Query\QueryCompiler;
use App\Http\Traits\GenerateCache;

class EnrollmentQueryService
{
    use GenerateCache;

    public function getPaginatedList(array $params): array
    {
        $startTime = microtime(true);

        // Buat cache key dinamis berdasarkan parameter request (search, filter, page, dll)
        $cacheKey = $this->getCacheKey('enrollments_list', $params);

        // Ambil dari cache atau eksekusi query jika cache kedaluwarsa/belum ada
        $result = $this->rememberCache($cacheKey, function () use ($params) {
            $query = DB::table('enrollments')->whereNull('deleted_at');
            QueryCompiler::apply($query, $params);

            $pageSize = $params['page_size'] ?? ($params['per_page'] ?? 25);
            $page = $params['page'] ?? 1;

            $paginator = $query->paginate($pageSize, ['*'], 'page', $page);

            return [
                'items'        => $paginator->items(),
                'total'        => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
            ];
        }, 1800); // TTL 30 Menit

        $queryTimeMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'data' => $result['items'],
            'meta' => [
                'total'         => $result['total'],
                'current_page'  => $result['current_page'],
                'last_page'     => $result['last_page'],
                'per_page'      => $result['per_page'],
                'query_time_ms' => $queryTimeMs,
            ]
        ];
    }
}