@extends('layouts.main')

@section('content')

<?php
$shiftList = $shifts ?? [];
$hariNama = [0 => 'Semua hari', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

$states = [];
foreach ($shiftList as $shift) {
    $states[(int) $shift->id] = (int) ($shift->active ?? 1) === 1;
}

$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'save' => url('admin/presensi/shift/save'),
        'delete' => url('admin/presensi/shift/delete'),
        'assign' => url('admin/presensi/shift/assign'),
        'unassign' => url('admin/presensi/shift/unassign'),
        'toggle' => url('admin/presensi/shift/toggle'),
    ],
    'states' => $states,
];
?>

<div x-data="shiftPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Shift Kerja Guru'"
            icon="bi-clock-history"
            tone="blue"
            subtitle="Guru tanpa shift memakai jadwal harian global. Penugasan hari spesifik mengalahkan penugasan semua-hari."
            :crumbs="['Presensi', 'Shift Kerja']">
            <x-slot:actions>
                <button type="button" @click="openModal()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-plus-lg"></i> Tambah Shift
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 px-4 pb-6 md:px-6">
        <x-ui.card title="Daftar Shift" icon="bi-clock" tone="blue"
            subtitle="Atur jam kerja, toleransi keterlambatan, dan penugasan guru per hari"
            bodyClass="p-5">
            <x-slot:actions>
                @if (count($shiftList) > 0)
                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">
                        {{ count($shiftList) }} shift
                    </span>
                @endif
            </x-slot:actions>

            @if (count($shiftList) === 0)
                <x-ui.empty icon="bi-clock-history" title="Belum ada shift"
                    description="Semua guru memakai jadwal global. Buat shift pertama untuk mengatur jam kerja khusus.">
                    <button type="button" @click="openModal()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                        <i class="bi bi-plus-lg"></i> Buat Shift Pertama
                    </button>
                </x-ui.empty>
            @else
                <div class="space-y-4">
                    @foreach ($shiftList as $shift)
                        @php
                            $shiftId = (int) $shift->id;
                            $anggotaShift = $anggota[$shift->id] ?? [];
                            $shiftData = [
                                'id' => $shiftId,
                                'nama' => $shift->nama ?? '',
                                'jam_masuk' => substr($shift->jam_masuk ?? '', 0, 5),
                                'jam_pulang' => substr($shift->jam_pulang ?? '', 0, 5),
                                'grace_menit' => $shift->grace_menit ?? null,
                            ];
                        @endphp

                        <div class="rounded-xl border p-4 transition-colors"
                            :class="active[{{ $shiftId }}] ? 'border-slate-200 bg-white' : 'border-dashed border-slate-300 bg-slate-50/70'">

                            <!-- Baris utama shift -->
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                        :class="active[{{ $shiftId }}] ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    <strong class="text-slate-800">{{ $shift->nama }}</strong>

                                    <span x-show="!active[{{ $shiftId }}]" x-cloak
                                        class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-500">
                                        Nonaktif
                                    </span>

                                    <span class="font-mono text-sm text-slate-400">
                                        {{ substr($shift->jam_masuk ?? '', 0, 5) }} – {{ substr($shift->jam_pulang ?? '', 0, 5) }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600">
                                        Tol. {{ $shift->grace_menit !== null ? ((int) $shift->grace_menit . ' mnt') : 'global' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full border border-blue-100 bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700">
                                        <i class="bi bi-people"></i> {{ (int) ($counts[$shift->id] ?? 0) }} guru
                                    </span>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <label class="inline-flex cursor-pointer items-center gap-2"
                                        :title="active[{{ $shiftId }}] ? 'Klik untuk menonaktifkan shift' : 'Klik untuk mengaktifkan shift'">
                                        <input type="checkbox" class="peer sr-only"
                                            :checked="active[{{ $shiftId }}]"
                                            :disabled="busyShift === {{ $shiftId }}"
                                            @change="toggleShift({{ $shiftId }}, $event)">
                                        <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full bg-slate-300 transition-colors after:absolute after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500/40"></span>
                                    </label>

                                    <button type="button" @click="openModal({{ Js::from($shiftData) }})"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-600 transition-colors hover:bg-blue-50">
                                        <i class="bi bi-pencil-square"></i> Ubah
                                    </button>
                                    <button type="button" @click="deleteShift({{ $shiftId }}, {{ Js::from($shift->nama ?? '') }})"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition-colors hover:bg-rose-50">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>

                            <!-- Form penugasan guru -->
                            <form class="mt-4" @submit.prevent="assignShift({{ $shiftId }}, $event.target)">
                                <div class="grid grid-cols-1 items-end gap-2 md:grid-cols-12">
                                    <div class="md:col-span-6">
                                        <label for="assign-guru-{{ $shiftId }}" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Guru</label>
                                        <select id="assign-guru-{{ $shiftId }}" name="user_id" required
                                            :disabled="!active[{{ $shiftId }}]"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-60">
                                            <option value="">— Pilih guru —</option>
                                            @foreach ($guruList as $guru)
                                                <option value="{{ $guru->id }}">{{ $guru->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label for="assign-hari-{{ $shiftId }}" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Hari</label>
                                        <select id="assign-hari-{{ $shiftId }}" name="hari"
                                            :disabled="!active[{{ $shiftId }}]"
                                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-60">
                                            @foreach ($hariNama as $hariKe => $label)
                                                <option value="{{ $hariKe }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <button type="submit" :disabled="!active[{{ $shiftId }}] || busyAssign === {{ $shiftId }}"
                                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                                            <i class="bi bi-person-plus"></i> Tugaskan
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div x-show="!active[{{ $shiftId }}]" x-cloak
                                class="mt-2.5 flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-100 px-3 py-2 text-xs text-slate-500">
                                <i class="bi bi-info-circle shrink-0 text-slate-400"></i>
                                Shift nonaktif diabaikan sistem dan tidak menerima penugasan baru. Aktifkan kembali untuk menugaskan guru.
                            </div>

                            <!-- Daftar guru yang ditugaskan -->
                            <div class="mt-3">
                                @if (count($anggotaShift) === 0)
                                    <p class="text-xs text-slate-400">Belum ada guru yang ditugaskan ke shift ini.</p>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($anggotaShift as $anggotaRow)
                                            <span class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600">
                                                <i class="bi bi-person text-slate-400"></i>
                                                <span class="font-medium text-slate-700">{{ $anggotaRow->nama_guru }}</span>
                                                <span class="text-slate-400">· {{ $hariNama[(int) $anggotaRow->hari] ?? $anggotaRow->hari }}</span>
                                                <button type="button" title="Lepaskan penugasan"
                                                    @click="unassignShift({{ (int) $anggotaRow->id }}, {{ Js::from($anggotaRow->nama_guru ?? '') }})"
                                                    class="ml-0.5 inline-flex h-4 w-4 items-center justify-center rounded-full border-0 bg-transparent p-0 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600">
                                                    <i class="bi bi-x-lg text-xs"></i>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>

    <!-- Modal Tambah / Ubah Shift -->
    <x-ui.modal id="modalShift" icon="bi-clock" tone="blue" size="max-w-lg">
        <x-slot:header>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                        <i class="bi bi-clock text-sm"></i>
                    </span>
                    <span class="truncate" x-text="form.id ? 'Ubah Shift' : 'Tambah Shift'"></span>
                </h2>
                <button type="button" @click="$store.modals.close('#modalShift')" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
        </x-slot:header>

        <form @submit.prevent="saveShift()" autocomplete="off" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="shiftNama" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Nama Shift <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="shiftNama" x-model="form.nama" maxlength="100" required autocomplete="off"
                        placeholder="Contoh: Shift Sore"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="shiftMasuk" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                            Jam Masuk <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" id="shiftMasuk" x-model="form.jam_masuk" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="shiftPulang" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                            Jam Pulang <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" id="shiftPulang" x-model="form.jam_pulang" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>

                <div>
                    <label for="shiftGrace" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Toleransi (menit, opsional)</label>
                    <input type="number" id="shiftGrace" x-model="form.grace_menit" min="0" max="120"
                        placeholder="Kosong = ikut global"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1.5 text-xs text-slate-400">Jam pulang harus setelah jam masuk (shift overnight belum didukung).</p>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalShift')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-check2-circle"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('shiftPage', () => ({
        config: { csrf: '', urls: {}, states: {} },

        form: { id: '', nama: '', jam_masuk: '13:00', jam_pulang: '18:00', grace_menit: '' },
        active: {},
        busyShift: null,
        busyAssign: null,
        saving: false,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');

            this.config = {
                csrf: cfg.csrf || '',
                urls: cfg.urls || {},
                states: cfg.states || {},
            };
            this.active = Object.assign({}, this.config.states);
        },

        async post(url, entries) {
            const body = new URLSearchParams();
            body.append('_token', this.config.csrf);

            entries.forEach(([key, value]) => body.append(key, value ?? ''));

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': this.config.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });

            let result = null;

            try {
                result = await response.json();
            } catch (error) {
                result = null;
            }

            if (!response.ok) {
                const err = new Error((result && (result.message || result.msg)) || 'Terjadi kesalahan koneksi server.');
                err.result = result;
                throw err;
            }

            return result;
        },

        openModal(data = null) {
            this.form = {
                id: data && data.id ? String(data.id) : '',
                nama: (data && data.nama) || '',
                jam_masuk: (data && data.jam_masuk) || '13:00',
                jam_pulang: (data && data.jam_pulang) || '18:00',
                grace_menit: data && data.grace_menit !== null && data.grace_menit !== undefined ? String(data.grace_menit) : '',
            };

            this.$store.modals.open('#modalShift');
            this.$nextTick(() => {
                const input = document.getElementById('shiftNama');

                if (input) input.focus();
            });
        },

        async saveShift() {
            if (this.saving) return;

            const nama = (this.form.nama || '').trim();
            const masuk = this.form.jam_masuk || '';
            const pulang = this.form.jam_pulang || '';

            if (!nama) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Nama shift wajib diisi.' });
                return;
            }

            if (!masuk || !pulang) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Jam masuk dan jam pulang wajib diisi.' });
                return;
            }

            if (pulang <= masuk) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Jam pulang harus setelah jam masuk (shift overnight belum didukung).' });
                return;
            }

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.save, [
                    ['id', this.form.id],
                    ['nama', nama],
                    ['jam_masuk', masuk],
                    ['jam_pulang', pulang],
                    ['grace_menit', this.form.grace_menit],
                ]);

                if (result && result.status) {
                    this.$store.modals.close('#modalShift');
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan!',
                        text: result.message || 'Shift berhasil disimpan.',
                        timer: 1000,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menyimpan shift.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Terjadi kesalahan koneksi server.' });
            } finally {
                this.saving = false;
            }
        },

        async toggleShift(id, event) {
            const next = !!event.target.checked;

            if (!next) {
                const konfirmasi = await Swal.fire({
                    title: 'Nonaktifkan shift?',
                    text: 'Guru yang ditugaskan akan kembali memakai jadwal default dan shift tidak menerima penugasan baru.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d97706',
                    confirmButtonText: 'Ya, Nonaktifkan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                });

                if (!konfirmasi.isConfirmed) {
                    event.target.checked = true;
                    return;
                }
            }

            this.busyShift = id;

            try {
                const result = await this.post(this.config.urls.toggle, [
                    ['id', id],
                    ['active', next ? 1 : 0],
                ]);

                if (result && result.status) {
                    this.active[id] = next;
                    window.location.reload();
                    return;
                }

                throw new Error((result && result.message) || 'Gagal mengubah status shift.');
            } catch (error) {
                event.target.checked = !next;
                Swal.fire({ icon: 'error', title: 'Gagal', text: error.message || 'Terjadi kesalahan koneksi server.' });
            } finally {
                this.busyShift = null;
            }
        },

        async deleteShift(id, nama) {
            const konfirmasi = await Swal.fire({
                title: 'Hapus shift' + (nama ? ' "' + nama + '"' : '') + '?',
                text: 'Semua penugasan guru pada shift ini ikut terhapus. Tindakan ini tidak bisa dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.delete, [['id', id]]);

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: result.message || 'Shift berhasil dihapus.',
                        timer: 1300,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menghapus shift.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Terjadi kesalahan koneksi server.' });
            }
        },

        async assignShift(shiftId, formEl) {
            if (!this.active[shiftId]) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Aktifkan shift terlebih dahulu untuk menugaskan guru.' });
                return;
            }

            if (this.busyAssign === shiftId) return;

            const data = new FormData(formEl);
            const userId = data.get('user_id');

            if (!userId) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih guru terlebih dahulu.' });
                return;
            }

            this.busyAssign = shiftId;

            try {
                const result = await this.post(this.config.urls.assign, [
                    ['shift_id', shiftId],
                    ['user_id', userId],
                    ['hari', data.get('hari')],
                ]);

                if (result && result.status) {
                    window.location.reload();
                    return;
                }

                throw new Error((result && result.message) || 'Gagal menugaskan guru.');
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Gagal', text: error.message || 'Terjadi kesalahan koneksi server.' });
            } finally {
                this.busyAssign = null;
            }
        },

        async unassignShift(id, nama) {
            const konfirmasi = await Swal.fire({
                title: 'Lepaskan penugasan' + (nama ? ' "' + nama + '"' : '') + '?',
                text: 'Guru ini akan kembali memakai jadwal default untuk hari tersebut.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Ya, Lepaskan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            try {
                const result = await this.post(this.config.urls.unassign, [['id', id]]);

                if (result && result.status) {
                    window.location.reload();
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal melepas penugasan.' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Terjadi kesalahan koneksi server.' });
            }
        },
    }));
});
</script>
@endsection
