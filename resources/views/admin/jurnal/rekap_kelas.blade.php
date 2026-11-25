@extends('layouts.main')

@section('content')
@php
    $months = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $periodeLabel = ($months[(int) $bulan] ?? $bulan) . ' ' . (int) $tahun;

    $params = ['bulan' => (int) $bulan, 'tahun' => (int) $tahun];
    if ((int) $id_kelas_pilih > 0) $params['id_kelas'] = (int) $id_kelas_pilih;
    if ((int) $id_guru_pilih > 0)  $params['id_guru']  = (int) $id_guru_pilih;
    if ((int) $id_mapel_pilih > 0) $params['id_mapel'] = (int) $id_mapel_pilih;
    $printUrl = url('admin/jurnal/rekap-kelas/cetak') . '?' . http_build_query($params);

    $hariNama = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $namaKelas = $kelas->nama_kelas ?? '—';
@endphp

<div class="space-y-4 px-4 pb-8 pt-4 md:px-6 md:pt-6">

    <x-ui.page-header
        title="Rekap Jurnal per Kelas"
        icon="bi-journal-text"
        tone="blue"
        subtitle="Jurnal mengajar kelas {{ $namaKelas }} — {{ $periodeLabel }}"
        :crumbs="['Jurnal', 'Rekap per Kelas']">
        <x-slot:actions>
            <a href="{{ $printUrl }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white no-underline shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-printer"></i> Cetak PDF
            </a>
            <a href="{{ url('admin/jurnal/rekap') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-file-earmark-check"></i> Rekap Guru
            </a>
            <a href="{{ url('admin/jurnal') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-100">
                <i class="bi bi-arrow-left"></i> Monitoring
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Filter -->
    <x-ui.card title="Filter Jurnal" icon="bi-funnel" subtitle="Pilih kelas, periode, guru, dan mata pelajaran yang ingin ditampilkan.">
        <form method="GET" action="{{ url('admin/jurnal/rekap-kelas') }}" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Kelas</label>
                <select name="id_kelas" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    @if (empty($kelases))
                        <option value="0">Belum ada kelas</option>
                    @else
                        @foreach ($kelases as $k)
                            <option value="{{ (int) $k->id_kelas }}" {{ (int) $id_kelas_pilih === (int) $k->id_kelas ? 'selected' : '' }}>
                                {{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Bulan</label>
                <select name="bulan" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    @foreach ($months as $k => $m)
                        <option value="{{ $k }}" {{ (int) $bulan === (int) $k ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Tahun</label>
                <select name="tahun" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    @for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ (int) $tahun === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Guru</label>
                <select name="id_guru" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="0">Semua guru</option>
                    @foreach ($gurus as $g)
                        <option value="{{ (int) $g->id_guru }}" {{ (int) $id_guru_pilih === (int) $g->id_guru ? 'selected' : '' }}>
                            {{ $g->nama_guru }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-[11px] font-bold uppercase text-slate-600">Mata Pelajaran</label>
                <select name="id_mapel" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="0">Semua mapel</option>
                    @foreach ($mapels as $m)
                        <option value="{{ (int) $m->id_mapel }}" {{ (int) $id_mapel_pilih === (int) $m->id_mapel ? 'selected' : '' }}>
                            {{ $m->nama_mapel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>
                <a href="{{ url('admin/jurnal/rekap-kelas') }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-600 no-underline transition-colors hover:bg-slate-200" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </x-ui.card>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <x-ui.card bodyClass="p-4">
            <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-slate-400">Pertemuan Tercatat</p>
            <p class="m-0 text-2xl font-extrabold leading-tight text-slate-900">{{ (int) $total_pertemuan }}</p>
        </x-ui.card>

        <x-ui.card bodyClass="p-4" style="border-color: rgb(199 210 254 / 0.7)">
            <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-indigo-500">Guru Mengajar</p>
            <p class="m-0 text-2xl font-extrabold leading-tight text-indigo-700">{{ (int) $total_guru }}</p>
        </x-ui.card>

        <x-ui.card bodyClass="p-4" style="border-color: rgb(167 243 208 / 0.7)">
            <p class="m-0 text-[10px] font-bold uppercase tracking-wide text-emerald-500">Mata Pelajaran</p>
            <p class="m-0 text-2xl font-extrabold leading-tight text-emerald-700">{{ (int) $total_mapel }}</p>
        </x-ui.card>
    </div>

    <!-- Rekap per guru di kelas ini -->
    <x-ui.card title="Rekap per Guru — Kelas {{ $namaKelas }}" icon="bi-people" tone="violet" bodyClass="p-0">
        <x-slot:meta>
            <span class="text-xs font-semibold text-slate-500">{{ $periodeLabel }}</span>
        </x-slot:meta>

        <x-ui.table>
            <x-slot:head>
                <th class="px-5 py-3.5 text-center" width="50">No</th>
                <th class="px-5 py-3.5 text-left">Nama Guru</th>
                <th class="px-5 py-3.5 text-left" width="150">NIP</th>
                <th class="px-5 py-3.5 text-left">Mata Pelajaran</th>
                <th class="px-5 py-3.5 text-center" width="110">Pertemuan</th>
                <th class="px-5 py-3.5 text-center" width="140">Terakhir Mengajar</th>
            </x-slot:head>

            @if (empty($per_guru))
                <tr>
                    <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                        Belum ada jurnal untuk kelas ini pada {{ $periodeLabel }}.
                    </td>
                </tr>
            @else
                @php $no = 1; @endphp
                @foreach ($per_guru as $g)
                    <tr class="transition-colors hover:bg-slate-50/70">
                        <td class="px-5 py-3 text-center text-slate-400">{{ $no++ }}</td>
                        <td class="px-5 py-3 font-bold uppercase text-slate-800">{{ $g['nama'] ?: 'Guru #' . (int) $g['id_guru'] }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $g['nip'] ?: '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ implode(', ', $g['mapel']) ?: '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700">{{ (int) $g['pertemuan'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-center text-[11px] font-bold text-slate-600">
                            {{ $g['terakhir'] !== '' ? date('d/m/Y', strtotime($g['terakhir'])) : '-' }}
                        </td>
                    </tr>
                @endforeach
            @endif
        </x-ui.table>
    </x-ui.card>

    <!-- Rincian pertemuan -->
    <x-ui.card title="Rincian Jurnal" icon="bi-journal-check" tone="emerald" bodyClass="p-0">
        <x-slot:meta>
            <span class="text-xs font-semibold text-slate-500">{{ count($jurnals) }} baris</span>
        </x-slot:meta>

        <x-ui.table>
            <x-slot:head>
                <th class="px-5 py-3.5 text-center" width="50">No</th>
                <th class="px-5 py-3.5 text-left" width="130">Hari, Tanggal</th>
                <th class="px-5 py-3.5 text-center" width="70">Jam</th>
                <th class="px-5 py-3.5 text-left">Mata Pelajaran</th>
                <th class="px-5 py-3.5 text-left">Guru</th>
                <th class="px-5 py-3.5 text-left">Materi Pokok</th>
                <th class="px-5 py-3.5 text-left" width="110">Asesmen</th>
                <th class="px-5 py-3.5 text-center" width="100">Tidak Hadir</th>
            </x-slot:head>

            @if (empty($jurnals))
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-slate-400">Belum ada pertemuan yang dijurnal pada periode ini.</td>
                </tr>
            @else
                @php $no = 1; @endphp
                @foreach ($jurnals as $j)
                    @php
                        $absen = json_decode((string) ($j->siswa_absent ?? ''), true);
                        $jumlahAbsen = is_array($absen) ? count(array_filter($absen, static fn ($v) => (int) $v > 0)) : 0;
                    @endphp
                    <tr class="align-top transition-colors hover:bg-slate-50/70">
                        <td class="px-5 py-3 text-center text-slate-400">{{ $no++ }}</td>
                        <td class="px-5 py-3">
                            <p class="m-0 font-bold text-slate-700">{{ $hariNama[(int) date('N', strtotime($j->tanggal))] ?? '' }}</p>
                            <p class="m-0 text-[11px] text-slate-400">{{ date('d/m/Y', strtotime($j->tanggal)) }}</p>
                        </td>
                        <td class="px-5 py-3 text-center font-bold text-slate-600">{{ $j->jam_ke ?: '-' }}</td>
                        <td class="px-5 py-3 font-semibold text-slate-700">{{ $j->nama_mapel ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $j->nama_guru ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ \Illuminate\Support\Str::limit((string) ($j->materi_pokok ?? '-'), 90) }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $j->jenis_asesmen ?: '-' }}</td>
                        <td class="px-5 py-3 text-center">
                            @if ($jumlahAbsen === 0)
                                <span class="inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-800">Lengkap</span>
                            @else
                                <span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-700">{{ $jumlahAbsen }} siswa</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endif
        </x-ui.table>
    </x-ui.card>

    <p class="m-0 flex items-center justify-center gap-1 text-center text-[10px] text-slate-400">
        <i class="bi bi-info-circle"></i>
        Tombol <b class="mx-1">Cetak PDF</b> membuka dokumen A4 landscape berformat jurnal Kurikulum Merdeka (kop sekolah, identitas kelas/fase, tabel jurnal, kolom paraf, dan tanda tangan).
    </p>

</div>
@endsection
