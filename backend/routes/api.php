<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EnrollmentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/enrollments/export-csv', [EnrollmentController::class, 'exportCsv']);
Route::get('/enrollments', [EnrollmentController::class, 'index']);
Route::post('/enrollments', [EnrollmentController::class, 'store']);
Route::get('/enrollments/export', [EnrollmentController::class, 'export']);
Route::apiResource('enrollments', EnrollmentController::class)->except(['show']);
// Route::get('/enrollments/export', [EnrollmentController::class, 'export']);
Route::put('/enrollments/{id}', [EnrollmentController::class, 'update']);
Route::delete('/enrollments/{id}', [EnrollmentController::class, 'destroy']);
