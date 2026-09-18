<?php

namespace App\Actions;

use App\Models\Enrollment;

class DeleteEnrollmentAction
{
    public function execute(Enrollment $enrollment): bool
    {
        return $enrollment->delete();
    }
}