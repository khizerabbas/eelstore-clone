@props(['src' => null, 'alt' => '', 'lazy' => true])
@php($placeholder = \App\Support\Content::placeholderUrl())
{{-- Image with a fallback: shows the "No preview available" placeholder when there is no image,
     and app.js swaps it in when the file fails to load (data-fallback). --}}
<img src="{{ $src ?: $placeholder }}"
     alt="{{ $alt }}"
     data-fallback="{{ $placeholder }}"
     @if($lazy) loading="lazy" @endif
     {{ $attributes }}>
