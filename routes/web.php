<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataSourceController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema de Continuidad TI
| IESP Cap. FAP José Abelardo Quiñones
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');

    Route::get('/register', [AuthController::class, 'createRegistration'])->name('register');
    Route::post('/register', [AuthController::class, 'storeRegistration'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/forgot-password', [AuthController::class, 'createForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:6,1')->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'createPasswordReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    // Dashboard & Executive Overview
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/arquitectura', [DashboardController::class, 'index'])->name('arquitectura');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password.update');

    // Respaldos y Recuperación
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->middleware('throttle:5,1')->name('backups.store');
    Route::post('/backups/{demoBackup}/restore', [BackupController::class, 'restore'])->middleware('throttle:5,1')->name('backups.restore');
    Route::get('/backups/{demoBackup}/download/{format?}', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/upload-restore', [BackupController::class, 'uploadRestore'])->name('backups.upload-restore');
    Route::post('/backups/schedule', [BackupController::class, 'updateSchedule'])->name('backups.schedule');
    Route::post('/backups/run-now', [BackupController::class, 'runScheduledNow'])->name('backups.run-now');
    Route::post('/backups/browse-folder', [BackupController::class, 'browseFolder'])->name('backups.browse-folder');
    Route::get('/backups/runbook', [BackupController::class, 'runbook'])->name('backups.runbook');

    // Backwards-compatibility aliases for existing tests & links
    Route::post('/demo-backups', [BackupController::class, 'store'])->middleware('throttle:5,1')->name('demo-backups.store');
    Route::post('/demo-backups/{demoBackup}/restore', [BackupController::class, 'restore'])->middleware('throttle:5,1')->name('demo-backups.restore');

    // Fuentes de Datos Críticas Institucionales
    Route::get('/data-sources', [DataSourceController::class, 'index'])->name('data-sources.index');

    // Auditoría de Seguridad y Eventos
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

    // Ficha del Proyecto de Innovación (Extracción alva.docx)
    Route::get('/project', [ProjectController::class, 'index'])->name('project.index');

    // Gestión Académica de Estudiantes
    Route::post('/estudiantes/seed-demo', [EstudianteController::class, 'seedDemo'])->name('estudiantes.seed-demo');
    Route::resource('estudiantes', EstudianteController::class);
});
