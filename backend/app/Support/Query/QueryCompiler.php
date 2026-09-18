<?php

namespace App\Support\Query;

use Illuminate\Database\Query\Builder;

class QueryCompiler
{
    // Whitelist kolom yang diizinkan untuk sorting dan filtering (mencegah SQL Injection)
    protected static array $allowedColumns = [
        'id',
        'student_nim',
        'student_name',
        'course_code',
        'course_name',
        'academic_year',
        'semester',
        'status',
        'created_at'
    ];

    public static function apply(Builder $query, array $params): Builder
    {
        // 1. Live Search (Global / Multi kolom)
        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('student_nim', 'ILIKE', $search)
                    ->orWhere('student_name', 'ILIKE', $search)
                    ->orWhere('course_code', 'ILIKE', $search);
            });
        }

        // 2. Quick Filters
        if (!empty($params['quick']) && is_array($params['quick'])) {
            foreach ($params['quick'] as $field => $val) {
                if (in_array($field, self::$allowedColumns) && !empty($val)) {
                    $query->where($field, $val);
                }
            }
        }

        // 3. Advanced Filters (Struktur JSON / Group AND-OR)
        if (!empty($params['filter']) && is_string($params['filter'])) {
            $filterData = json_decode($params['filter'], true);
            if ($filterData && isset($filterData['groups'])) {
                self::compileFilterGroup($query, $filterData);
            }
        }

        // 4. Sorting Multi Kolom
        // 4. Sorting (Mendukung format JSON multi-sort atau parameter tunggal sort_by/sort_dir)
        if (!empty($params['sort']) && is_string($params['sort'])) {
            $sorts = json_decode($params['sort'], true);
            if (is_array($sorts)) {
                foreach ($sorts as $sort) {
                    $field = $sort['field'] ?? null;
                    $dir = strtolower($sort['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
                    if (in_array($field, self::$allowedColumns)) {
                        $query->orderBy($field, $dir);
                    }
                }
            }
        } elseif (!empty($params['sort_by']) && in_array($params['sort_by'], self::$allowedColumns)) {
            // Tambahan dukungan untuk parameter URL standar (?sort_by=kolom&sort_dir=asc)
            $field = $params['sort_by'];
            $dir = strtolower($params['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy($field, $dir);
        } else {
            // Default sorting jika tidak ada parameter sort sama sekali
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    protected static function compileFilterGroup(Builder $query, array $group)
    {
        $logic = strtoupper($group['logic'] ?? 'AND') === 'OR' ? 'orWhere' : 'where';

        $query->{$logic}(function ($subQuery) use ($group) {
            // Proses kondisi tunggal dalam group
            if (!empty($group['conditions'])) {
                foreach ($group['conditions'] as $cond) {
                    self::applyCondition($subQuery, $cond);
                }
            }
            // Proses sub-group rekursif (maksimal kedalaman aman)
            if (!empty($group['groups'])) {
                foreach ($group['groups'] as $subGroup) {
                    self::compileFilterGroup($subQuery, $subGroup);
                }
            }
        });
    }

    protected static function applyCondition(Builder $query, array $cond)
    {
        $field = $cond['field'] ?? null;
        $op = $cond['op'] ?? $cond['operator'] ?? 'equals';
        $val = $cond['value'] ?? null;

        if (!in_array($field, self::$allowedColumns)) {
            return;
        }

        switch ($op) {
            case 'equals':
            case '=':
                $query->where($field, '=', $val);
                break;
            case 'notEquals':
            case '!=':
                $query->where($field, '!=', $val);
                break;
            case 'contains':
                $query->where($field, 'ILIKE', "%{$val}%");
                break;
            case 'startsWith':
                $query->where($field, 'ILIKE', "{$val}%");
                break;
            case 'endsWith':
                $query->where($field, 'ILIKE', "%{$val}");
                break;
            case 'in':
                if (is_array($val)) {
                    $query->whereIn($field, $val);
                }
                break;
            case 'between':
                if (is_array($val) && count($val) === 2) {
                    $query->whereBetween($field, $val);
                }
                break;
        }
    }
}
