<p>Hello {{ $submission->name }},</p>

<p>We received your message and our team will contact you shortly.</p>

<p><strong>Service Enquired:</strong> {{ $serviceName }}</p>

@if ($submission->message)
<p><strong>Your Message:</strong><br>{{ $submission->message }}</p>
@endif

<p>For urgent queries, please call us directly.</p>

<p>Thank you,<br>{{ config('app.name') }}</p>
