<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public patient-facing routes
Route::get('/', [PatientController::class, 'index'])->name('home');

// Returning patient
Route::get('/verify', [PatientController::class, 'verify'])->name('patient.verify');
Route::post('/verify/confirm', [PatientController::class, 'confirm'])->name('patient.confirm');

// New patient registration
Route::get('/register', [RegistrationController::class, 'create'])->name('patient.register');
Route::post('/register', [RegistrationController::class, 'store'])->name('patient.store');

// Queue workflow (requires patient in session)
Route::middleware('patient.session')->group(function () {
    Route::get('/queue/select', [QueueController::class, 'select'])->name('queue.select');
    Route::post('/queue/generate', [QueueController::class, 'generate'])->name('queue.generate');
    Route::get('/queue/ticket/{registration}', [QueueController::class, 'ticket'])->name('queue.ticket');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Admin routes
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::resource('polyclinics', Admin\PolyclinicController::class);
    Route::resource('doctors', Admin\DoctorController::class);
    Route::resource('practice-schedules', Admin\PracticeScheduleController::class);
});
