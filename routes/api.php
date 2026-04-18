<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;


// ── Public (no token needed) ─────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

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

    // ── Doctor (and admin) ───────────────────────────────────
    Route::middleware('is_doctor')->prefix('doctor')->group(function () {
        // We'll add these in the next steps:
        // GET  /api/doctor/appointments  (their schedule)
        // PATCH /api/doctor/appointments/{id}  (mark complete)
    });
});

Route::get('/debug/users', function () {
    return \App\Models\User::all();
});
