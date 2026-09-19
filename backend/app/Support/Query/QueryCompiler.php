<?php

namespace App\Support\Query;

class QueryCompiler
{
    // Whitelist kolom yang diizinkan untuk sorting dan filtering
    protected static array $allowedColumns = [
        'id',
        'student_id',
        'course_id',
        'student_nim',
        'student_name',
        'course_code',
        'course_name',
        'academic_year',
        'semester',
        'status',
        'created_at'
    ];

    public static function apply($query, array $params)
    {
        // 1. Live Search (Ganti ILIKE -> LIKE)
        if (!empty($params['search'])) {
            $search = '%' . trim($params['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('student_nim', 'LIKE', $search)
                    ->orWhere('student_name', 'LIKE', $search)
                    ->orWhere('course_code', 'LIKE', $search)
                    ->orWhere('course_name', 'LIKE', $search);
            });
        }

        // 2. Quick Filters
        if (!empty($params['quick']) && is_array($params['quick'])) {
            foreach ($params['quick'] as $field => $val) {
                if (in_array($field, self::$allowedColumns) && $val !== null && $val !== '') {
                    $query->where($field, $val);
                }
            }
        }

        // 3. Advanced Filters
        if (!empty($params['filter'])) {
            $filterData = is_string($params['filter']) 
                ? json_decode($params['filter'], true) 
                : $params['filter'];

            if (is_array($filterData) && (!empty($filterData['conditions']) || !empty($filterData['groups']))) {
                $query->where(function ($subQuery) use ($filterData) {
                    self::compileFilterGroupDirect($subQuery, $filterData);
                });
            }
        }

        // 4. Multi-Column Sorting
        if (!empty($params['sort'])) {
            $sortData = is_string($params['sort']) 
                ? json_decode($params['sort'], true) 
                : $params['sort'];

            if (is_array($sortData)) {
                $ordersList = [];
                if (isset($sortData['orders']) && is_array($sortData['orders'])) {
                    $ordersList = $sortData['orders'];
                } elseif (array_keys($sortData) === range(0, count($sortData) - 1)) {
                    $ordersList = $sortData;
                }

                $appliedSort = false;
                foreach ($ordersList as $sort) {
                    if (!is_array($sort)) continue;
                    $field = $sort['field'] ?? null;
                    $dirRaw = $sort['dir'] ?? $sort['direction'] ?? 'asc';
                    $dir = strtolower($dirRaw) === 'desc' ? 'desc' : 'asc';

                    if ($field && in_array($field, self::$allowedColumns)) {
                        $query->orderBy($field, $dir);
                        $appliedSort = true;
                    }
                }

                if (!$appliedSort) {
                    $query->orderBy('id', 'desc');
                }
            } else {
                $query->orderBy('id', 'desc');
            }
        } elseif (!empty($params['sort_by']) && in_array($params['sort_by'], self::$allowedColumns)) {
            $field = $params['sort_by'];
            $dir = strtolower($params['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy($field, $dir);
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    protected static function compileFilterGroupDirect($query, array $group)
    {
        $logic = strtoupper($group['logic'] ?? 'AND');
        $first = true;

        // Proses kondisi
        if (!empty($group['conditions']) && is_array($group['conditions'])) {
            foreach ($group['conditions'] as $cond) {
                if (!is_array($cond)) continue;
                if ($first) {
                    self::applyCondition($query, $cond, 'where');
                    $first = false;
                } else {
                    $method = ($logic === 'OR') ? 'orWhere' : 'where';
                    self::applyCondition($query, $cond, $method);
                }
            }
        }

        // Proses sub-group
        if (!empty($group['groups']) && is_array($group['groups'])) {
            foreach ($group['groups'] as $subGroup) {
                if (!is_array($subGroup)) continue;
                $subMethod = ($logic === 'OR' && !$first) ? 'orWhere' : 'where';
                $query->{$subMethod}(function ($nestedQuery) use ($subGroup) {
                    self::compileFilterGroupDirect($nestedQuery, $subGroup);
                });
                $first = false;
            }
        }
    }

    protected static function applyCondition($query, array $cond, string $method = 'where')
    {
        $field = $cond['field'] ?? null;
        $op = $cond['op'] ?? $cond['operator'] ?? 'equals';
        $val = $cond['value'] ?? null;

        if (!$field || !in_array($field, self::$allowedColumns)) {
            return;
        }

        // Ganti semua ILIKE menjadi LIKE
        switch ($op) {
            case 'equals':
            case 'equal':
            case '=':
                if ($val !== null && $val !== '') {
                    $query->{$method}($field, '=', $val);
                }
                break;

            case 'notEquals':
            case '!=':
                if ($val !== null && $val !== '') {
                    $query->{$method}($field, '!=', $val);
                }
                break;

            case 'contains':
            case 'like':
                if ($val !== null && $val !== '') {
                    $query->{$method}($field, 'LIKE', "%{$val}%");
                }
                break;

            case 'startsWith':
                if ($val !== null && $val !== '') {
                    $query->{$method}($field, 'LIKE', "{$val}%");
                }
                break;

            case 'endsWith':
                if ($val !== null && $val !== '') {
                    $query->{$method}($field, 'LIKE', "%{$val}");
                }
                break;

            case 'in':
                if (is_string($val)) {
                    $val = array_filter(array_map('trim', explode(',', $val)));
                }
                if (is_array($val) && count($val) > 0) {
                    $inMethod = ($method === 'orWhere') ? 'orWhereIn' : 'whereIn';
                    $query->{$inMethod}($field, $val);
                }
                break;

            case 'between':
                if (is_array($val) && count($val) === 2 && $val[0] !== '' && $val[1] !== '') {
                    $betweenMethod = ($method === 'orWhere') ? 'orWhereBetween' : 'whereBetween';
                    $query->{$betweenMethod}($field, $val);
                }
                break;
        }
    }
}