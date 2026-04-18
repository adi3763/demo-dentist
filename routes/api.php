<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Artisan;


// ── Seeding endpoint (admin only) ────────────────────────────
Route::post('/run-seed', function () {
    try {
        Artisan::call('db:seed', ['--force' => true]);
        $output = Artisan::output();
        
        // Check if users were actually created
        $userCount = \App\Models\User::count();
        
        return response()->json([
            'message' => 'Database seeded successfully!',
            'status'  => 'success',
            'user_count' => $userCount,
            'artisan_output' => $output
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Seeding failed: ' . $e->getMessage(),
            'status'  => 'error',
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// ── Migration endpoint ───────────────────────────────────────
Route::post('/run-migrate', function () {
    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();
        
        return response()->json([
            'message' => 'Migrations run successfully!',
            'status'  => 'success',
            'artisan_output' => $output
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Migration failed: ' . $e->getMessage(),
            'status'  => 'error',
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

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
