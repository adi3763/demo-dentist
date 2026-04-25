<?php
namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected Client $twilio;
    protected string $from;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->from = config('services.twilio.whatsapp_from');
    }

    public function send(string $toPhone, string $message): bool
    {
        try {
            // Ensure phone has country code — add +91 if missing
            $phone = $this->formatPhone($toPhone);

            $this->twilio->messages->create(
                "whatsapp:{$phone}",
                [
                    'from' => $this->from,
                    'body' => $message,
                ]
            );

            return true;

        } catch (\Exception $e) {
            // Don't crash the app if WhatsApp fails — just log it
            Log::error('WhatsApp send failed: ' . $e->getMessage(), [
                'to'      => $toPhone,
                'message' => $message,
            ]);
            return false;
        }
    }

    private function formatPhone(string $phone): string
    {
        // Remove spaces, dashes, brackets
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Add +91 if no country code
        if (! str_starts_with($phone, '+')) {
            $phone = '+91' . $phone;
        }

        return $phone;
    }
}
