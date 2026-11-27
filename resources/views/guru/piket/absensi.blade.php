@extends('layouts.main')

@section('content')

<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul"
        icon="bi-calendar-check"
        tone="rose"
        subtitle="Input Rekapitulasi Absensi Per Kelas oleh Piket"
        :crumbs="['Piket', 'Absen Kelas']">
        <x-slot:actions>
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
            <form method="get" class="flex flex-wrap items-end gap-3">
                <div class="mr-1 flex items-center gap-2">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50">
                        <i class="bi bi-calendar-event text-sm text-rose-600"></i>
                    </span>
                    <h2 class="text-sm font-bold text-slate-700">Pilih Tanggal &amp; Kelas</h2>
                </div>

                <div>
                    <label for="tanggal" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}"
                        class="h-9 rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 outline-hidden focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
                </div>

                <div>
                    <label for="kelas" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Kelas</label>
                    <select name="kelas" id="kelas" onchange="this.form.submit()"
                        class="h-9 min-w-[180px] rounded-xl border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 outline-hidden focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach ($kelas_list as $k)
                            <option value="{{ $k->id_kelas }}" @selected((int) $kelas_selected === (int) $k->id_kelas)>
                                {{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                    class="inline-flex h-9 items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 text-xs font-bold text-white shadow-xs shadow-rose-500/20 transition-colors hover:bg-rose-700">
                    <i class="bi bi-search"></i> Tampilkan
                </button>
            </form>
        </x-slot:toolbar>
    </x-ui.card>

    @if ($kelas_selected && ! empty($siswa_absensi))
        <x-ui.card :title="'Absensi Kelas — ' . date('d/m/Y', strtotime($tanggal))" icon="bi-people-fill" tone="rose" bodyClass="p-0">
            <form id="formAbsensi"
                x-data="piketAbsensi('{{ url('guru/piket/save_absensi') }}', '{{ csrf_token() }}')"
                @submit.prevent="simpan($event.target)">
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                <input type="hidden" name="id_kelas" value="{{ $kelas_selected }}">

                <x-ui.table class="min-w-[760px]">
                    <x-slot:head>
                        <th class="w-14 px-4 py-3 text-center">No</th>
                        <th class="w-40 px-4 py-3">NIS / Username</th>
                        <th class="px-4 py-3">Nama Lengkap Siswa</th>
                        <th class="px-4 py-3">Status Kehadiran</th>
                    </x-slot:head>

                    @foreach ($siswa_absensi as $i => $s)
                        @php
                            $statusAwal = $s->status ?? '';
                            $pilihan = [
                                ['kode' => 'H', 'label' => 'Hadir', 'kelas' => 'peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700'],
                                ['kode' => 'S', 'label' => 'Sakit', 'kelas' => 'peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700'],
                                ['kode' => 'I', 'label' => 'Izin', 'kelas' => 'peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700'],
                                ['kode' => 'A', 'label' => 'Alpha', 'kelas' => 'peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700'],
                                ['kode' => 'T', 'label' => 'Telat', 'kelas' => 'peer-checked:border-slate-500 peer-checked:bg-slate-100 peer-checked:text-slate-700'],
                            ];
                        @endphp
                        <tr x-data="{ status: '{{ $statusAwal }}' }"
                            :class="{
                                'bg-emerald-50/40': status === 'H',
                                'bg-sky-50/40': status === 'S',
                                'bg-amber-50/40': status === 'I',
                                'bg-rose-50/40': status === 'A',
                                'bg-slate-50': status === 'T',
                            }"
                            class="transition-colors">
                            <td class="px-4 py-2.5 text-center text-xs font-bold text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2.5 font-mono text-xs text-slate-500">{{ $s->nis }}</td>
                            <td class="px-4 py-2.5 text-sm font-bold text-slate-800">{{ $s->nama }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($pilihan as $opt)
                                        <label class="relative">
                                            <input type="radio" name="absensi[{{ $s->id_siswa }}]" value="{{ $opt['kode'] }}"
                                                x-model="status" @checked($statusAwal === $opt['kode']) class="peer sr-only">
                                            <span class="inline-flex cursor-pointer items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-slate-500 transition-colors hover:bg-slate-50 {{ $opt['kelas'] }}">
                                                {{ $opt['label'] }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                <div class="flex flex-col items-stretch justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-3.5 sm:flex-row sm:items-center">
                    <p class="text-[11px] text-slate-500">
                        <i class="bi bi-info-circle me-1"></i>
                        Pilih status kehadiran setiap siswa, lalu simpan rekap.
                    </p>
                    <button type="submit" :disabled="menyimpan"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs shadow-emerald-500/25 transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="bi" :class="menyimpan ? 'bi-arrow-repeat animate-spin' : 'bi-cloud-arrow-up'"></i>
                        <span x-text="menyimpan ? 'Menyimpan...' : 'SIMPAN REKAP ABSENSI'">SIMPAN REKAP ABSENSI</span>
                    </button>
                </div>
            </form>
        </x-ui.card>
    @else
        <x-ui.card bodyClass="p-0">
            <div class="p-6">
                <x-ui.empty icon="bi-file-earmark-spreadsheet" title="Belum Ada Data Terpilih"
                    description="Silakan tentukan Tanggal dan Kelas pada filter di atas untuk menginput absensi." />
            </div>
        </x-ui.card>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('piketAbsensi', (url, token) => ({
        menyimpan: false,

        simpan(form) {
            const kelas = form.querySelector('input[name="id_kelas"]').value;
            const terisi = Array.from(form.querySelectorAll('input[type=radio]:checked')).length;
            const total = form.querySelectorAll('tbody tr').length;

            if (terisi === 0) {
                Swal.fire({ icon: 'warning', title: 'Belum ada status', text: 'Pilih status kehadiran minimal satu siswa.' });
                return;
            }

            const periksa = terisi < total
                ? { title: 'Simpan Absensi?', text: 'Masih ada ' + (total - terisi) + ' siswa tanpa status. Simpan tetap?' }
                : { title: 'Simpan Absensi?', text: 'Pastikan data kehadiran sudah benar untuk kelas ini.' };

            Swal.fire({
                ...periksa,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                this.menyimpan = true;
                Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });

                fetch(url, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                })
                    .then((response) => response.json())
                    .then((res) => {
                        Swal.fire({
                            icon: res.status ? 'success' : 'error',
                            title: res.status ? 'Berhasil' : 'Gagal',
                            text: res.message,
                        });
                    })
                    .catch(() => {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: 'Terjadi kesalahan saat menyimpan absensi.' });
                    })
                    .finally(() => {
                        this.menyimpan = false;
                    });
            });
        },
    }));
});
</script>
@endsection
