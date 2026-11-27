@extends('layouts.main')

@section('content')

<div x-data="piketTamu('{{ url('guru/piket/save_tamu') }}', '{{ url('guru/piket/delete_tamu') }}', '{{ csrf_token() }}')">
<div class="px-4 pt-4 md:px-6 md:pt-6">
    <x-ui.page-header
        :title="$judul"
        icon="bi-person-badge"
        tone="violet"
        subtitle="Registrasi Tamu dan Kunjungan Sekolah"
        :crumbs="['Piket', 'Buku Tamu']">
        <x-slot:actions>
            <a href="{{ url('guru/piket') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 no-underline shadow-xs transition-colors hover:bg-slate-50">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <button type="button" @click="bukaModal()"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs shadow-violet-500/25 transition-colors hover:bg-violet-700">
                <i class="bi bi-person-plus-fill"></i> Registrasi Tamu
            </button>
        </x-slot:actions>
    </x-ui.page-header>
</div>

<div class="mt-4 space-y-5 px-4 pb-6 md:px-6">
    <x-ui.card title="Daftar Tamu — {{ date('d M Y', strtotime($tanggal)) }}" icon="bi-calendar3" tone="violet" bodyClass="p-0">
        <x-slot:meta>
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-bold text-slate-500">
                {{ count($data_tamu) }} kunjungan
            </span>
        </x-slot:meta>

        @if (empty($data_tamu))
            <div class="p-5">
                <x-ui.empty icon="bi-person-vcard" title="Tidak ada tamu hari ini"
                    description="Belum ada tamu yang terdaftar pada tanggal ini." />
            </div>
        @else
            <x-ui.table class="min-w-[860px]">
                <x-slot:head>
                    <th class="w-24 px-4 py-3">Jam Masuk</th>
                    <th class="px-4 py-3">Nama Lengkap Tamu</th>
                    <th class="px-4 py-3">Asal Instansi / Alamat</th>
                    <th class="px-4 py-3">Bertemu Dengan</th>
                    <th class="px-4 py-3">Tujuan / Keperluan</th>
                    <th class="w-20 px-4 py-3 text-center">Aksi</th>
                </x-slot:head>

                @foreach ($data_tamu as $t)
                    <tr class="transition-colors hover:bg-slate-50/60">
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-2.5 py-0.5 font-mono text-[11px] font-bold text-violet-700">
                                {{ date('H:i', strtotime((string) $t->jam_masuk)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-xs font-bold text-slate-800">{{ $t->nama }}</td>
                        <td class="px-4 py-2.5 text-xs text-slate-500">
                            <i class="bi bi-building me-1 text-slate-400"></i>{{ $t->instansi }}
                        </td>
                        <td class="px-4 py-2.5 text-xs font-bold text-violet-700">{{ $t->bertemu }}</td>
                        <td class="px-4 py-2.5 text-xs text-slate-500">{{ $t->keperluan }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <button type="button" @click="hapus({{ $t->id }})" title="Hapus data tamu"
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

{{-- Modal registrasi tamu --}}
<div x-show="buka" x-cloak @keydown.escape.window="buka = false" @click.self="buka = false"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true"
    aria-labelledby="judulModalTamu">
    <div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl" @click.stop>
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <h2 id="judulModalTamu" class="flex items-center gap-2 text-sm font-bold text-slate-800">
                <i class="bi bi-person-rolodex text-violet-600"></i> Form Registrasi Tamu
            </h2>
            <button type="button" @click="buka = false" aria-label="Tutup"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">&times;</button>
        </div>

        <form x-ref="formTamu" @submit.prevent="simpan($event.target)">
            <div class="space-y-3 px-5 py-4">
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="jamMasukTamu" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="jamMasukTamu" value="{{ date('H:i') }}" required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20">
                    </div>
                </div>

                <div>
                    <label for="namaTamu" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Nama Lengkap Tamu</label>
                    <input type="text" name="nama" id="namaTamu" required placeholder="Masukkan nama tamu..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20">
                </div>

                <div>
                    <label for="instansiTamu" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Instansi / Alamat</label>
                    <input type="text" name="instansi" id="instansiTamu" placeholder="e.g. Dinas Pendidikan / Alamat Rumah"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20">
                </div>

                <div>
                    <label for="bertemuTamu" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Bertemu Dengan</label>
                    <input type="text" name="bertemu" id="bertemuTamu" required placeholder="Nama Guru / Staf yang dituju"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20">
                </div>

                <div>
                    <label for="keperluanTamu" class="mb-1 block text-[11px] font-bold uppercase tracking-wider text-slate-500">Tujuan / Keperluan</label>
                    <textarea name="keperluan" id="keperluanTamu" rows="3" placeholder="Deskripsikan singkat keperluan bertamu..."
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-hidden focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                <button type="button" @click="buka = false"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit" :disabled="menyimpan"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-5 py-2 text-xs font-bold text-white shadow-xs shadow-violet-500/25 transition-colors hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi" :class="menyimpan ? 'bi-arrow-repeat animate-spin' : 'bi-save'"></i>
                    <span x-text="menyimpan ? 'Menyimpan...' : 'Simpan Data Tamu'">Simpan Data Tamu</span>
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
    Alpine.data('piketTamu', (urlSimpan, urlHapus, token) => ({
        buka: false,
        menyimpan: false,

        bukaModal() {
            this.$refs.formTamu.reset();
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
                .catch(() => Swal.fire('Error', 'Gagal menyimpan data tamu.', 'error'))
                .finally(() => { this.menyimpan = false; });
        },

        hapus(id) {
            Swal.fire({
                title: 'Hapus Data Tamu?',
                text: 'Data kunjungan tamu ini akan dihapus permanen!',
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
                    .catch(() => Swal.fire('Gagal', 'Data tamu tidak bisa dihapus.', 'error'));
            });
        },
    }));
});
</script>
@endsection
