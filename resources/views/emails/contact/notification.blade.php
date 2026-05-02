<p>A new contact form submission was received.</p>

<p><strong>Name:</strong> {{ $submission->name }}</p>
<p><strong>Email:</strong> {{ $submission->email }}</p>
<p><strong>Phone:</strong> {{ $submission->phone }}</p>
<p><strong>Service:</strong> {{ $serviceName }}</p>
<p><strong>IP:</strong> {{ $submission->ip_address ?? 'Unavailable' }}</p>

@if ($submission->message)
<p><strong>Message:</strong><br>{{ $submission->message }}</p>
@else
<p><strong>Message:</strong> No message provided.</p>
@endif
