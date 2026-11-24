@extends('layouts.main')

@section('content')
@php
    $namaBulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    $baris = [];
    foreach ($payments as $p) {
        $nominal = (float) ($p->nominal_bayar ?: $p->total_denda);
        $tglBayar = $p->tanggal_bayar ?: ($p->paid_at ?? null);
        $adminNama = trim(($p->admin_first ?? '').' '.($p->admin_last ?? ''));
        $metode = strtoupper((string) ($p->metode_bayar ?? ''));
        $status = (string) ($p->status ?? '');

        $baris[] = [
            'id' => $p->id ?? null,
            'cari' => mb_strtolower((string) ($p->nama_guru ?? '')),
            'nama' => (string) ($p->nama_guru ?? '-'),
            'nip' => (string) ($p->nip ?? ''),
            'tanggal' => $tglBayar ? date('d M Y', strtotime($tglBayar)) : '-',
            'periode' => ($namaBulan[(int) $p->bulan] ?? (string) $p->bulan).' '.$p->tahun,
            'nominal' => $nominal,
            'nominalTeks' => number_format($nominal, 0, ',', '.'),
            'metode' => in_array($metode, ['POIN', 'SALDO'], true) ? $metode : 'TUNAI',
            'status' => $status,
            'statusLabel' => $status === 'lunas' ? 'Lunas' : ($status === 'sebagian' ? 'Sebagian' : ucfirst($status)),
            'keterangan' => mb_strimwidth((string) ($p->keterangan ?? '-'), 0, 60, '…'),
            'pencatat' => $adminNama,
            'dicatat' => $p->paid_at ? date('d M Y H:i', strtotime($p->paid_at)) : '',
        ];
    }

    $infoFilter = [];
    if (! empty($filterGuruNama)) {
        $infoFilter[] = $filterGuruNama;
    } elseif (! empty($filterUserId)) {
        $infoFilter[] = 'Guru #'.$filterUserId;
    }
    $infoFilter[] = 'Dari '.date('d M Y', strtotime($filterTglDari));
    $infoFilter[] = 'Sampai '.date('d M Y', strtotime($filterTglSampai));
    if (! empty($filterMetode)) {
        $infoFilter[] = ucfirst(strtolower((string) $filterMetode));
    }

    $laporanConfig = [
        'rows' => $baris,
        'filter' => [
            'user_id' => (string) ($filterUserId ?? ''),
            'tgl_dari' => (string) $filterTglDari,
            'tgl_sampai' => (string) $filterTglSampai,
            'metode' => (string) ($filterMetode ?? ''),
        ],
        'urlPdf' => url('admin/presensi/denda/laporan/pdf'),
    ];
@endphp

<div class="space-y-4" x-data="laporanPembayaran(@js($laporanConfig))">

    <x-ui.page-header title="Laporan Pembayaran Denda" icon="bi-journal-text" tone="blue"
        subtitle="Riwayat pembayaran denda guru/pegawai • {{ implode(' • ', $infoFilter) }}"
        :crumbs="['Presensi', 'Laporan Pembayaran']" class="print:hidden">
        <x-slot:actions>
            <a href="{{ url('admin/presensi/denda') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2.5 text-xs font-bold text-sky-700 no-underline transition-colors hover:bg-sky-100">
                <i class="bi bi-cash-coin"></i> Kelola Denda
            </a>
            <a href="{{ url('admin/presensi') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-speedometer2"></i> Monitoring
            </a>
            <button type="button" @click="unduhPdf()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2.5 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700">
                <i class="bi bi-file-earmark-pdf"></i> Unduh PDF
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Ringkasan nominal --}}
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        @php
            $ringkasan = [
                ['Total', 'bi-cash-coin', 'border-l-slate-400', 'bg-slate-100 text-slate-600', 'text-slate-900', $totalNominal, count($payments).' transaksi'],
                ['Tunai', 'bi-cash-stack', 'border-l-emerald-500', 'bg-emerald-50 text-emerald-600', 'text-emerald-600', $totalTunai, null],
                ['Poin', 'bi-stars', 'border-l-violet-500', 'bg-violet-50 text-violet-600', 'text-violet-600', $totalPoin, null],
                ['Saldo', 'bi-wallet2', 'border-l-sky-500', 'bg-sky-50 text-sky-600', 'text-sky-600', $totalSaldo ?? 0, null],
            ];
        @endphp

        @foreach ($ringkasan as [$label, $ikon, $aksen, $warnaIkon, $warnaAngka, $nilai, $catatan])
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 {{ $aksen }} border-l-4 bg-white p-4 shadow-xs">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $warnaIkon }} text-lg">
                    <i class="bi {{ $ikon }}"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $label }}</p>
                    <p class="truncate text-xl font-extrabold leading-tight tabular-nums {{ $warnaAngka }}">
                        Rp {{ number_format($nilai, 0, ',', '.') }}
                    </p>
                    @if ($catatan)
                        <p class="text-[11px] text-slate-400">{{ $catatan }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <x-ui.card bodyClass="p-0" class="print:hidden">
        <form x-ref="filterForm" action="" method="get" class="flex flex-wrap items-end gap-3 p-5">
            <div class="w-[220px]">
                <label for="fGuru" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Guru / Pegawai</label>
                <select name="user_id" id="fGuru" x-model="filter.user_id"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">— Semua Guru —</option>
                    @foreach ($guruList as $g)
                        <option value="{{ $g->id }}">{{ $g->nama_guru }}{{ $g->nip ? ' ('.$g->nip.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-[150px]">
                <label for="fDari" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Dari</label>
                <input type="date" id="fDari" name="tgl_dari" x-model="filter.tgl_dari" value="{{ $filterTglDari }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div class="w-[150px]">
                <label for="fSampai" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Sampai</label>
                <input type="date" id="fSampai" name="tgl_sampai" x-model="filter.tgl_sampai" value="{{ $filterTglSampai }}"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            </div>
            <div class="w-[150px]">
                <label for="fMetode" class="mb-1.5 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Metode</label>
                <select name="metode" id="fMetode" x-model="filter.metode"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 shadow-xs outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">— Semua —</option>
                    <option value="TUNAI">Tunai</option>
                    <option value="POIN">Poin</option>
                    <option value="SALDO">Saldo</option>
                </select>
            </div>
            <button type="submit"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                <i class="bi bi-funnel"></i> Filter
            </button>
            <a href="{{ url('admin/presensi/denda/laporan') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
            <div class="relative ml-0 min-w-[220px] md:ml-auto">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
                <input type="text" x-ref="cari" x-model="q" placeholder="Cari nama guru..." autocomplete="off"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-8 pr-8 text-xs font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                <button type="button" x-show="q !== ''" x-cloak @click="q = ''; $refs.cari.focus()"
                    class="absolute right-2 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-slate-200/70 hover:text-slate-700"
                    title="Bersihkan pencarian">
                    <i class="bi bi-x text-xs"></i>
                </button>
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-1.5 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
            <span class="mr-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Metode</span>
            @foreach ([['', 'Semua', null], ['TUNAI', 'Tunai', 'bg-emerald-500'], ['POIN', 'Poin', 'bg-purple-500'], ['SALDO', 'Saldo', 'bg-sky-400']] as [$kode, $teks, $titik])
                <button type="button" @click="pilihMetode('{{ $kode }}')"
                    :class="metodeAktif('{{ $kode }}')
                        ? 'border-blue-600 bg-blue-600 text-white'
                        : 'border-slate-200 bg-white text-slate-500 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600'"
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border-[1.5px] px-3 py-1.5 text-xs font-semibold transition-colors">
                    @if ($titik)
                        <span class="h-2 w-2 shrink-0 rounded-full {{ $titik }}"></span>
                    @endif
                    {{ $teks }}
                </button>
            @endforeach
        </div>
    </x-ui.card>

    <x-ui.card title="Riwayat Pembayaran" icon="bi-table" bodyClass="p-0">
        <x-slot:actions>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                <span x-text="ringkas"></span> • <span class="tabular-nums" x-text="totalTampilTeks"></span>
            </span>
        </x-slot:actions>

        <div class="max-h-[60vh] overflow-auto" tabindex="0">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center font-bold">#</th>
                        <th class="px-3 py-3 text-left font-bold">Tanggal</th>
                        <th class="min-w-[160px] px-3 py-3 text-left font-bold">Guru</th>
                        <th class="px-3 py-3 text-center font-bold">Periode</th>
                        <th class="min-w-[110px] px-3 py-3 text-right font-bold">Nominal</th>
                        <th class="px-3 py-3 text-center font-bold">Metode</th>
                        <th class="px-3 py-3 text-center font-bold">Status</th>
                        <th class="hidden min-w-[150px] px-3 py-3 text-left font-bold lg:table-cell">Keterangan</th>
                        <th class="hidden min-w-[130px] px-3 py-3 text-left font-bold lg:table-cell">Pencatat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr x-show="!baris.length">
                        <td colspan="9" class="px-3 py-12 text-center text-slate-400">
                            <i class="bi bi-inbox mb-2 block text-4xl"></i>
                            <p class="mb-0 text-sm font-semibold text-slate-500">Tidak ada pembayaran</p>
                            <p class="mb-0 text-xs">Ubah filter tanggal / guru / metode.</p>
                        </td>
                    </tr>

                    <template x-for="(row, index) in baris" :key="row.id + '-' + index">
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-2.5 text-center font-mono text-xs text-slate-400" x-text="index + 1"></td>
                            <td class="whitespace-nowrap px-3 py-2.5 font-semibold text-slate-800" x-text="row.tanggal"></td>
                            <td class="px-3 py-2.5">
                                <div class="text-sm font-semibold leading-tight text-slate-800" x-text="row.nama"></div>
                                <div x-show="row.nip" class="text-[11px] text-slate-400" x-text="row.nip"></div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-bold text-indigo-700" x-text="row.periode"></span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-bold tabular-nums"
                                :class="row.metode === 'POIN' ? 'text-violet-600' : (row.metode === 'SALDO' ? 'text-sky-600' : 'text-emerald-600')"
                                x-text="row.nominalTeks"></td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    :class="row.metode === 'POIN' ? 'bg-purple-50 text-purple-700' : (row.metode === 'SALDO' ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700')"
                                    x-text="row.metode === 'TUNAI' ? 'Tunai' : (row.metode === 'POIN' ? 'Poin' : 'Saldo')"></span>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold"
                                    :class="row.status === 'lunas'
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : (row.status === 'sebagian' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600')"
                                    x-text="row.statusLabel"></span>
                            </td>
                            <td class="hidden px-3 py-2.5 text-xs text-slate-400 lg:table-cell" :title="row.keterangan" x-text="row.keterangan"></td>
                            <td class="hidden px-3 py-2.5 text-xs lg:table-cell">
                                <div class="font-medium leading-tight text-slate-700" x-text="row.pencatat || '—'"></div>
                                <div class="text-[11px] text-slate-400" x-text="row.dicatat"></div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <div class="flex items-center justify-between text-xs text-slate-400 print:hidden">
                <span x-text="ringkas"></span>
                <span>{{ $filterTglDari }} — {{ $filterTglSampai }}</span>
            </div>
        </x-slot:footer>
    </x-ui.card>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('laporanPembayaran', (config = {}) => ({
        rows: config.rows || [],
        filter: Object.assign({ user_id: '', tgl_dari: '', tgl_sampai: '', metode: '' }, config.filter || {}),
        urlPdf: config.urlPdf || '',
        q: '',

        get baris() {
            const kata = this.q.trim().toLowerCase();

            return kata === '' ? this.rows : this.rows.filter((row) => row.cari.includes(kata));
        },

        get ringkas() {
            const tampil = this.baris.length;

            return tampil === this.rows.length
                ? tampil + ' transaksi'
                : 'Menampilkan ' + tampil + ' dari ' + this.rows.length + ' transaksi';
        },

        get totalTampilTeks() {
            const total = this.baris.reduce((jumlah, row) => jumlah + Number(row.nominal || 0), 0);

            return 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        },

        metodeAktif(kode) {
            return (this.filter.metode || '') === kode;
        },

        pilihMetode(kode) {
            this.filter.metode = kode;

            this.$nextTick(() => this.$refs.filterForm?.submit());
        },

        unduhPdf() {
            const params = new URLSearchParams();

            Object.entries(this.filter).forEach(([kunci, nilai]) => {
                if (nilai) params.append(kunci, nilai);
            });

            const query = params.toString();

            window.location.href = this.urlPdf + (query ? '?' + query : '');
        },
    }));
});
</script>
@endsection
