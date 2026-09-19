<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index('student_nim');
            $table->index('academic_year');
            $table->index('semester');
            $table->index('status');
            $table->index('course_code');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['student_nim']);
            $table->dropIndex(['academic_year']);
            $table->dropIndex(['semester']);
            $table->dropIndex(['status']);
            $table->dropIndex(['course_code']);
        });
    }
};