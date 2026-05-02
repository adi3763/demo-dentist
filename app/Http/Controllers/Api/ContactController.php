<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactSubmissionAcknowledgementMail;
use App\Mail\ContactSubmissionNotificationMail;
use App\Models\ContactSubmission;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function __construct(protected WhatsAppService $whatsapp)
    {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'service_id' => 'nullable|exists:services,id',
            'message' => 'nullable|string|max:1000',
        ]);

        $submission = ContactSubmission::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'service_id' => $validated['service_id'] ?? null,
            'message' => $validated['message'] ?? null,
            'ip_address' => $request->ip(),
            'status' => 'new',
        ]);

        $submission->load('service');
        $serviceName = $submission->service?->name ?? 'Not specified';

        $doctorPhoneRecipients = User::query()
            ->where('role', 'doctor')
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->get();

        $emailRecipients = User::query()
            ->where('role', 'doctor')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($fallbackEmail = config('services.contact.notification_email')) {
            $emailRecipients->push($fallbackEmail);
            $emailRecipients = $emailRecipients->unique()->values();
        }

        $notifications = [
            'whatsapp' => [
                'customer' => false,
                'doctors_sent' => 0,
            ],
            'email' => [
                'customer' => false,
                'doctors_sent' => 0,
            ],
        ];

        $notifications['whatsapp']['customer'] = $this->whatsapp->send(
            $submission->phone,
            "Hello {$submission->name}! We received your message.\n\n" .
            "Service Enquired: {$serviceName}\n\n" .
            "Our team will contact you shortly. " .
            "For urgent queries call us directly.\n\n" .
            "Thank you for reaching out to us!"
        );

        foreach ($doctorPhoneRecipients as $doctor) {
            if ($this->whatsapp->send(
                $doctor->phone,
                "New contact form submission!\n\n" .
                "Name: {$submission->name}\n" .
                "Phone: {$submission->phone}\n" .
                "Email: {$submission->email}\n" .
                "Service: {$serviceName}\n" .
                "Message: " . ($submission->message ?? 'No message') . "\n" .
                "IP: {$submission->ip_address}"
            )) {
                $notifications['whatsapp']['doctors_sent']++;
            }
        }

        try {
            Mail::to($submission->email)->send(
                new ContactSubmissionAcknowledgementMail($submission, $serviceName)
            );
            $notifications['email']['customer'] = true;
        } catch (\Throwable $e) {
            Log::error('Contact acknowledgement email failed.', [
                'submission_id' => $submission->id,
                'email' => $submission->email,
                'error' => $e->getMessage(),
            ]);
        }

        foreach ($emailRecipients as $recipient) {
            try {
                Mail::to($recipient)->send(
                    new ContactSubmissionNotificationMail($submission, $serviceName)
                );
                $notifications['email']['doctors_sent']++;
            } catch (\Throwable $e) {
                Log::error('Contact notification email failed.', [
                    'submission_id' => $submission->id,
                    'email' => $recipient,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Thank you! We will get back to you shortly.',
            'notifications' => $notifications,
        ], 201);
    }
}
