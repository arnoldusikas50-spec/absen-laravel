@props([])

<div class="overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'w-full border-collapse text-left text-sm']) }}>
        @isset($head)
            <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <tr class="border-b-2 border-slate-200/80">{{ $head }}</tr>
            </thead>
        @endisset

        <tbody class="divide-y divide-slate-100 text-slate-700">{{ $slot }}</tbody>
    </table>
</div>
