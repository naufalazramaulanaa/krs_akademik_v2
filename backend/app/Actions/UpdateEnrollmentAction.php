<?php

namespace App\Actions;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

class UpdateEnrollmentAction
{
    public function execute(Enrollment $enrollment, array $data): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $data) {
            $enrollment->update([
                'academic_year' => $data['academic_year'],
                'semester'      => $data['semester'],
                'status'        => $data['status'],
            ]);

            return $enrollment;
        });
    }
}