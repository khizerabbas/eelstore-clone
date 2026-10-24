@props(['name', 'class' => 'w-5 h-5'])
{{-- Small outline icons used by editable storefront blocks (benefits, socials). Unknown names render a dot. --}}
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('shield')
            <path d="M12 3l7 3v5c0 4.5-3 8.5-7 10-4-1.5-7-5.5-7-10V6l7-3z"/><path d="M9 12l2 2 4-4"/>
            @break
        @case('tag')
            <path d="M3 12V4a1 1 0 011-1h8l9 9-9 9-9-9z"/><circle cx="7.5" cy="7.5" r="1.2"/>
            @break
        @case('cash')
            <rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v.01M18 15v.01"/>
            @break
        @case('phone')
            <path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a1 1 0 01-1 1A16 16 0 014 5a1 1 0 011-1z"/>
            @break
        @case('truck')
            <path d="M3 6h11v10H3zM14 9h4l3 3v4h-7z"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/>
            @break
        @case('star')
            <path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.8l-5.2 2.8 1-5.8L3.5 9.7l5.9-.9L12 3.5z"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
        @case('check')
            <circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>
            @break
        @case('bolt')
            <path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z"/>
            @break
        @case('wrench')
            <path d="M14.5 6.5a4 4 0 00-5.4 5.1L3.5 17.2a1.8 1.8 0 002.6 2.6l5.6-5.6a4 4 0 005.1-5.4l-2.4 2.4-2.3-.5-.5-2.3 2.4-2.4z"/>
            @break
        @case('location')
            <path d="M12 21s-7-6.2-7-11.5A7 7 0 0119 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6l8.5 7 8.5-7"/>
            @break
        @case('whatsapp')
            <path d="M4 20l1.3-3.8A8 8 0 1112 20a8 8 0 01-4.2-1.2L4 20z"/><path d="M9 9.5c0 2.5 2.5 5 5 5l1-1.2-1.8-.9-.8.8c-.9-.4-1.7-1.2-2.1-2.1l.8-.8-.9-1.8L9 9.5z"/>
            @break
        @case('facebook')
            <path d="M14 8h2.5V4.5H14A3.5 3.5 0 0010.5 8v2.5H8V14h2.5v6.5H14V14h2.5l.5-3.5h-3V8.5a.5.5 0 01.5-.5z"/>
            @break
        @case('instagram')
            <rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17 7v.01"/>
            @break
        @case('youtube')
            <rect x="2.5" y="5.5" width="19" height="13" rx="4"/><path d="M10 9.5v5l4.5-2.5L10 9.5z"/>
            @break
        @case('x')
            <path d="M4 4l16 16M20 4L4 20"/>
            @break
        @default
            <circle cx="12" cy="12" r="3"/>
    @endswitch
</svg>
