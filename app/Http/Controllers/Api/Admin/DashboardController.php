<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    // GET /api/admin/dashboard
    public function index()
    {
        $today = Carbon::today();

        // ── Appointment stats ────────────────────────────────
        $totalAppointments   = Appointment::count();
        $todayAppointments   = Appointment::whereDate('appointment_date', $today)->count();
        $pendingAppointments = Appointment::where('status', 'pending')->count();
        $confirmedToday      = Appointment::whereDate('appointment_date', $today)
                                          ->where('status', 'confirmed')
                                          ->count();
        $completedToday      = Appointment::whereDate('appointment_date', $today)
                                          ->where('status', 'completed')
                                          ->count();
        $thisMonthTotal      = Appointment::whereMonth('appointment_date', $today->month)
                                          ->whereYear('appointment_date', $today->year)
                                          ->count();

        // ── Contact stats ────────────────────────────────────
        $totalContacts  = ContactSubmission::count();
        $newContacts    = ContactSubmission::where('status', 'new')->count();

        // ── Doctor stats ─────────────────────────────────────
        $totalDoctors   = User::where('role', 'doctor')->count();
        $activeDoctors  = User::where('role', 'doctor')->where('is_active', true)->count();

        // ── Today's appointments list ────────────────────────
        $todayList = Appointment::whereDate('appointment_date', $today)
                                ->with(['doctor:id,name', 'service:id,name'])
                                ->orderBy('start_time')
                                ->get()
                                ->map(fn($a) => [
                                    'id'           => $a->id,
                                    'patient_name' => $a->patient_name,
                                    'patient_phone'=> $a->patient_phone,
                                    'doctor'       => $a->doctor?->name,
                                    'service'      => $a->service?->name,
                                    'start_time'   => Carbon::parse($a->start_time)->format('h:i A'),
                                    'status'       => $a->status,
                                ]);

        // ── Last 7 days booking chart data ───────────────────
        $last7Days = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return [
                'date'  => $date->format('d M'),
                'count' => Appointment::whereDate('appointment_date', $date)->count(),
            ];
        });

        // ── Recent contact submissions ───────────────────────
        $recentContacts = ContactSubmission::latest()
                                           ->take(5)
                                           ->get(['id','name','email','phone','status','created_at']);

        // ── Recent activity logs ─────────────────────────────
        $recentActivities = \App\Models\ActivityLog::with('user:id,name,role')
                                           ->latest()
                                           ->take(15)
                                           ->get();

        return response()->json([
            'stats' => [
                'appointments' => [
                    'total'          => $totalAppointments,
                    'today'          => $todayAppointments,
                    'pending'        => $pendingAppointments,
                    'confirmed_today'=> $confirmedToday,
                    'completed_today'=> $completedToday,
                    'this_month'     => $thisMonthTotal,
                ],
                'contacts' => [
                    'total' => $totalContacts,
                    'new'   => $newContacts,
                ],
                'doctors' => [
                    'total'  => $totalDoctors,
                    'active' => $activeDoctors,
                ],
            ],
            'today_appointments' => $todayList,
            'chart_last_7_days'  => $last7Days,
            'recent_contacts'    => $recentContacts,
            'recent_activities'  => $recentActivities,
        ]);
    }
}
