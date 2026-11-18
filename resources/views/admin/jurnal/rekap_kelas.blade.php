@extends('layouts.main')

@section('content')
<?php
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
?>
<div class="fade-in-up p-4 md:p-6 space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-journal-text"></i> Jurnal Kurikulum Merdeka
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Rekap Jurnal per Kelas</h1>
            <p class="text-xs text-slate-500 mt-1">Jurnal mengajar kelas {{ $namaKelas }} — {{ $periodeLabel }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ $printUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-printer"></i> Cetak PDF
            </a>
            <a href="{{ url('admin/jurnal/rekap') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-file-earmark-check"></i> Rekap Guru
            </a>
            <a href="{{ url('admin/jurnal') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Monitoring
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <form method="GET" action="{{ url('admin/jurnal/rekap-kelas') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Kelas</label>
                <select name="id_kelas" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <?php if (empty($kelases)): ?>
                    <option value="0">Belum ada kelas</option>
                    <?php else: foreach ($kelases as $k): ?>
                    <option value="{{ (int) $k->id_kelas }}" {{ (int) $id_kelas_pilih === (int) $k->id_kelas ? 'selected' : '' }}>
                        {{ $k->nama_kelas }}
                    </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Bulan</label>
                <select name="bulan" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <?php foreach ($months as $k => $m): ?>
                    <option value="{{ $k }}" {{ (int) $bulan === (int) $k ? 'selected' : '' }}>{{ $m }}</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Tahun</label>
                <select name="tahun" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                    <option value="{{ $y }}" {{ (int) $tahun === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Guru</label>
                <select name="id_guru" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="0">Semua guru</option>
                    <?php foreach ($gurus as $g): ?>
                    <option value="{{ (int) $g->id_guru }}" {{ (int) $id_guru_pilih === (int) $g->id_guru ? 'selected' : '' }}>
                        {{ $g->nama_guru }}
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Mata Pelajaran</label>
                <select name="id_mapel" class="w-full text-xs font-semibold px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="0">Semua mapel</option>
                    <?php foreach ($mapels as $m): ?>
                    <option value="{{ (int) $m->id_mapel }}" {{ (int) $id_mapel_pilih === (int) $m->id_mapel ? 'selected' : '' }}>
                        {{ $m->nama_mapel }}
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-colors">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>
                <a href="{{ url('admin/jurnal/rekap-kelas') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-colors no-underline" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 m-0">Pertemuan Tercatat</p>
            <p class="text-2xl font-extrabold text-slate-900 leading-tight m-0">{{ (int) $total_pertemuan }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-indigo-200/70 shadow-xs p-4">
            <p class="text-[10px] font-bold uppercase tracking-wide text-indigo-500 m-0">Guru Mengajar</p>
            <p class="text-2xl font-extrabold text-indigo-700 leading-tight m-0">{{ (int) $total_guru }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200/70 shadow-xs p-4">
            <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-500 m-0">Mata Pelajaran</p>
            <p class="text-2xl font-extrabold text-emerald-700 leading-tight m-0">{{ (int) $total_mapel }}</p>
        </div>
    </div>

    <!-- Rekap per guru di kelas ini -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <h3 class="text-sm font-bold text-slate-800 m-0">Rekap per Guru — Kelas {{ $namaKelas }}</h3>
            </div>
            <span class="text-xs font-semibold text-slate-500">{{ $periodeLabel }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200/80">
                        <th class="px-5 py-3.5 text-center" width="50">No</th>
                        <th class="px-5 py-3.5">Nama Guru</th>
                        <th class="px-5 py-3.5" width="150">NIP</th>
                        <th class="px-5 py-3.5">Mata Pelajaran</th>
                        <th class="px-5 py-3.5 text-center" width="110">Pertemuan</th>
                        <th class="px-5 py-3.5 text-center" width="140">Terakhir Mengajar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($per_guru)): ?>
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400">
                            Belum ada jurnal untuk kelas ini pada {{ $periodeLabel }}.
                        </td>
                    </tr>
                    <?php else: $no = 1; foreach ($per_guru as $g): ?>
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-3 text-center text-slate-400">{{ $no++ }}</td>
                        <td class="px-5 py-3 font-bold text-slate-800 uppercase">{{ $g['nama'] ?: 'Guru #' . (int) $g['id_guru'] }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $g['nip'] ?: '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ implode(', ', $g['mapel']) ?: '—' }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-block px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full font-black text-xs">{{ (int) $g['pertemuan'] }}</span>
                        </td>
                        <td class="px-5 py-3 text-center text-[11px] font-bold text-slate-600">
                            {{ $g['terakhir'] !== '' ? date('d/m/Y', strtotime($g['terakhir'])) : '-' }}
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rincian pertemuan -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <h3 class="text-sm font-bold text-slate-800 m-0">Rincian Jurnal</h3>
            </div>
            <span class="text-xs font-semibold text-slate-500">{{ count($jurnals) }} baris</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200/80">
                        <th class="px-5 py-3.5 text-center" width="50">No</th>
                        <th class="px-5 py-3.5" width="130">Hari, Tanggal</th>
                        <th class="px-5 py-3.5 text-center" width="70">Jam</th>
                        <th class="px-5 py-3.5">Mata Pelajaran</th>
                        <th class="px-5 py-3.5">Guru</th>
                        <th class="px-5 py-3.5">Materi Pokok</th>
                        <th class="px-5 py-3.5" width="110">Asesmen</th>
                        <th class="px-5 py-3.5 text-center" width="100">Tidak Hadir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($jurnals)): ?>
                    <tr>
                        <td colspan="8" class="py-10 text-center text-slate-400">Belum ada pertemuan yang dijurnal pada periode ini.</td>
                    </tr>
                    <?php else: $no = 1; foreach ($jurnals as $j):
                        $absen = json_decode((string) ($j->siswa_absent ?? ''), true);
                        $jumlahAbsen = is_array($absen) ? count(array_filter($absen, static fn ($v) => (int) $v > 0)) : 0;
                    ?>
                    <tr class="hover:bg-slate-50/70 transition-colors align-top">
                        <td class="px-5 py-3 text-center text-slate-400">{{ $no++ }}</td>
                        <td class="px-5 py-3">
                            <p class="font-bold text-slate-700 m-0">{{ $hariNama[(int) date('N', strtotime($j->tanggal))] ?? '' }}</p>
                            <p class="text-[11px] text-slate-400 m-0">{{ date('d/m/Y', strtotime($j->tanggal)) }}</p>
                        </td>
                        <td class="px-5 py-3 text-center font-bold text-slate-600">{{ $j->jam_ke ?: '-' }}</td>
                        <td class="px-5 py-3 font-semibold text-slate-700">{{ $j->nama_mapel ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $j->nama_guru ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ \Illuminate\Support\Str::limit((string) ($j->materi_pokok ?? '-'), 90) }}</td>
                        <td class="px-5 py-3 text-slate-500">{{ $j->jenis_asesmen ?: '-' }}</td>
                        <td class="px-5 py-3 text-center">
                            <?php if ($jumlahAbsen === 0): ?>
                            <span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[11px]">Lengkap</span>
                            <?php else: ?>
                            <span class="inline-block px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full font-bold text-[11px]">{{ $jumlahAbsen }} siswa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-[10px] text-slate-400 text-center m-0 flex items-center justify-center gap-1">
        <i class="bi bi-info-circle"></i>
        Tombol <b class="mx-1">Cetak PDF</b> membuka dokumen A4 landscape berformat jurnal Kurikulum Merdeka (kop sekolah, identitas kelas/fase, tabel jurnal, kolom paraf, dan tanda tangan).
    </p>

</div>
@endsection
