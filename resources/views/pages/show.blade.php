@extends('layouts.frontend')

@section('title', ($page->meta_title ?: $page->title) . \App\Support\Content::text('seo.title_suffix'))
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->body), 155))

@section('content')
    <section class="bg-brand text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl font-bold">{{ $page->title }}</h1>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <article class="bg-white border border-gray-100 rounded-lg p-5 sm:p-8 prose prose-sm sm:prose max-w-none">
            {!! \App\Support\RichText::render($page->body) !!}
        </article>
    </section>
@endsection
