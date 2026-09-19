<?php

namespace App\Services\Enrollment;

use Illuminate\Database\Eloquent\Builder;

class FilterCompiler
{
    /**
     * Menerapkan advanced filter dengan logika AND/OR ke query builder.
     */
    public function apply(Builder $query, array $filter)
    {
        $logic = strtoupper($filter['logic'] ?? 'AND');
        $conditions = $filter['conditions'] ?? [];

        if (empty($conditions)) {
            return;
        }

        // Menentukan apakah menggunakan AND atau OR antar kondisi
        $method = $logic === 'OR' ? 'orWhere' : 'where';

        $query->where(function ($q) use ($conditions, $method) {
            foreach ($conditions as $c) {
                $field = $c['field'] ?? null;
                $op = $c['op'] ?? null;
                $value = $c['value'] ?? null;

                if (!$field || !$op) {
                    continue;
                }

                switch ($op) {
                    case 'contains':
                        $q->$method($field, 'ILIKE', "%{$value}%");
                        break;
                    case 'startsWith':
                        $q->$method($field, 'ILIKE', "{$value}%");
                        break;
                    case 'equal':
                        $q->$method($field, $value);
                        break;
                    case 'between':
                        if (is_array($value) && count($value) === 2) {
                            // Menggunakan nested closure untuk menjaga logika AND/OR
                            $subMethod = $method === 'orWhere' ? 'orWhereBetween' : 'whereBetween';
                            $q->$subMethod($field, $value);
                        }
                        break;
                    case 'in':
                        if (is_array($value)) {
                            $subMethod = $method === 'orWhere' ? 'orWhereIn' : 'whereIn';
                            $q->$subMethod($field, $value);
                        }
                        break;
                }
            }
        });
    }
}