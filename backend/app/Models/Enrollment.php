<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'enrollments';

    // TAMBAHKAN SEMUA KOLOM INI AGAR TIDAK DIABAIKAN OLEH ELOQUENT
    protected $fillable = [
        'student_id',
        'course_id',
        'student_nim',
        'student_name',
        'course_code',
        'course_name',
        'academic_year',
        'semester',
        'status',
    ];

    // Atau jika tidak ingin repot mendaftarkan satu-satu, Anda bisa ganti $fillable dengan:
    // protected $guarded = [];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}