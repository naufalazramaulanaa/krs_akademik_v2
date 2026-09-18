<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedMassiveEnrollments extends Command
{
    protected $signature = 'db:seed-massive-enrollments';
    protected $description = 'Melakukan seeding 5 juta data KRS dengan optimasi drop/recreate index';

    public function handle()
    {
        $this->info('Persiapan: Mengosongkan tabel dan menghapus indeks sementara...');
        $startTime = microtime(true);

        // 1. Kosongkan tabel terlebih dahulu
        DB::statement('TRUNCATE TABLE enrollments, students, courses RESTART IDENTITY CASCADE;');

        // 2. DROP INDEX / FOREIGN KEY YANG TIDAK PERLU SELAMA SEEDING BERLANGSUNG
        DB::statement('DROP INDEX IF EXISTS enrollments_student_id_index;');
        DB::statement('DROP INDEX IF EXISTS enrollments_course_id_index;');
        DB::statement('DROP INDEX IF EXISTS students_nim_index;');

        $this->info('Indeks berhasil dibersihkan. Memulai seeding 5 juta data...');

        // Pool Nama Manusia Alami (Tanpa Angka)
        $firstNames = ['Ahmad', 'Muhammad', 'Siti', 'Nur', 'Dewi', 'Budi', 'Eko', 'Rizky', 'Fitri', 'Indah', 'Reza', 'Yoga', 'Dimas', 'Citra', 'Sarah', 'Rian', 'Agung', 'Putri', 'Mega', 'Fajar', 'Bayu', 'Annisa', 'Dwi', 'Rahmat'];
        $middleNames = ['Pratama', 'Hidayat', 'Kurniawan', 'Santoso', 'Lestari', 'Ramadhan', 'Utama', 'Wijaya', 'Setiawan', 'Fauzi', 'Rahmawati', 'Siregar', 'Cahyono'];
        $lastNames = ['Putra', 'Putri', 'Susanto', 'Wijaya', 'Nugroho', 'Saputra', 'Wulandari', 'Hidayat', 'Maulana', 'Kusuma', 'Pertiwi', 'Sari', 'Pangestu'];

        // Pool Mata Kuliah
        $courses = [
            ['code' => 'IF101', 'name' => 'Pemrograman Berorientasi Objek', 'credits' => 3],
            ['code' => 'IF102', 'name' => 'Pemrograman Web', 'credits' => 3],
            ['code' => 'IF103', 'name' => 'Grafika Komputer', 'credits' => 2],
            ['code' => 'IF104', 'name' => 'Kecerdasan Buatan', 'credits' => 3],
            ['code' => 'IF105', 'name' => 'Sistem Terdistribusi', 'credits' => 3],
            ['code' => 'IF106', 'name' => 'Rekayasa Perangkat Lunak', 'credits' => 3],
            ['code' => 'IF107', 'name' => 'Algoritma dan Pemrograman', 'credits' => 4],
            ['code' => 'IF108', 'name' => 'Interaksi Manusia dan Komputer', 'credits' => 3],
            ['code' => 'IF109', 'name' => 'Struktur Data', 'credits' => 3],
            ['code' => 'IF110', 'name' => 'Keamanan Informasi', 'credits' => 3],
        ];

        $totalRows = 5000000;
        $chunkSize = 1000; 
        $bar = $this->output->createProgressBar($totalRows);
        $bar->start();

        for ($i = 0; $i < $totalRows; $i += $chunkSize) {
            $studentsData = [];
            $coursesData = [];
            $now = Carbon::now();

            for ($j = 0; $j < $chunkSize; $j++) {
                $currentIndex = $i + $j;
                if ($currentIndex >= $totalRows) break;

                // 1. Generate Nama Unik Tanpa Angka
                $f = $firstNames[$currentIndex % count($firstNames)];
                $m = $middleNames[floor($currentIndex / count($firstNames)) % count($middleNames)];
                $l = $lastNames[($currentIndex * 31) % count($lastNames)];
                $studentName = "{$f} {$m} {$l}";

                $nim = str_pad(10000000 + $currentIndex, 8, '0', STR_PAD_LEFT);
                $email = "mhs{$currentIndex}@kampus.ac.id";

                $studentsData[] = [
                    'nim' => $nim,
                    'name' => $studentName,
                    'email' => $email,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $course = $courses[$currentIndex % count($courses)];
                
                // Ambil 2 huruf pertama, gabungkan dengan base36 index (panjang pas 7 karakter, aman dari VARCHAR(7))
                $prefix = substr($course['code'], 0, 2);
                $uniqueCode = $prefix . str_pad(strtoupper(base_convert($currentIndex, 10, 36)), 5, '0', STR_PAD_LEFT);

                $coursesData[] = [
                    'code' => $uniqueCode, 
                    'name' => $course['name'],
                    'credits' => $course['credits'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Eksekusi Batch Insert
            DB::transaction(function () use ($studentsData, $coursesData, $now) {
                DB::table('students')->insert($studentsData);
                DB::table('courses')->insert($coursesData);

                $insertedStudents = DB::table('students')
                    ->orderBy('id', 'desc')
                    ->limit(count($studentsData))
                    ->get(['id', 'nim', 'name'])
                    ->reverse()
                    ->values();

                // Ambil ID, Code, dan Name mata kuliah yang baru di-insert
                $insertedCourses = DB::table('courses')
                    ->orderBy('id', 'desc')
                    ->limit(count($coursesData))
                    ->get(['id', 'code', 'name'])
                    ->reverse()
                    ->values();

                $enrollmentsData = [];
                $statuses = ['DRAFT', 'SUBMITTED', 'APPROVED', 'REJECTED'];
                $semesters = ['GANJIL', 'GENAP'];

                for ($k = 0; $k < count($insertedStudents); $k++) {
                    $enrollmentsData[] = [
                        'student_id' => $insertedStudents[$k]->id,
                        'student_nim' => $insertedStudents[$k]->nim,
                        'student_name' => $insertedStudents[$k]->name,
                        'course_id' => $insertedCourses[$k]->id,
                        'course_code' => $insertedCourses[$k]->code,
                        'course_name' => $insertedCourses[$k]->name, // <-- Ditambahkan agar tidak null
                        'academic_year' => '2025/2026',
                        'semester' => $semesters[$k % count($semesters)],
                        'status' => $statuses[$k % count($statuses)],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('enrollments')->insert($enrollmentsData);
            });

            $bar->advance($chunkSize);
        }

        $bar->finish();
        $this->newLine();
        $this->info('Seeding data selesai. Membangun kembali (Rebuilding) Indeks Database...');

        // 3. RECREATE INDEX KEMBALI SETELAH SEEDING SELESAI
        DB::statement('CREATE INDEX enrollments_student_id_index ON enrollments(student_id);');
        DB::statement('CREATE INDEX enrollments_course_id_index ON enrollments(course_id);');
        DB::statement('CREATE INDEX students_nim_index ON students(nim);');

        $this->info('Semua indeks berhasil dibangun kembali!');
        $this->info('Total waktu eksekusi: ' . round(microtime(true) - $startTime, 2) . ' detik!');
    }
}