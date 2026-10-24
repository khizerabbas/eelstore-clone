<x-mail::message>
# {{ $reopened ? 'An error is back' : 'New error on the website' }}

@if($reopened)
This error was marked as resolved, but it has just happened again.
@else
The website recorded an error it hasn't seen before. You won't get another email for this error unless it is resolved and comes back.
@endif

<x-mail::panel>
**{{ $error->message }}**
</x-mail::panel>

**Where:** {{ $error->url ?: ($error->file ? $error->file . ':' . $error->line : '—') }}<br>
**Type:** {{ $error->type }} ({{ $error->source }})<br>
@if(! empty($error->context['order']))
**Order:** {{ $error->context['order'] }}<br>
@endif
**When:** {{ $error->last_seen_at?->format('d M Y, h:i A') }}

<x-mail::button :url="$url">
Open the Error log
</x-mail::button>

If you're not sure what this means, forward this email to your developer.
</x-mail::message>
