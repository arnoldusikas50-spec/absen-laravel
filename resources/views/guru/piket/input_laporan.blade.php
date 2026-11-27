@extends('layouts.main')

@section('content')

<div x-data="piketLaporan('{{ url('guru/piket/save_laporan_guru') }}', '{{ csrf_token() }}')">
<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul"
        icon="bi-pencil-square"
        tone="blue"
        subtitle="Input Laporan Kehadiran Guru Harian (Cross-Check Manual)"
        :crumbs="['Piket', 'Input Laporan']">
        <x-slot:actions>
            <a href="{{ url('guru/piket/laporan') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2 text-xs font-bold text-blue-700 no-underline shadow-xs transition-colors hover:bg-blue-100">
                <i class="bi bi-file-earmark-bar-graph"></i> Lihat Hasil Rekap
            </a>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 space-y-5 px-4 pb-6 md:px-6">

    <x-ui.card bodyClass="p-0">
        <x-slot:toolbar>
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="tanggal" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Tanggal Piket</label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()"
                        class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                </div>
                <noscript>
                    <button type="submit"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 text-xs font-bold text-white">Tampilkan</button>
                </noscript>
                <p class="pb-2 text-[11px] text-slate-500">
                    <i class="bi bi-info-circle me-1"></i> Data disimpan otomatis saat status diubah.
                </p>
            </form>
        </x-slot:toolbar>
    </x-ui.card>

    @if (empty($report))
        <x-ui.card bodyClass="p-0">
            <div class="p-6">
                <x-ui.empty icon="bi-calendar-x" title="Jadwal tidak ditemukan"
                    description="Tidak ada jadwal KBM untuk tanggal yang dipilih." />
            </div>
        </x-ui.card>
    @else
        @foreach ($report as $idKls => $kls)
            <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                <div x-show="menyimpan[{{ $idKls }}]" x-cloak
                    class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                    <i class="bi bi-arrow-repeat animate-spin text-2xl text-blue-600"></i>
                </div>

                <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800">
                        <i class="bi bi-building text-blue-600"></i> Kelas: {{ $kls['nama_kelas'] }}
                    </h2>
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-500">
                        {{ count($kls['slots']) }} jam
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[880px] border-collapse text-left text-xs">
                        <thead class="bg-slate-50/80 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            <tr class="border-b border-slate-200">
                                <th class="w-20 px-4 py-2.5">Jam</th>
                                <th class="px-3 py-2.5">Mata Pelajaran</th>
                                <th class="px-3 py-2.5">Guru (Sesuai Jadwal)</th>
                                <th class="w-48 px-3 py-2.5">Status Kehadiran</th>
                                <th class="w-72 px-4 py-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($kls['slots'] as $s)
                                @php
                                    $manual = $s['manual'];
                                    $statusAwal = $manual->status ?? '';
                                @endphp
                                <tr x-data="{ status: '{{ $statusAwal }}' }" class="transition-colors hover:bg-slate-50/60">
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 text-[11px] font-bold text-slate-600">{{ $s['jam_ke'] }}</span>
                                    </td>
                                    <td class="px-3 py-2.5 font-bold text-slate-800">{{ $s['nama_mapel'] }}</td>
                                    <td class="px-3 py-2.5 {{ $s['nama_guru'] == 'Belum Diset' ? 'font-bold text-rose-600' : 'text-slate-600' }}">
                                        {{ $s['nama_guru'] }}
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <select x-model="status"
                                            @change="simpan($el, {{ $idKls }}, {{ $s['jam_ke'] }}, {{ $s['id_mapel'] }}, {{ $s['id_guru'] ?: 0 }})"
                                            aria-label="Status kehadiran guru"
                                            :class="{
                                                'border-slate-200 bg-white text-slate-600': status === '',
                                                'border-emerald-500 bg-emerald-50 text-emerald-700': status === 'H',
                                                'border-sky-500 bg-sky-50 text-sky-700': status === 'S',
                                                'border-amber-500 bg-amber-50 text-amber-700': status === 'I',
                                                'border-rose-500 bg-rose-50 text-rose-700': status === 'A',
                                            }"
                                            class="w-full cursor-pointer rounded-xl border-2 px-3 py-1.5 text-xs font-semibold outline-hidden transition-colors focus:ring-2 focus:ring-blue-500/20">
                                            <option value="">-- Pilih Status --</option>
                                            <option value="H">Hadir</option>
                                            <option value="S">Sakit</option>
                                            <option value="I">Izin</option>
                                            <option value="A">Alpa</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                                                <i class="bi bi-chat-dots"></i>
                                            </span>
                                            <input type="text" placeholder="Catatan (opsional)..." value="{{ $manual->keterangan ?? '' }}"
                                                aria-label="Catatan laporan piket"
                                                @blur="simpan($el, {{ $idKls }}, {{ $s['jam_ke'] }}, {{ $s['id_mapel'] }}, {{ $s['id_guru'] ?: 0 }})"
                                                class="w-full rounded-xl border border-slate-200 bg-white py-1.5 pr-3 pl-8 text-xs text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
</div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('piketLaporan', (url, token) => ({
        menyimpan: {},

        simpan(el, idKelas, jamKe, idMapel, idGuru) {
            const baris = el.closest('tr');
            const select = baris.querySelector('select');
            const catatan = baris.querySelector('input[type=text]');
            const status = select.value;
            const keterangan = catatan.value;

            if (!status && keterangan.trim() === '') {
                return;
            }

            if (!status) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Status wajib dipilih',
                    text: 'Pilih Hadir/Sakit/Izin/Alpa sebelum menyimpan catatan.',
                });
                return;
            }

            const data = new FormData();
            data.append('tanggal', '{{ $tanggal }}');
            data.append('id_kelas', idKelas);
            data.append('jam_ke', jamKe);
            data.append('id_mapel', idMapel);
            data.append('id_guru', idGuru);
            data.append('status', status);
            data.append('keterangan', keterangan);

            this.menyimpan[idKelas] = true;

            fetch(url, {
                method: 'POST',
                body: data,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
            })
                .then((response) => response.json())
                .then((res) => {
                    if (res.status) {
                        Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        }).fire({ icon: 'success', title: res.message });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                })
                .catch((err) => {
                    console.error(err);
                    Swal.fire('Error', 'Laporan piket gagal disimpan.', 'error');
                })
                .finally(() => {
                    this.menyimpan[idKelas] = false;
                });
        },
    }));
});
</script>
@endsection
