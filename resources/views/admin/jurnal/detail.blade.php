@extends('layouts.main')

@section('content')
<div class="mx-auto max-w-4xl space-y-4 px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <div x-data>
        <x-ui.page-header
            title="Detail Jurnal Mengajar"
            icon="bi-journal-text"
            tone="violet"
            :subtitle="($tp_active->tahun ?? '-') . ' · Semester ' . ($smt_active->smt ?? '-')"
            :crumbs="['Jurnal', 'Monitoring', 'Detail']">
            <x-slot:actions>
                <a href="{{ url('admin/jurnal') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 no-underline transition-colors hover:bg-slate-100"
                    title="Kembali ke Monitoring">
                    <i class="bi bi-arrow-left"></i>
                </a>
                @if (!empty($can_print))
                    <a href="{{ url('guru/jurnal/cetak/' . (int) $jurnal->id_jurnal) }}" target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                        <i class="bi bi-printer"></i> Cetak
                    </a>
                @endif
                <button type="button" @click="window.history.back()"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition-colors hover:bg-slate-100"
                    title="Kembali">
                    <i class="bi bi-x-lg"></i>
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <!-- Identitas KBM -->
    <x-ui.card icon="bi-calendar3" title="Identitas KBM" tone="blue" bodyClass="space-y-3 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">
                <i class="bi bi-calendar3 text-[10px]"></i>
                {{ buat_tanggal(date('l, d F Y', strtotime($jurnal->tanggal))) }}
            </span>
            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-700">
                Jam {{ $jurnal->jam_ke }}
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 pt-1 text-xs sm:grid-cols-2">
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Guru Pengajar</span>
                <span class="font-bold uppercase text-slate-800">{{ $jurnal->nama_guru ?? '-' }}</span>
                <span class="mt-0.5 block text-[10px] text-slate-400">NIP: {{ $jurnal->nip ?: '-' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Kelas / Mata Pelajaran</span>
                <span class="font-bold uppercase text-indigo-700">{{ $jurnal->nama_kelas ?? '-' }}</span>
                <span class="mt-0.5 block text-[11px] font-semibold uppercase text-slate-600">{{ $jurnal->nama_mapel ?? '-' }}</span>
            </div>
        </div>
    </x-ui.card>

    <!-- Status validasi -->
    @if (!empty($ada_validasi))
        @php
            $stValidasi = (string) ($status_validasi ?? '');
            $validGaya = [
                'APPROVED' => ['Disetujui guru piket', 'border-emerald-200 bg-emerald-50/70 text-emerald-800', 'bi-shield-check'],
                'PENDING'  => ['Menunggu validasi guru piket', 'border-indigo-200 bg-indigo-50/70 text-indigo-700', 'bi-hourglass-split'],
                'REJECTED' => ['Ditolak — perlu diperbaiki guru', 'border-rose-200 bg-rose-50/70 text-rose-700', 'bi-x-circle-fill'],
            ];
            $gi = $validGaya[$stValidasi] ?? $validGaya['PENDING'];
        @endphp
        <div class="flex items-start gap-2.5 rounded-2xl border p-3.5 {{ $gi[1] }}">
            <i class="bi {{ $gi[2] }} mt-0.5"></i>
            <div class="min-w-0">
                <span class="block text-xs font-extrabold">{{ $gi[0] }}</span>
                <span class="mt-0.5 block text-[10px] font-semibold opacity-80">
                    @if ($stValidasi !== 'PENDING' && !empty($nama_validator))
                        Oleh {{ $nama_validator }}{!! !empty($jurnal->validasi_pada) ? ' · ' . e((string) $jurnal->validasi_pada) : '' !!}
                    @elseif (!empty($jurnal->updated_at))
                        Jurnal terakhir diubah {{ (string) $jurnal->updated_at }}
                    @endif
                </span>
                @if (!empty($jurnal->validasi_catatan))
                    <p class="mb-0 mt-1.5 whitespace-pre-line text-[11px] font-semibold leading-relaxed">
                        Catatan: {{ (string) $jurnal->validasi_catatan }}
                    </p>
                @endif
            </div>
        </div>
    @endif

    <!-- Materi Pokok & Tujuan Pembelajaran -->
    <x-ui.card icon="bi-book" title="Materi & Tujuan Pembelajaran" tone="blue" bodyClass="space-y-3 p-4">
        @if (!empty($jurnal->materi_pokok) || !empty($jurnal->tujuan_pembelajaran))
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @if (!empty($jurnal->materi_pokok))
                    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-3">
                        <span class="mb-1.5 flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-blue-700">
                            <i class="bi bi-bookmark-fill text-[9px] text-blue-600"></i> Materi Pokok
                        </span>
                        <p class="m-0 whitespace-pre-line text-xs font-medium leading-relaxed text-slate-700">
                            {!! nl2br(e((string) $jurnal->materi_pokok)) !!}
                        </p>
                    </div>
                @endif
                @if (!empty($jurnal->tujuan_pembelajaran))
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-3">
                        <span class="mb-1.5 flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-indigo-700">
                            <i class="bi bi-bullseye text-[9px] text-indigo-600"></i> Tujuan Pembelajaran
                        </span>
                        <p class="m-0 whitespace-pre-line text-xs font-medium leading-relaxed text-slate-700">
                            {!! nl2br(e((string) $jurnal->tujuan_pembelajaran)) !!}
                        </p>
                    </div>
                @endif
            </div>
        @else
            <x-ui.empty icon="bi-dash-square" title="Tidak ada materi pokok dan tujuan pembelajaran" />
        @endif
    </x-ui.card>

    <!-- Model & Asesmen -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <x-ui.card bodyClass="p-3.5">
            <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Model / Metode</span>
            <span class="text-xs font-bold text-violet-700">{{ $jurnal->model_belajar ?: 'Reguler' }}</span>
        </x-ui.card>
        <x-ui.card bodyClass="p-3.5">
            <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Bentuk Asesmen</span>
            <span class="text-xs font-bold text-emerald-700">{{ $jurnal->jenis_asesmen ?: 'Formatif' }}</span>
        </x-ui.card>
    </div>

    <!-- Profil Pancasila -->
    @if (!empty($profil_list))
        <x-ui.card icon="bi-stars" title="Dimensi Profil Pelajar Pancasila" tone="amber" bodyClass="p-4">
            <div class="flex flex-wrap gap-1.5">
                @foreach ($profil_list as $p)
                    <span class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">
                        <i class="bi bi-star-fill text-[9px] text-amber-500"></i> {{ $p }}
                    </span>
                @endforeach
            </div>
        </x-ui.card>
    @endif

    <!-- Kegiatan / Media -->
    @if (!empty($jurnal->kegiatan_belajar) || !empty($jurnal->media_alat))
        <x-ui.card icon="bi-list-task" title="Kegiatan & Media" tone="blue" bodyClass="space-y-3 p-4">
            @if (!empty($jurnal->kegiatan_belajar))
                <div>
                    <span class="mb-1 flex items-center gap-1 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                        <i class="bi bi-list-task text-[9px] text-blue-500"></i> Alur Kegiatan Belajar
                    </span>
                    <p class="m-0 whitespace-pre-line text-xs font-medium leading-relaxed text-slate-700">
                        {{ (string) $jurnal->kegiatan_belajar }}
                    </p>
                </div>
            @endif
            @if (!empty($jurnal->media_alat))
                <div class="border-t border-slate-100 pt-2">
                    <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Media &amp; Alat</span>
                    <span class="text-xs font-bold text-slate-700">{{ $jurnal->media_alat }}</span>
                </div>
            @endif
        </x-ui.card>
    @endif

    <!-- Refleksi & Tindak Lanjut -->
    @if (!empty($jurnal->refleksi) || !empty($jurnal->tindak_lanjut))
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            @if (!empty($jurnal->refleksi))
                <x-ui.card icon="bi-lightbulb" title="Refleksi Guru" tone="amber" bodyClass="p-4">
                    <p class="m-0 whitespace-pre-line text-xs font-medium leading-relaxed text-slate-700">
                        {{ (string) $jurnal->refleksi }}
                    </p>
                </x-ui.card>
            @endif
            @if (!empty($jurnal->tindak_lanjut))
                <x-ui.card icon="bi-arrow-right-circle" title="Rencana Tindak Lanjut" tone="blue" bodyClass="p-4">
                    <p class="m-0 whitespace-pre-line text-xs font-medium leading-relaxed text-slate-700">
                        {{ (string) $jurnal->tindak_lanjut }}
                    </p>
                </x-ui.card>
            @endif
        </div>
    @endif

    <!-- Kehadiran Siswa & Catatan -->
    <x-ui.card icon="bi-people" title="Kehadiran Siswa & Catatan" tone="emerald" bodyClass="space-y-3 p-4">
        <div>
            <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                Status Kehadiran Siswa
            </span>
            @php
                $absentNama = $siswa_absent_nama ?? [];
                $absentCount = $siswa_absent_count ?? 0;
                $rawAbsent = trim((string) ($jurnal->siswa_absent ?? ''));
                $isNihil = empty($absentNama) && empty($rawAbsent) || strtolower($rawAbsent) === 'nihil';
            @endphp
            @if ($absentCount > 0)
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">
                    <i class="bi bi-person-x"></i> Tidak Hadir ({{ $absentCount }}):
                    <span class="ml-1 text-[10px] uppercase">{{ implode(', ', $absentNama) }}</span>
                </span>
            @elseif (!$isNihil && $rawAbsent !== '')
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700">
                    <i class="bi bi-person-x"></i> Tidak Hadir: {{ $rawAbsent }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                    <i class="bi bi-check-circle-fill"></i> Semua siswa hadir
                </span>
            @endif
        </div>

        @if (!empty($jurnal->catatan_khusus))
            <div class="border-t border-slate-100 pt-2">
                <span class="mb-1 block text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                    Catatan Khusus
                </span>
                <p class="m-0 whitespace-pre-line text-xs font-medium text-slate-700">{!! nl2br(e((string) $jurnal->catatan_khusus)) !!}</p>
            </div>
        @endif
    </x-ui.card>

    <p class="m-0 flex items-center justify-center gap-1 text-center text-[10px] text-slate-400">
        <i class="bi bi-info-circle"></i>
        Halaman ini hanya untuk membaca. Perubahan jurnal dilakukan oleh guru pemilik jurnal.
    </p>
</div>
@endsection
