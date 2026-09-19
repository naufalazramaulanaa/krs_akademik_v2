<?php

namespace App\Services\Enrollment;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentService
{
    protected FilterCompiler $filterCompiler;
    protected SortCompiler $sortCompiler;

    public function __construct(FilterCompiler $filterCompiler, SortCompiler $sortCompiler)
    {
        $this->filterCompiler = $filterCompiler;
        $this->sortCompiler = $sortCompiler;
    }

    /**
     * Menerapkan filter dan multi-sort ke query builder utama.
     */
    public function applyFiltersAndSort(array $payload): Builder
    {
        $query = Enrollment::query();

        // 1. Proses Filter
        if (!empty($payload['filter'])) {
            $filterData = is_string($payload['filter']) 
                ? json_decode($payload['filter'], true) 
                : $payload['filter'];

            if (is_array($filterData)) {
                $this->filterCompiler->apply($query, $filterData);
            }
        }

        // 2. Proses Sorting (Multi-Sort)
        if (!empty($payload['sort'])) {
            $sortData = is_string($payload['sort']) 
                ? json_decode($payload['sort'], true) 
                : $payload['sort'];

            if (is_array($sortData)) {
                $this->sortCompiler->apply($query, $sortData);
            }
        }

        return $query;
    }
}