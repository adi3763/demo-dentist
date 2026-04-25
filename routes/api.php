<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\Doctor\ScheduleController;
use App\Http\Controllers\Api\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;


// ── Public (no token needed) ─────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

Route::get('/doctors',          [SlotController::class, 'doctors']);
Route::get('/services',         [SlotController::class, 'services']);
Route::get('/slots',            [SlotController::class, 'available']);
Route::get('/appointments',     [AppointmentController::class, 'index']);
Route::post('/appointments',    [AppointmentController::class, 'store']);

// ── Any logged in user (admin or doctor) ─────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // ── Admin only ───────────────────────────────────────────
    Route::middleware('is_admin')->prefix('admin')->group(function () {
        // We'll add these routes in the next steps:
        // GET  /api/admin/dashboard
        // GET  /api/admin/users          (manage doctors)
        // POST /api/admin/users          (create doctor account)
        // GET  /api/admin/appointments
        // GET  /api/admin/contacts
    });

    // ── Doctor routes ────────────────────────────────────────
    Route::middleware('is_doctor')->prefix('doctor')->group(function () {
        Route::get('/schedule',                          [ScheduleController::class, 'index']);
        Route::post('/schedule',                         [ScheduleController::class, 'store']);
        Route::delete('/schedule/{id}',                  [ScheduleController::class, 'destroy']);
        Route::post('/blocked-dates',                    [ScheduleController::class, 'blockDate']);
        Route::delete('/blocked-dates/{id}',             [ScheduleController::class, 'unblockDate']);

        Route::get('/appointments',                      [DoctorAppointmentController::class, 'index']);
        Route::patch('/appointments/{id}/complete',      [DoctorAppointmentController::class, 'markComplete']);
        Route::get('/appointments/{id}/reschedule',      [DoctorAppointmentController::class, 'rescheduleInfo']);
        Route::patch('/appointments/{id}/reschedule',    [DoctorAppointmentController::class, 'reschedule']);
    });

        // ── Admin routes ─────────────────────────────────────────
    Route::middleware('is_admin')->prefix('admin')->group(function () {
        Route::get('/users',               [AdminUserController::class, 'index']);
        Route::post('/users',              [AdminUserController::class, 'store']);
        Route::patch('/users/{id}',        [AdminUserController::class, 'update']);
        Route::patch('/users/{id}/toggle', [AdminUserController::class, 'toggle']);
        Route::delete('/users/{id}',       [AdminUserController::class, 'destroy']);
    });
});

Route::get('/debug/users', function () {
    return \App\Models\User::all();
});
