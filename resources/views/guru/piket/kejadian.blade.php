@extends('layouts.main')

@section('content')

<div x-data="piketKejadian('{{ url('guru/piket/save_kejadian') }}', '{{ url('guru/piket/delete_kejadian') }}', '{{ csrf_token() }}')">
<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul"
        icon="bi-exclamation-octagon"
        tone="amber"
        subtitle="Catatan Kejadian, Pelanggaran, dan Prestasi Siswa"
        :crumbs="['Piket', 'Kejadian']">
        <x-slot:actions>
            <a href="{{ url('guru/piket') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="button" @click="bukaModal()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-bold text-white shadow-xs shadow-amber-500/25 transition-colors hover:bg-amber-600">
                <i class="bi bi-plus-circle"></i> Catat Kejadian
            </button>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 space-y-5 px-4 pb-6 md:px-6">
    <x-ui.card title="Data Kejadian — {{ date('d M Y', strtotime($tanggal)) }}" icon="bi-calendar3" tone="amber" bodyClass="p-0">
        <x-slot:meta>
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-500">
                {{ count($data_kejadian) }} catatan
            </span>
        </x-slot:meta>

        @if (empty($data_kejadian))
            <div class="p-5">
                <x-ui.empty icon="bi-journal-x" title="Tidak ada data kejadian"
                    description="Belum ada kejadian yang dicatat pada tanggal ini." />
            </div>
        @else
            <x-ui.table class="min-w-[900px]">
                <x-slot:head>
                    <th class="w-16 px-4 py-3">Jam</th>
                    <th class="w-32 px-3 py-3">Jenis</th>
                    <th class="w-56 px-4 py-3">Siswa Terlapor</th>
                    <th class="px-4 py-3">Keterangan</th>
                    <th class="px-4 py-3">Penanganan / Tindak Lanjut</th>
                    <th class="w-20 px-4 py-3 text-center">Aksi</th>
                </x-slot:head>

                @foreach ($data_kejadian as $k)
                    @php
                        $badgeJenis = match ($k->jenis) {
                            'Pelanggaran' => 'border-rose-200 bg-rose-50 text-rose-700',
                            'Kejadian' => 'border-amber-200 bg-amber-50 text-amber-700',
                            'Sakit' => 'border-sky-200 bg-sky-50 text-sky-700',
                            'Prestasi' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            default => 'border-slate-200 bg-slate-50 text-slate-600',
                        };
                    @endphp
                    <tr class="transition-colors hover:bg-slate-50/60">
                        <td class="px-4 py-2.5 font-mono text-xs font-bold text-slate-700">
                            {{ date('H:i', strtotime((string) $k->jam)) }}
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $badgeJenis }}">
                                {{ $k->jenis }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            @if ($k->nama_siswa)
                                <div class="text-xs font-bold text-slate-800">{{ $k->nama_siswa }}</div>
                                <div class="mt-0.5 flex items-center gap-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                    <i class="bi bi-door-open"></i>{{ $k->nama_kelas }}
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="max-w-[280px] px-4 py-2.5 text-xs text-slate-500">{{ $k->keterangan }}</td>
                        <td class="px-4 py-2.5">
                            <div class="text-xs font-bold text-slate-700">{{ $k->penanganan }}</div>
                            @if ((string) $k->tindak_lanjut !== '')
                                <div class="mt-0.5 text-[11px] italic text-emerald-600">{{ $k->tindak_lanjut }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <button type="button" @click="hapus({{ $k->id }})" title="Hapus data kejadian"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-100 bg-rose-50 text-rose-600 transition-colors hover:bg-rose-600 hover:text-white">
                                <i class="bi bi-trash3 text-xs"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </x-ui.card>
</div>

{{-- Modal catat kejadian --}}
<div x-show="buka" x-cloak @keydown.escape.window="buka = false" @click.self="buka = false"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"
    aria-labelledby="judulModalKejadian">
    <div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <h2 id="judulModalKejadian" class="flex items-center gap-2 text-sm font-bold text-slate-800">
                <i class="bi bi-pencil-square text-amber-500"></i> Catat Kejadian Siswa
            </h2>
            <button type="button" @click="buka = false" aria-label="Tutup"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">&times;</button>
        </div>

        <form x-ref="formKejadian" @submit.prevent="simpan($event.target)">
            <div class="space-y-3 px-5 py-4">
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="jamKejadian" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Jam Kejadian</label>
                        <input type="time" name="jam" id="jamKejadian" value="{{ date('H:i') }}" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
                    </div>
                    <div>
                        <label for="jenisKejadian" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Jenis Kejadian</label>
                        <select name="jenis" id="jenisKejadian"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
                            <option value="Pelanggaran">Pelanggaran Tata Tertib</option>
                            <option value="Kejadian">Kejadian Khusus / Lainnya</option>
                            <option value="Sakit">Sakit / Kesehatan</option>
                            <option value="Prestasi">Prestasi</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="siswaKejadian" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Siswa Terlibat (Opsional)</label>
                    <select name="terlapor_siswa_id" id="siswaKejadian"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
                        <option value="">-- Pilih Siswa --</option>
                        @foreach ($siswa_list as $s)
                            <option value="{{ $s->id_siswa }}">{{ $s->nama_kelas }} - {{ $s->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="pelaporKejadian" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Pelapor / Saksi</label>
                    <input type="text" name="pelapor" id="pelaporKejadian" placeholder="Nama Guru/Siswa yang melapor"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20">
                </div>

                <div>
                    <label for="keteranganKejadian" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Keterangan Kejadian</label>
                    <textarea name="keterangan" id="keteranganKejadian" rows="3" required placeholder="Jelaskan kronologi singkat..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"></textarea>
                </div>

                <div>
                    <label for="penangananKejadian" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Penanganan / Tindak Lanjut</label>
                    <textarea name="penanganan" id="penangananKejadian" rows="2" placeholder="Apa yang dilakukan petugas piket?"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"></textarea>
                </div>

                <div>
                    <label for="tindakLanjutKejadian" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-emerald-600">Status Penyelesaian Akhir</label>
                    <input type="text" name="tindak_lanjut" id="tindakLanjutKejadian"
                        placeholder="e.g. Selesai, Perlu pembinaan BK, atau Panggil Orang Tua"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" @click="buka = false"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" :disabled="menyimpan"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-5 py-2 text-xs font-bold text-white shadow-xs shadow-amber-500/25 transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi" :class="menyimpan ? 'bi-arrow-repeat animate-spin' : 'bi-save'"></i>
                    <span x-text="menyimpan ? 'Menyimpan...' : 'Simpan Data'">Simpan Data</span>
                </button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('piketKejadian', (urlSimpan, urlHapus, token) => ({
        buka: false,
        menyimpan: false,

        bukaModal() {
            this.$refs.formKejadian.reset();
            this.buka = true;
        },

        simpan(form) {
            this.menyimpan = true;
            Swal.fire({ title: 'Menyimpan...', didOpen: () => Swal.showLoading() });

            fetch(urlSimpan, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
            })
                .then((response) => response.json())
                .then((res) => {
                    if (res.status) {
                        this.buka = false;
                        Swal.fire('Sukses', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Gagal menyimpan data kejadian.', 'error'))
                .finally(() => { this.menyimpan = false; });
        },

        hapus(id) {
            Swal.fire({
                title: 'Hapus Data?',
                text: 'Data kejadian ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const data = new FormData();
                data.append('id', id);

                fetch(urlHapus, {
                    method: 'POST',
                    body: data,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
                })
                    .then((response) => response.json())
                    .then((res) => {
                        if (res.status) {
                            Swal.fire('Terhapus!', res.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('Gagal', 'Data kejadian tidak bisa dihapus.', 'error'));
            });
        },
    }));
});
</script>
@endsection
