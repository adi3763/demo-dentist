<?php
namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendAppointmentReminders extends Command
{
    protected $signature   = 'appointments:remind';
    protected $description = 'Send WhatsApp reminders 1 hour before appointments';

    public function handle(WhatsAppService $whatsapp): void
    {
        $targetTime = Carbon::now()->addHour()->format('H:i:00');
        $today      = Carbon::today()->toDateString();

        // Find all appointments exactly 1 hour from now that haven't been reminded
        $appointments = Appointment::where('appointment_date', $today)
                                   ->where('start_time', $targetTime)
                                   ->whereIn('status', ['pending', 'confirmed'])
                                   ->where('reminder_sent', false)
                                   ->with(['doctor', 'service'])
                                   ->get();

        foreach ($appointments as $appointment) {
            $formattedTime = Carbon::parse($appointment->start_time)->format('h:i A');
            $doctorName    = $appointment->doctor->name;
            $serviceName   = $appointment->service?->name ?? 'Dental Appointment';

            // Remind patient
            $whatsapp->send(
                $appointment->patient_phone,
                "Reminder! Your appointment is in 1 hour.\n\n" .
                "Doctor: {$doctorName}\n" .
                "Service: {$serviceName}\n" .
                "Time: {$formattedTime}\n\n" .
                "Please be on time!"
            );

            // Remind doctor
            $whatsapp->send(
                $appointment->doctor->phone,
                "Upcoming appointment in 1 hour:\n\n" .
                "Patient: {$appointment->patient_name}\n" .
                "Phone: {$appointment->patient_phone}\n" .
                "Service: {$serviceName}\n" .
                "Time: {$formattedTime}"
            );

            $appointment->update(['reminder_sent' => true]);
        }

        $this->info("Reminders sent for {$appointments->count()} appointments.");
    }
}
