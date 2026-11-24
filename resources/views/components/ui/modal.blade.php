@props([
    'id',
    'title' => null,
    'icon' => null,
    'tone' => 'blue',
    'size' => 'max-w-lg',
])

@php
    $tones = [
        'blue' => 'bg-blue-600',
        'emerald' => 'bg-emerald-600',
        'amber' => 'bg-amber-500',
        'rose' => 'bg-rose-600',
        'slate' => 'bg-slate-800',
    ];
@endphp

<div id="{{ $id }}"
    {{ $attributes->merge(['class' => 'modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-3 sm:p-4']) }}
    tabindex="-1" aria-hidden="true"
    @if ($title) aria-labelledby="{{ $id }}-title" @endif>
    <div class="ui-modal-box flex max-h-[92vh] w-full {{ $size }} flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        @isset($header)
            {{ $header }}
        @elseif ($title)
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 id="{{ $id }}-title" class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    @if ($icon)
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white {{ $tones[$tone] ?? $tones['blue'] }}">
                            <i class="bi {{ $icon }} text-sm"></i>
                        </span>
                    @endif
                    <span class="truncate">{{ $title }}</span>
                </h2>
                <button type="button" data-ui-dismiss="modal" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
