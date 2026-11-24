@props([
    'icon' => 'bi-inbox',
    'title' => 'Belum ada data',
    'description' => null,
])

<div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-10 text-center">
    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl text-blue-500">
        <i class="bi {{ $icon }}"></i>
    </div>
    <h3 class="text-sm font-bold text-slate-700">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">{{ $description }}</p>
    @endif
    @isset($slot)
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">{{ $slot }}</div>
    @endisset
</div>
