@props([
    'title' => '',
    'icon' => 'bi-grid-1x2',
    'tone' => 'blue',
    'subtitle' => null,
    'crumbs' => [],
])

@php
    $tones = [
        'blue' => 'bg-blue-50 text-blue-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'slate' => 'bg-slate-100 text-slate-700',
    ];
    $trail = collect($crumbs)->filter(fn ($crumb) => filled($crumb))->values();
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div class="flex min-w-0 items-center gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-xs {{ $tones[$tone] ?? $tones['blue'] }}">
            <i class="bi {{ $icon }} text-xl"></i>
        </span>
        <div class="min-w-0">
            <h1 class="truncate text-xl font-bold text-slate-800 md:text-2xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-2">
        @isset($actions)
            {{ $actions }}
        @endisset

        <nav aria-label="breadcrumb">
            <ol class="flex flex-wrap items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white px-3.5 py-2 text-xs font-semibold shadow-xs">
                <li>
                    <a href="{{ url('dashboard') }}" class="inline-flex items-center gap-1 text-slate-500 no-underline transition-colors hover:text-blue-600">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                @foreach ($trail as $crumb)
                    <li aria-hidden="true" class="text-slate-300">/</li>
                    <li @class(['text-blue-600' => $loop->last, 'text-slate-400' => ! $loop->last])>{{ $crumb }}</li>
                @endforeach
            </ol>
        </nav>
    </div>
</div>
