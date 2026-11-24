@props([
    'title' => null,
    'icon' => null,
    'subtitle' => null,
    'tone' => 'blue',
    'bodyClass' => 'p-5',
])

@php
    $iconColor = [
        'blue' => 'text-blue-600',
        'emerald' => 'text-emerald-600',
        'violet' => 'text-violet-600',
        'amber' => 'text-amber-600',
        'rose' => 'text-rose-600',
        'slate' => 'text-slate-700',
    ][$tone] ?? 'text-blue-600';
@endphp

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs']) }}>
    @if ($title !== null || isset($actions))
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                @if ($title !== null)
                    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        @if ($icon)
                            <i class="bi {{ $icon }} {{ $iconColor }}"></i>
                        @endif
                        <span>{{ $title }}</span>
                    </h2>
                @endif
                @if ($subtitle)
                    <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
                @isset($meta)
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">{{ $meta }}</div>
                @endisset
            </div>
            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @isset($toolbar)
        <div class="border-b border-slate-100 px-5 py-3">{{ $toolbar }}</div>
    @endisset

    <div class="{{ $bodyClass }}">{{ $slot }}</div>

    @isset($footer)
        <div class="border-t border-slate-100 bg-slate-50 px-5 py-3">{{ $footer }}</div>
    @endisset
</div>
