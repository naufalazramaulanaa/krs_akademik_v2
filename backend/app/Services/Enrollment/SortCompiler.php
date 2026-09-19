<?php

namespace App\Services\Enrollment;

use Illuminate\Database\Eloquent\Builder;

class SortCompiler

{
    /**
     * Menerapkan multi-column sorting (advanced order) secara berurutan.
     */
    public function apply(Builder $query, array $sort)
    {
        $orders = $sort['orders'] ?? [];

        foreach ($orders as $o) {
            $field = $o['field'] ?? null;
            $direction = strtolower($o['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

            if ($field) {
                $query->orderBy($field, $direction);
            }
        }
    }
}