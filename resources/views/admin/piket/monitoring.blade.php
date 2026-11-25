@extends('layouts.main')

@section('styles')
<style>
@media print {
    .no-print { display: none !important; }
    .fade-in-up { animation: none !important; }
    body { background: #fff !important; }
}
</style>
@endsection

@section('content')
<div class="fade-in-up space-y-4 px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="{{ $judul }}"
        icon="bi-clipboard-data"
        tone="blue"
        subtitle="Laporan harian aktivitas piket."
        :crumbs="['Piket', 'Monitoring']">
        <x-slot:actions>
            <button type="button" @click="window.print()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                <i class="bi bi-printer"></i> Cetak
            </button>
            <a href="{{ url('admin/piket') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-arrow-left"></i> Kembali ke Jadwal
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Filter -->
    <x-ui.card title="Filter Tanggal" icon="bi-funnel" class="no-print"
        subtitle="Pilih tanggal laporan piket yang ingin ditampilkan.">
        <form method="get" class="flex flex-col items-stretch gap-3 md:flex-row md:items-end">
            <div class="flex-1">
                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700" for="filter-tanggal">
                    <i class="bi bi-calendar3 mr-1 text-slate-400"></i>Tanggal
                </label>
                <input type="date" name="tanggal" id="filter-tanggal" value="{{ $tanggal }}" max="{{ date('Y-m-d') }}"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-800 transition-colors focus:border-transparent focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                <i class="bi bi-search"></i> Lihat Data
            </button>
        </form>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Rekap Absen -->
        <x-ui.card title="Absensi Siswa" icon="bi-person-x-fill" tone="rose" bodyClass="p-0"
            subtitle="Rekap ketidakhadiran per kelas • {{ $tanggal }}">
            @if (empty($rekap_absen))
                <div class="p-5">
                    @if (!empty($sudah_direkap))
                        <x-ui.empty icon="bi-check-circle" title="Nihil" description="Semua siswa hadir." />
                    @else
                        <x-ui.empty icon="bi-clipboard-x" title="Belum Direkap"
                            description="Piket belum menginput absensi tanggal ini." />
                    @endif
                </div>
            @else
                <x-ui.table>
                    <x-slot:head>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-right">Jumlah Ketidakhadiran</th>
                    </x-slot:head>

                    @foreach ($rekap_absen as $r)
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-semibold text-slate-800">
                                {{ $r->nama_kelas }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-flex items-center rounded-full border border-red-100 bg-red-50 px-2.5 py-0.5 text-xs font-bold text-red-700">
                                    {{ (int) $r->total_absen }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>

        <!-- Tamu & Kejadian -->
        <div class="space-y-4">
            <!-- Kejadian -->
            <x-ui.card title="Kejadian / Pelanggaran" icon="bi-exclamation-triangle-fill" tone="amber" bodyClass="p-0"
                subtitle="Catatan kejadian khusus">
                @if (!empty($kejadian))
                    <x-slot:meta>
                        <span class="rounded-full border border-amber-100 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                            {{ count($kejadian) }}
                        </span>
                    </x-slot:meta>
                @endif

                @if (empty($kejadian))
                    <div class="p-5">
                        <x-ui.empty icon="bi-shield-check" title="Aman" description="Tidak ada kejadian khusus." />
                    </div>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <th class="px-4 py-3 text-left" width="10%">Jam</th>
                            <th class="px-4 py-3 text-left" width="20%">Jenis</th>
                            <th class="px-4 py-3 text-left" width="25%">Siswa</th>
                            <th class="px-4 py-3 text-left">Keterangan</th>
                        </x-slot:head>

                        @foreach ($kejadian as $k)
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-4 py-3 font-mono text-xs">{{ substr($k->jam ?? '', 0, 5) ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border border-amber-100 bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700">
                                        {{ $k->jenis ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold uppercase text-slate-800">{{ $k->nama_siswa ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $k->keterangan ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>

            <!-- Tamu -->
            <x-ui.card title="Buku Tamu" icon="bi-people-fill" tone="blue" bodyClass="p-0"
                subtitle="Catatan kunjungan tamu">
                @if (!empty($tamu))
                    <x-slot:meta>
                        <span class="rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">
                            {{ count($tamu) }}
                        </span>
                    </x-slot:meta>
                @endif

                @if (empty($tamu))
                    <div class="p-5">
                        <x-ui.empty icon="bi-person-badge" title="Belum Ada Tamu"
                            description="Belum ada tamu yang tercatat hari ini." />
                    </div>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <th class="px-4 py-3 text-left">Waktu</th>
                            <th class="px-4 py-3 text-left">Nama Tamu</th>
                            <th class="px-4 py-3 text-left">Instansi</th>
                            <th class="px-4 py-3 text-left">Bertemu</th>
                        </x-slot:head>

                        @foreach ($tamu as $t)
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">
                                    <i class="bi bi-clock mr-1 text-slate-300"></i>{{ substr($t->jam_masuk ?? '', 0, 5) ?: '—' }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $t->nama ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $t->instansi ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">
                                        {{ $t->bertemu ?? '—' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>
        </div>
    </div>

    <!-- Jurnal KBM & Kehadiran Guru -->
    @php
        $guruMasuk = [];
        $guruTerlambat = [];
        $guruBelum = [];
        foreach (($guru_absensi ?? []) as $ga) {
            if (!empty($ga->jam_masuk)) {
                $guruMasuk[] = $ga;
                if (!empty($ga->status_masuk) && strtolower($ga->status_masuk) === 'late') {
                    $guruTerlambat[] = $ga;
                }
            } else {
                $guruBelum[] = $ga;
            }
        }
    @endphp

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <!-- Jurnal KBM -->
        <x-ui.card title="Jurnal KBM" icon="bi-journal-text" tone="blue" bodyClass="p-0"
            subtitle="Aktivitas mengajar • {{ $tanggal }}">
            @if (!empty($jurnal))
                <x-slot:meta>
                    <span class="rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-[11px] font-bold text-blue-700">
                        {{ count($jurnal) }}
                    </span>
                </x-slot:meta>
            @endif

            @if (empty($jurnal))
                <div class="p-5">
                    <x-ui.empty icon="bi-journal-x" title="Belum Ada Jurnal"
                        description="Belum ada aktivitas KBM yang dicatat tanggal ini." />
                </div>
            @else
                <div class="max-h-96 overflow-y-auto">
                    <x-ui.table>
                        <x-slot:head>
                            <th class="px-4 py-3 text-left">Jam</th>
                            <th class="px-4 py-3 text-left">Guru</th>
                            <th class="px-4 py-3 text-left">Kelas &amp; Mapel</th>
                            <th class="px-4 py-3 text-center">Detail</th>
                        </x-slot:head>

                        @foreach ($jurnal as $j)
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">
                                        {{ $j->jam_ke ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold uppercase text-slate-800">{{ $j->nama_guru ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold uppercase text-slate-700">{{ $j->nama_kelas ?? '—' }}</div>
                                    <div class="text-[11px] uppercase text-slate-400">{{ $j->nama_mapel ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ url('admin/jurnal/detail/' . (int) $j->id_jurnal) }}" target="_blank"
                                        class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-xs text-blue-600 no-underline transition-colors hover:bg-blue-100"
                                        title="Baca detail jurnal">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </div>
            @endif
        </x-ui.card>

        <!-- Kehadiran Guru -->
        <x-ui.card title="Kehadiran Guru" icon="bi-people-fill" tone="emerald" bodyClass="p-0"
            subtitle="Check-in/out • {{ $tanggal }}">
            <x-slot:meta>
                <span class="inline-flex items-center rounded-full border border-emerald-100 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                    Hadir {{ count($guruMasuk) - count($guruTerlambat) }}
                </span>
                <span class="inline-flex items-center rounded-full border border-amber-100 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                    Terlambat {{ count($guruTerlambat) }}
                </span>
                <span class="inline-flex items-center rounded-full border border-rose-100 bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700">
                    Belum {{ count($guruBelum) }}
                </span>
            </x-slot:meta>

            @if (empty($guru_absensi))
                <div class="p-5">
                    <x-ui.empty icon="bi-person-x" title="Belum Ada Data"
                        description="Belum ada data kehadiran guru tanggal ini." />
                </div>
            @else
                <div class="max-h-96 overflow-y-auto">
                    <x-ui.table>
                        <x-slot:head>
                            <th class="px-4 py-3 text-left">Nama</th>
                            <th class="px-4 py-3 text-center">Masuk</th>
                            <th class="px-4 py-3 text-center">Pulang</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </x-slot:head>

                        @foreach ($guru_absensi as $g)
                            @php
                                $isLate = !empty($g->status_masuk) && strtolower($g->status_masuk) === 'late';
                                if (!empty($g->jam_masuk) && !$isLate) {
                                    $bCls = 'bg-emerald-50 text-emerald-700 border-emerald-100'; $bTxt = 'Hadir';
                                } elseif (!empty($g->jam_masuk)) {
                                    $bCls = 'bg-amber-50 text-amber-700 border-amber-100'; $bTxt = 'Terlambat';
                                } else {
                                    $bCls = 'bg-rose-50 text-rose-700 border-rose-100'; $bTxt = 'Belum';
                                }
                                $jamMasukFmt = '—';
                                $jamPulangFmt = '—';
                                try {
                                    if (!empty($g->jam_masuk)) $jamMasukFmt = date('H:i', strtotime((string) $g->jam_masuk));
                                    if (!empty($g->jam_pulang)) $jamPulangFmt = date('H:i', strtotime((string) $g->jam_pulang));
                                } catch (\Throwable $e) {
                                }
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/70">
                                <td class="px-4 py-3 font-semibold uppercase text-slate-800">{{ $g->nama_guru ?? '—' }}</td>
                                <td class="px-4 py-3 text-center font-mono text-xs">{{ $jamMasukFmt }}</td>
                                <td class="px-4 py-3 text-center font-mono text-xs">{{ $jamPulangFmt }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $bCls }}">
                                        {{ $bTxt }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- Selisih Piket (patokan) vs Presensi Formal -->
    <x-ui.card title="Selisih Piket vs Presensi Formal" icon="bi-exclamation-triangle-fill" tone="amber" bodyClass="p-0"
        style="border-color: rgb(253 230 138)"
        subtitle="Catatan piket bertentangan dengan sistem • {{ $tanggal }}">
        @if (!empty($divergensi ?? []))
            <x-slot:meta>
                <span class="rounded-full border border-amber-100 bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">
                    {{ count($divergensi) }}
                </span>
            </x-slot:meta>
        @endif

        @if (empty($divergensi ?? []))
            <div class="p-5">
                <x-ui.empty icon="bi-check-circle" title="Selaras" description="Tidak ada pertentangan piket vs sistem." />
            </div>
        @else
            <div class="max-h-96 overflow-y-auto">
                <x-ui.table>
                    <x-slot:head>
                        <th class="px-4 py-3 text-left">Siswa</th>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-center">Piket</th>
                        <th class="px-4 py-3 text-center">Sistem</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                        <th class="no-print px-4 py-3 text-right">Aksi</th>
                    </x-slot:head>

                    @foreach ($divergensi as $d)
                        <tr class="transition-colors hover:bg-slate-50/70"
                            x-data="{
                                busy: false,
                                done: false,
                                async selaraskan(idSiswa) {
                                    if (!idSiswa || this.busy) return;
                                    this.busy = true;
                                    const fd = new FormData();
                                    fd.append('tanggal', '{{ $tanggal }}');
                                    fd.append('id_siswa', idSiswa);
                                    fd.append('_token', '{{ csrf_token() }}');
                                    try {
                                        const res = await fetch('{{ url('admin/piket/selaras-divergensi') }}', {
                                            method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                        });
                                        const json = await res.json().catch(() => null);
                                        if (res.ok && json && json.status) {
                                            this.done = true;
                                            window.location.reload();
                                        } else {
                                            alert((json && json.message) || 'Gagal menyelaraskan');
                                        }
                                    } catch (e) {
                                        alert('Gagal menghubungkan ke server');
                                    } finally {
                                        this.busy = false;
                                    }
                                }
                            }"
                            :style="done ? 'opacity: .45' : ''">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $d->nama ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $d->nama_kelas ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $d->piket ?? '?' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700">{{ ($d->formal ?? '—') . (!empty($d->jam_masuk) ? ' ' . substr($d->jam_masuk, 0, 5) : '') }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-amber-700">{{ $d->ket ?? '' }}</td>
                            <td class="no-print px-4 py-3 text-right">
                                <button type="button" @click="selaraskan({{ (int) ($d->id_siswa ?? 0) }})" :disabled="busy"
                                    x-text="busy ? '…' : (done ? 'Selaras' : 'Selaraskan')"
                                    class="rounded-lg border border-amber-200 bg-amber-100 px-2.5 py-1.5 text-[11px] font-bold text-amber-800 transition-colors hover:bg-amber-200 disabled:opacity-60">
                                    Selaraskan
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
