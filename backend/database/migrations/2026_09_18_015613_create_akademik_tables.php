<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ekstensi pg_trgm untuk performa pencarian
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm;');

        // 2. Buat tipe ENUM di level database PostgreSQL
        DB::statement("DO $$ BEGIN
            CREATE TYPE semester_enum AS ENUM ('GANJIL', 'GENAP');
            CREATE TYPE status_enum AS ENUM ('DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED');
        EXCEPTION
            WHEN duplicate_object THEN null;
        END $$;");

        // 3. Tabel Students
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nim', 12)->unique();
            $table->string('name', 100);
            $table->string('email', 150)->unique();
            $table->timestampsTz();
        });

        // 4. Tabel Courses
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 7)->unique();
            $table->string('name', 120);
            $table->smallInteger('credits');
            $table->timestampsTz();
        });

        // 5. Tabel Enrollments
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('restrict');
            $table->foreignId('course_id')->constrained('courses')->onDelete('restrict');
            
            $table->string('academic_year', 9);
            
            // Kolom Snapshot (Denormalisasi)
            $table->string('student_nim', 12);
            $table->string('student_name', 100);
            $table->string('course_code', 7);
            $table->string('course_name', 120);
            
            $table->softDeletesTz();
            $table->timestampsTz();
        });

        // 6. Solusi: Tambahkan kolom custom ENUM menggunakan raw SQL
        DB::statement('ALTER TABLE enrollments ADD COLUMN semester semester_enum NOT NULL DEFAULT \'GANJIL\';');
        DB::statement('ALTER TABLE enrollments ADD COLUMN status status_enum NOT NULL DEFAULT \'DRAFT\';');

        // 7. Tambahkan Unique Constraint setelah kolom berhasil ditambahkan
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['student_id', 'course_id', 'academic_year', 'semester'], 'enrollments_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('students');
        
        DB::statement('DROP TYPE IF EXISTS status_enum;');
        DB::statement('DROP TYPE IF EXISTS semester_enum;');
    }
};