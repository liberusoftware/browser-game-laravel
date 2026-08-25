<div class="flex flex-wrap gap-3">
    @foreach ($links as $label => $url)
        @if (is_string($url))
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-sm text-primary-600">{{ $label }}</a>
        @endif
    @endforeach
</div>
