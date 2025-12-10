@component('mail::message')
    # New Contact Form Submission

    You have received a new message from the contact form on **Usman Electronics**.

    **Name:** {{ $contactMessage->name }}

    **Email:** {{ $contactMessage->email }}

    @if($contactMessage->phone)
        **Phone:** {{ $contactMessage->phone }}
    @endif

    @if($contactMessage->subject)
        **Subject:** {{ $contactMessage->subject }}
    @endif

    **Message:**

    {{ $contactMessage->message }}

    ---

    IP Address: {{ $contactMessage->ip_address ?? 'N/A' }}
    Submitted at: {{ $contactMessage->created_at->format('Y-m-d H:i') }}

@endcomponent
