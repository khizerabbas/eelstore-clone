@if($categories->isNotEmpty())
    <div class="mt-5 text-left">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Or browse a category</p>
        <div class="flex flex-wrap gap-2">
            @foreach($categories as $category)
                <a href="{{ route('category.show', $category->slug) }}"
                   class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1.5 text-sm text-slate-700 hover:border-accent hover:text-accent-ink">
                    {{ $category->name }}
                </a>
            @endforeach
        </div>
    </div>
@endif
