<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Doctor\ScheduleController;
use App\Http\Controllers\Api\Doctor\DashboardController as DoctorDashboardController;
use App\Http\Controllers\Api\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Api\Doctor\ProfileController as DoctorProfileController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\DoctorProfileController as AdminDoctorProfileController;
use App\Http\Controllers\Api\Admin\AppointmentController as AdminAppointmentController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;

// ── Public ───────────────────────────────────────────────────
Route::post('/login',           [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp',      [AuthController::class, 'verifyOtp']);
Route::post('/reset-password',  [AuthController::class, 'resetPassword']);

Route::get('/doctors',          [SlotController::class, 'doctors']);
Route::get('/services',         [SlotController::class, 'services']);
Route::get('/slots',            [SlotController::class, 'available']);
Route::post('/appointments',    [AppointmentController::class, 'store']);
Route::post('/contact',         [ContactController::class, 'store']);

// ── Authenticated ────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // ── Doctor ───────────────────────────────────────────────
    Route::middleware('is_doctor')->prefix('doctor')->group(function () {

        // Dashboard
        Route::get('/dashboard',                 [DoctorDashboardController::class, 'index']);

        // Profile
        Route::get('/profile',                   [DoctorProfileController::class, 'show']);
        Route::patch('/profile',                 [DoctorProfileController::class, 'update']);
        Route::post('/profile/photo',            [DoctorProfileController::class, 'uploadPhoto']);
        Route::patch('/profile/change-password', [DoctorProfileController::class, 'changePassword']);

        // View other doctors (read-only)
        Route::get('/doctors',                   [DoctorProfileController::class, 'listDoctors']);
        Route::get('/doctors/{id}',              [DoctorProfileController::class, 'viewOtherDoctor']);

        // Schedule — order matters, specific routes before {id} routes
        Route::get('/schedule',                  [ScheduleController::class, 'index']);
        Route::post('/schedule/default',         [ScheduleController::class, 'applyDefault']);
        Route::post('/schedule/bulk',            [ScheduleController::class, 'bulkStore']);
        Route::post('/schedule',                 [ScheduleController::class, 'store']);
        Route::patch('/schedule/{id}',           [ScheduleController::class, 'update']);
        Route::patch('/schedule/{id}/toggle',    [ScheduleController::class, 'toggle']);
        Route::delete('/schedule/clear',         [ScheduleController::class, 'clearDay']);
        Route::delete('/schedule/{id}',          [ScheduleController::class, 'destroy']);

        // Blocked dates
        Route::get('/blocked-dates',             [ScheduleController::class, 'blockedDates']);
        Route::post('/blocked-dates/bulk',       [ScheduleController::class, 'bulkBlockDates']);
        Route::post('/blocked-dates',            [ScheduleController::class, 'blockDate']);
        Route::delete('/blocked-dates/{id}',     [ScheduleController::class, 'unblockDate']);

        // Appointments
        Route::get('/appointments',                      [DoctorAppointmentController::class, 'index']);
        Route::patch('/appointments/{id}/approve',       [DoctorAppointmentController::class, 'approve']);
        Route::patch('/appointments/{id}/reject',        [DoctorAppointmentController::class, 'reject']);
        Route::patch('/appointments/{id}/reschedule',    [DoctorAppointmentController::class, 'reschedule']);
        Route::patch('/appointments/{id}/complete',      [DoctorAppointmentController::class, 'markComplete']);
    });

    // ── Admin ─────────────────────────────────────────────────
    Route::middleware('is_admin')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Users (doctors)
        Route::get('/users',                       [AdminUserController::class, 'index']);
        Route::post('/users',                      [AdminUserController::class, 'store']);
        Route::patch('/users/{id}',                [AdminUserController::class, 'update']);
        Route::patch('/users/{id}/toggle',         [AdminUserController::class, 'toggle']);
        Route::patch('/users/{id}/reset-password', [AdminUserController::class, 'resetToDefault']);
        Route::patch('/users/{id}/restore',        [AdminUserController::class, 'restore']);
        Route::delete('/users/{id}',               [AdminUserController::class, 'destroy']);

        // Doctor profiles
        Route::get('/users/{id}/profile',          [AdminDoctorProfileController::class, 'show']);
        Route::patch('/users/{id}/profile',        [AdminDoctorProfileController::class, 'update']);

        // Services
        Route::get('/services',                    [AdminServiceController::class, 'index']);
        Route::post('/services',                   [AdminServiceController::class, 'store']);
        Route::get('/services/{id}',               [AdminServiceController::class, 'show']);
        Route::patch('/services/{id}',             [AdminServiceController::class, 'update']);
        Route::patch('/services/{id}/toggle',      [AdminServiceController::class, 'toggle']);
        Route::delete('/services/{id}',            [AdminServiceController::class, 'destroy']);

        // Appointments
        Route::get('/appointments',                [AdminAppointmentController::class, 'index']);
        Route::get('/appointments/{id}',           [AdminAppointmentController::class, 'show']);
        Route::patch('/appointments/{id}',         [AdminAppointmentController::class, 'update']);
        Route::delete('/appointments/{id}',        [AdminAppointmentController::class, 'destroy']);

        // Contacts
        Route::get('/contacts',                    [AdminContactController::class, 'index']);
        Route::get('/contacts/{id}',               [AdminContactController::class, 'show']);
        Route::patch('/contacts/{id}/read',        [AdminContactController::class, 'markRead']);
        Route::patch('/contacts/{id}/replied',     [AdminContactController::class, 'markReplied']);
        Route::delete('/contacts/{id}',            [AdminContactController::class, 'destroy']);
    });
});
