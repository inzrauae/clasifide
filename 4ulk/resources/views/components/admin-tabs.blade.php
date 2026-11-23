@props(['tabs', 'current', 'route', 'param' => 'status'])
<div class="flex gap-1 overflow-x-auto no-scrollbar border-b border-line mb-4">
    @foreach ($tabs as $k => $label)
        <a href="{{ route($route, array_merge(request()->except('page', $param), [$param => $k])) }}" class="shrink-0 px-4 py-2.5 text-sm font-bold border-b-2 -mb-px {{ (string) $current === (string) $k ? 'border-brand text-brand-dark' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
    @endforeach
</div>
