<x-mail::message>
# New contact form message

You have received a new message from the contact form on **{{ \App\Models\SiteSetting::storeName() }}**.

**Name:** {{ $contactMessage->name }}<br>
@if($contactMessage->email)
**Email:** {{ $contactMessage->email }}<br>
@endif
@if($contactMessage->phone)
**Phone:** {{ $contactMessage->phone }}<br>
@endif
@if($contactMessage->subject)
**Subject:** {{ $contactMessage->subject }}
@endif

**Message:**

{{ $contactMessage->message }}

<x-mail::subcopy>
IP: {{ $contactMessage->ip_address ?? 'N/A' }} · Received {{ $contactMessage->created_at->format('d M Y, h:i A') }}
</x-mail::subcopy>
</x-mail::message>
