<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedMassiveCommand extends Command
{
    protected $signature = 'seed:massive 
                            {--students=200000 : Jumlah mahasiswa} 
                            {--courses=2000 : Jumlah mata kuliah} 
                            {--enrollments=5000000 : Jumlah total KRS} 
                            {--truncate : Hapus data lama sebelum seed}';

    protected $description = 'Seed database dengan jutaan data menggunakan raw SQL (Bulk Insert) dengan nama asli Indonesia yang unik';

    public function handle()
    {
        $start = microtime(true);
        $totalStudents = (int) $this->option('students');
        $totalCourses = (int) $this->option('courses');
        $totalEnrollments = (int) $this->option('enrollments');
        
        // 1. Truncate jika flag --truncate digunakan
        if ($this->option('truncate')) {
            $this->warn('Menghapus data lama (Truncate)...');
            DB::statement('TRUNCATE TABLE enrollments, courses, students RESTART IDENTITY CASCADE');
        }

        // 2. Seed Courses (Batch Bulk Insert) dengan nama mata kuliah realistis
        $this->info("Menyuntikkan {$totalCourses} Courses...");
        DB::statement("
            INSERT INTO courses (code, name, credits, created_at, updated_at)
            SELECT 
                'IF' || LPAD(i::text, 4, '0'), 
                (ARRAY['Algoritma dan Pemrograman', 'Struktur Data', 'Basis Data Relasional', 'Sistem Operasi', 'Jaringan Komputer', 'Pemrograman Web', 'Kecerdasan Buatan', 'Rekayasa Perangkat Lunak', 'Interaksi Manusia dan Komputer', 'Keamanan Informasi', 'Matematika Diskrit', 'Statistika Probabilitas', 'Pemrograman Berorientasi Objek', 'Grafika Komputer', 'Sistem Terdistribusi'])[((i - 1) % 15) + 1] || ' ' || i, 
                (i % 5) + 1, 
                NOW(), NOW()
            FROM generate_series(1, ?) AS i
        ", [$totalCourses]);

        // 3. Seed Students dengan nama asli manusia Indonesia yang unik
        $this->info("Menyuntikkan {$totalStudents} Students dengan nama asli Indonesia yang unik...");
        DB::statement("
            INSERT INTO students (nim, name, email, created_at, updated_at)
            SELECT 
                LPAD(i::text, 10, '0'), 
                (ARRAY['Naufal', 'Rizky', 'Ahmad', 'Muhammad', 'Siti', 'Nurul', 'Dwi', 'Putri', 'Budi', 'Andi', 'Fajar', 'Dewi', 'Rian', 'Putra', 'Intan', 'Reza', 'Sarah', 'Dimas', 'Citra', 'Yoga', 'Eko', 'Agus', 'Wahyudi', 'Hendra', 'Surya', 'Fitri', 'Mega', 'Bayu', 'Arya', 'Wahyu'])[(i % 30) + 1] || ' ' ||
                (ARRAY['Maulana', 'Pratama', 'Saputra', 'Wijaya', 'Kusuma', 'Hidayat', 'Santoso', 'Gunawan', 'Permana', 'Nugroho', 'Utama', 'Siregar', 'Lestari', 'Rahmawati', 'Puspita', 'Firmansyah', 'Setiawan', 'Ramadhan', 'Hidayatullah', 'Kurniawan'])[((i * 7) % 20) + 1] || ' ' || i, 
                'student' || i || '@example.com', 
                NOW(), NOW()
            FROM generate_series(1, ?) AS i
        ", [$totalStudents]);

        // 4. Seed Enrollments Secara Bertahap (Batching)
        $enrollmentsPerStudent = (int) ceil($totalEnrollments / $totalStudents);
        $batchSize = 10000;

        $this->info("Menyuntikkan {$totalEnrollments} Enrollments (KRS) secara bertahap...");
        $bar = $this->output->createProgressBar(ceil($totalStudents / $batchSize));
        $bar->start();

        for ($i = 0; $i < $totalStudents; $i += $batchSize) {
            $startId = $i + 1;
            $endId = $i + $batchSize;

            DB::statement("
                INSERT INTO enrollments (student_id, course_id, academic_year, semester, status, student_nim, student_name, course_code, course_name, created_at, updated_at)
                SELECT 
                    s.id, 
                    c.id,
                    CASE WHEN k.k % 2 = 0 THEN '2025/2026' ELSE '2024/2025' END,
                    (CASE WHEN k.k % 3 = 0 THEN 'GANJIL' ELSE 'GENAP' END)::semester_enum,
                    (CASE k.k % 4 WHEN 0 THEN 'DRAFT' WHEN 1 THEN 'SUBMITTED' WHEN 2 THEN 'APPROVED' ELSE 'REJECTED' END)::status_enum,
                    s.nim, s.name, c.code, c.name, NOW(), NOW()
                FROM students s
                CROSS JOIN generate_series(0, ?) AS k(k)
                JOIN courses c ON c.id = ((s.id * 7 + k.k * 13) % ?) + 1
                WHERE s.id BETWEEN ? AND ?
            ", [$enrollmentsPerStudent - 1, $totalCourses, $startId, $endId]);

            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // 5. Build Index setelah data masuk agar performa tetap prima
        $this->info('Membangun Database Index (B-Tree & GIN) paska-seeding...');
        DB::unprepared("
            CREATE INDEX IF NOT EXISTS idx_enr_id ON enrollments (id) WHERE deleted_at IS NULL;
            CREATE INDEX IF NOT EXISTS idx_enr_status_sem_year ON enrollments (status, semester, academic_year, id) WHERE deleted_at IS NULL;
            CREATE INDEX IF NOT EXISTS idx_enr_year_sem_nim ON enrollments (academic_year DESC, semester ASC, student_nim ASC) WHERE deleted_at IS NULL;
            CREATE INDEX IF NOT EXISTS idx_enr_nim_pattern ON enrollments (student_nim varchar_pattern_ops);
            CREATE INDEX IF NOT EXISTS idx_enr_code_pattern ON enrollments (course_code varchar_pattern_ops);
            CREATE INDEX IF NOT EXISTS idx_enr_name_trgm ON enrollments USING GIN (student_name gin_trgm_ops);
            CREATE INDEX IF NOT EXISTS idx_enr_nim_trgm  ON enrollments USING GIN (student_nim gin_trgm_ops);
            CREATE INDEX IF NOT EXISTS idx_enr_code_trgm ON enrollments USING GIN (course_code gin_trgm_ops);
            CREATE INDEX IF NOT EXISTS idx_enr_student ON enrollments (student_id);
            CREATE INDEX IF NOT EXISTS idx_enr_course  ON enrollments (course_id);
        ");

        // 6. Jalankan ANALYZE untuk update statistik query planner
        $this->warn('Menjalankan ANALYZE...');
        DB::statement('ANALYZE enrollments;');

        $duration = round(microtime(true) - $start, 2);
        $count = DB::table('enrollments')->count();
        
        $this->info("SELESAI! Total waktu eksekusi: {$duration} detik.");
        $this->info("Total data diverifikasi di tabel enrollments: " . number_format($count) . " baris.");
    }
}