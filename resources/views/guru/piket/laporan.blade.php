@extends('layouts.main')

@section('content')

<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul"
        icon="bi-file-earmark-diff"
        tone="blue"
        subtitle="Perbandingan Kehadiran Guru (Piket vs Jurnal Guru)"
        :crumbs="['Piket', 'Laporan']">
        <x-slot:actions>
            <a href="{{ url('guru/piket/input_laporan') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-pencil-square"></i> Input Laporan Piket
            </a>
            <a href="{{ url('guru/piket') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

    <x-ui.card bodyClass="p-0">
        <x-slot:toolbar>
            <form method="get" class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-end gap-3">
                    <div>
                        <label for="tanggal" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()"
                            class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <noscript>
                        <button type="submit"
                            class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 text-xs font-bold text-white">Tampilkan</button>
                    </noscript>
                </div>

                <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-bold text-slate-500">
                    <span class="inline-flex items-center gap-1"><i class="bi bi-person-check-fill text-emerald-600"></i> Jurnal = Guru isi mandiri</span>
                    <span class="h-4 w-px bg-slate-200" aria-hidden="true"></span>
                    <span class="inline-flex items-center gap-1"><i class="bi bi-clipboard-check-fill text-blue-600"></i> Piket = Petugas piket cek manual</span>
                </div>
            </form>
        </x-slot:toolbar>
    </x-ui.card>

    @if (empty($report))
        <x-ui.card bodyClass="p-0">
            <div class="p-6">
                <x-ui.empty icon="bi-inbox" title="Tidak ada jadwal KBM ditemukan"
                    description="Tidak ada jadwal KBM pada tanggal yang dipilih." />
            </div>
        </x-ui.card>
    @else
        <div class="grid gap-4 xl:grid-cols-2">
            @foreach ($report as $kls)
                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                            <i class="bi bi-building text-blue-600"></i> Kelas: {{ $kls['nama_kelas'] }}
                        </h2>
                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-500">
                            Sesuai Jadwal
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] border-collapse text-left text-xs">
                            <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                <tr class="border-b border-slate-200">
                                    <th class="w-14 px-3 py-2.5">Jam</th>
                                    <th class="px-3 py-2.5">Mata Pelajaran / Guru</th>
                                    <th class="w-24 px-3 py-2.5 text-center">Jurnal Guru</th>
                                    <th class="w-28 px-3 py-2.5 text-center">Laporan Piket</th>
                                    <th class="w-16 px-3 py-2.5 text-center">Match?</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($kls['slots'] as $s)
                                    @php
                                        $isFilled = $s['jurnal'];
                                        $piket = $s['piket'];
                                        $isPiketHadir = ($piket && $piket->status == 'H');
                                        $match = ($isFilled == $isPiketHadir);
                                    @endphp
                                    <tr class="{{ ! $match ? 'bg-rose-50/60' : '' }}">
                                        <td class="px-3 py-2.5 text-center font-bold text-slate-700">{{ $s['jam_ke'] }}</td>
                                        <td class="px-3 py-2.5">
                                            <div class="font-bold text-slate-800">{{ $s['nama_mapel'] }}</div>
                                            <div class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-400">
                                                <i class="bi bi-person"></i>{{ $s['nama_guru'] }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            @if ($isFilled)
                                                <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-800">
                                                    <i class="bi bi-check-circle"></i> Isi
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-800">
                                                    <i class="bi bi-x-circle"></i> Kosong
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            @if ($piket)
                                                <span class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase {{ $isPiketHadir ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                    <i class="bi bi-{{ $isPiketHadir ? 'person-check' : 'person-x' }}"></i> {{ $piket->status }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-400">
                                                    ? Belum
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            @if (! $piket)
                                                <i class="bi bi-question-circle text-slate-400" title="Belum ada laporan piket"></i>
                                            @elseif ($match)
                                                <i class="bi bi-check-all text-lg text-emerald-600" title="Data sesuai"></i>
                                            @else
                                                <i class="bi bi-exclamation-triangle-fill text-rose-600" title="Data tidak sesuai!"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($piket && $piket->keterangan)
                                        <tr class="{{ ! $match ? 'bg-rose-50/60' : '' }}">
                                            <td colspan="5" class="px-3 pb-2.5">
                                                <div class="flex items-start gap-1.5 rounded-lg border border-slate-200 bg-white/70 px-2.5 py-1.5 text-[11px] text-slate-500">
                                                    <i class="bi bi-info-circle mt-0.5"></i>
                                                    <span>Catatan Piket: {{ $piket->keterangan }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50/70 px-4 py-3.5">
            <i class="bi bi-lightbulb mt-0.5 text-xl text-blue-500"></i>
            <div class="text-xs leading-relaxed text-slate-600">
                <h2 class="mb-0.5 text-xs font-bold text-slate-800">Tips Cross-Check</h2>
                <p>
                    Baris berwarna <span class="rounded bg-rose-100 px-1 font-semibold text-rose-700">merah</span>
                    menandakan adanya ketidaksesuaian antara jurnal yang diisi guru dengan pantauan manual petugas piket.
                    Hal ini berguna untuk memastikan objektivitas data kehadiran guru.
                </p>
            </div>
        </div>
    @endif
</div>
@endsection
