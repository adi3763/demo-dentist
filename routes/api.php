<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\FeedDataController;
use App\Http\Controllers\Api\Doctor\ScheduleController;
use App\Http\Controllers\Api\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Api\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\DoctorProfileController as AdminDoctorProfileController;

// ── Public ───────────────────────────────────────────────────
Route::post('/login',           [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp',      [AuthController::class, 'verifyOtp']);
Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
Route::match(['get', 'post'], '/feed-data', FeedDataController::class);

Route::get('/doctors',          [SlotController::class, 'doctors']);
Route::get('/services',         [SlotController::class, 'services']);
Route::get('/slots',            [SlotController::class, 'available']);
Route::post('/appointments',    [AppointmentController::class, 'store']);
Route::post('/contact',         [ContactController::class, 'store']);

// ── Authenticated ────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Doctor list is visible to admins and doctors; write actions remain admin-only.
    Route::middleware('is_doctor')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
    });

    // ── Doctor ───────────────────────────────────────────────
    Route::middleware('is_doctor')->prefix('doctor')->group(function () {

        // Profile
        Route::get('/profile',              [DoctorProfileController::class, 'show']);
        Route::patch('/profile',            [DoctorProfileController::class, 'update']);
        Route::post('/profile/photo',       [DoctorProfileController::class, 'uploadPhoto']);
        Route::patch('/profile/change-password', [DoctorProfileController::class, 'changePassword']);

        // Schedule
        Route::get('/schedule',             [ScheduleController::class, 'index']);
        Route::post('/schedule',            [ScheduleController::class, 'store']);
        Route::delete('/schedule/{id}',     [ScheduleController::class, 'destroy']);
        Route::post('/blocked-dates',       [ScheduleController::class, 'blockDate']);
        Route::delete('/blocked-dates/{id}',[ScheduleController::class, 'unblockDate']);

        // Appointments
        Route::get('/appointments',                   [DoctorAppointmentController::class, 'index']);
        Route::patch('/appointments/{id}/complete',   [DoctorAppointmentController::class, 'markComplete']);
        Route::patch('/appointments/{id}/reschedule', [DoctorAppointmentController::class, 'reschedule']);
    });

    // ── Admin ────────────────────────────────────────────────
    Route::middleware('is_admin')->prefix('admin')->group(function () {

        // Doctor account management
        Route::post('/users',                   [AdminUserController::class, 'store']);
        Route::patch('/users/{id}',             [AdminUserController::class, 'update']);
        Route::patch('/users/{id}/toggle',      [AdminUserController::class, 'toggle']);
        Route::delete('/users/{id}',            [AdminUserController::class, 'destroy']);
        Route::patch('/users/{id}/restore',     [AdminUserController::class, 'restore']);
        Route::patch('/users/{id}/reset-password', [AdminUserController::class, 'resetToDefault']);

        // Doctor profile — admin view/edit
        Route::get('/users/{id}/profile',       [AdminDoctorProfileController::class, 'show']);
        Route::patch('/users/{id}/profile',     [AdminDoctorProfileController::class, 'update']);
    });
});
