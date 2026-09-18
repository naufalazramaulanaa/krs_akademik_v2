<?php

namespace App\Observers;

use App\Models\Enrollment;
use Illuminate\Support\Facades\Cache;

class EnrollmentObserver
{
    public function saved(Enrollment $enrollment): void
    {
        $this->invalidateCache();
    }

    public function deleted(Enrollment $enrollment): void
    {
        $this->invalidateCache();
    }

    public function restored(Enrollment $enrollment): void
    {
        $this->invalidateCache();
    }

    protected function invalidateCache(): void
    {
        // Jika driver cache mendukung tag (seperti Redis/Memcached)
        if (Cache::supportsTags()) {
            Cache::tags(['enrollments'])->flush();
        } else {
            // Jika menggunakan File/Database driver, lakukan pembersihan global cache
            Cache::flush();
        }
    }
}