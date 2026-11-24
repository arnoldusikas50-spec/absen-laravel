@extends('layouts.main')

@section('content')

<?php
$isHariSekolah = $is_hari_sekolah ?? true;
$piketTanggal = $piket_tanggal_label ?? date('d M Y');
$piketHariLabel = $piket_hari_label ?? ($hari_ini_indo ?? '');

$miniStats = [
    ['label' => 'Tamu', 'value' => $stats_hari_ini['tamu'] ?? 0, 'icon' => 'bi-people-fill', 'soft' => 'bg-emerald-50 text-emerald-600', 'num' => 'text-emerald-600', 'bar' => 'border-l-emerald-500'],
    ['label' => 'Kejadian', 'value' => $stats_hari_ini['kejadian'] ?? 0, 'icon' => 'bi-exclamation-triangle-fill', 'soft' => 'bg-amber-50 text-amber-500', 'num' => 'text-amber-500', 'bar' => 'border-l-amber-500'],
    ['label' => 'Absen Siswa', 'value' => $stats_hari_ini['siswa_absen'] ?? 0, 'icon' => 'bi-person-x-fill', 'soft' => 'bg-rose-50 text-rose-500', 'num' => 'text-rose-500', 'bar' => 'border-l-rose-500'],
];

$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'save' => url('admin/piket/save_jadwal'),
        'generate' => url('admin/piket/generate-jadwal'),
        'delete' => url('admin/piket/delete_jadwal'),
    ],
    'hariList' => array_values($hari_list ?? []),
];
?>

<div x-data="piketJadwalPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <!-- Content Header -->
    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Jadwal Piket'"
            icon="bi-calendar-week"
            tone="blue"
            :subtitle="'Tahun Pelajaran ' . ($tp_active->tahun ?? '-') . ' · Semester ' . ($smt_active->smt ?? '-')"
            :crumbs="['Piket', 'Jadwal Mingguan']">
            <x-slot:actions>
                <a href="{{ url('admin/piket/monitoring') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2 text-xs font-semibold text-sky-700 no-underline shadow-xs transition-colors hover:bg-sky-100">
                    <i class="bi bi-eye"></i> Monitoring Harian
                </a>
                <button type="button" @click="openGenerate()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700 shadow-xs transition-colors hover:bg-emerald-100">
                    <i class="bi bi-magic"></i> Generate Otomatis
                </button>
                <button type="button" @click="openAdd()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700">
                    <i class="bi bi-plus-lg"></i> Tambah Petugas
                </button>
            </x-slot:actions>
        </x-ui.page-header>
    </div>

    <div class="mt-4 space-y-4 px-4 pb-6 md:px-6">

        <!-- Petugas hari ini + statistik -->
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">
            <div class="rounded-2xl border border-slate-200/80 border-l-4 border-l-blue-500 bg-white p-5 shadow-xs xl:col-span-2">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[10px] font-bold tracking-wider text-slate-400 uppercase">
                        Petugas Piket {{ $isHariSekolah ? 'Hari Ini' : 'Hari Sekolah Berikutnya' }}
                    </p>
                    @if (! $isHariSekolah)
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                            <i class="bi bi-calendar-x"></i> Libur
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-xs font-bold text-slate-700">{{ $piketTanggal }}</p>

                @if (! $isHariSekolah)
                    <p class="mt-1 text-[10px] leading-relaxed text-amber-600">
                        Hari ini {{ $libur_keterangan ?? 'libur' }}. Jadwal di bawah untuk {{ $piketHariLabel }} berikutnya.
                    </p>
                @endif

                <div class="mt-2.5">
                    @if (empty($piket_hari_ini))
                        <span class="text-sm text-slate-400 italic">
                            {{ $isHariSekolah ? 'Belum ada jadwal.' : 'Belum ada jadwal untuk hari sekolah berikutnya.' }}
                        </span>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @foreach ($piket_hari_ini as $petugas)
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
                                    <i class="bi bi-person-circle"></i> {{ $petugas->nama_guru }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @foreach ($miniStats as $stat)
                <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 border-l-4 bg-white p-4 shadow-xs {{ $stat['bar'] }}">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg {{ $stat['soft'] }}">
                        <i class="bi {{ $stat['icon'] }}"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold tracking-wider text-slate-400 uppercase">{{ $stat['label'] }}</p>
                        <p class="text-xl leading-tight font-extrabold {{ $stat['num'] }}">{{ (int) $stat['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Jadwal mingguan -->
        <x-ui.card title="Jadwal Guru Piket Mingguan" icon="bi-calendar-week" tone="blue"
            subtitle="Kelola jadwal petugas piket per hari" bodyClass="p-0">
            <x-ui.table class="min-w-[760px]">
                <x-slot:head>
                    @foreach ($hari_list as $hari)
                        <th class="px-4 py-3 text-center">{{ $hari }}</th>
                    @endforeach
                </x-slot:head>

                <tr>
                    @foreach ($hari_list as $hari)
                        @php $daftarPiket = $jadwal[$hari] ?? []; @endphp
                        <td class="border-t border-slate-100 bg-slate-50/60 p-3 align-top">
                            @if (! empty($daftarPiket))
                                <div class="space-y-2">
                                    @foreach ($daftarPiket as $item)
                                        <div class="relative rounded-xl border border-slate-200 bg-white p-3 shadow-xs">
                                            <div class="mb-1 pr-6 text-[13px] font-bold text-slate-800">
                                                {{ $item->nama_guru }}
                                            </div>
                                            @if (! empty($item->keterangan))
                                                <div class="mt-1 border-t border-slate-100 pt-1 text-[11px] text-slate-400 italic">
                                                    {{ $item->keterangan }}
                                                </div>
                                            @endif
                                            <button type="button" @click="removeJadwal({{ (int) $item->id }})" title="Hapus"
                                                class="absolute top-2 right-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-500">
                                                <i class="bi bi-x-circle-fill text-sm"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="py-8 text-center text-slate-300">
                                    <i class="bi bi-dash-lg text-2xl"></i>
                                </div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </x-ui.table>
        </x-ui.card>
    </div>

    <!-- Modal Tambah Petugas -->
    <x-ui.modal id="modalAddJadwal" title="Tambah Petugas Piket" icon="bi-person-plus-fill" tone="blue" size="max-w-lg">
        <form @submit.prevent="saveJadwal()" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div>
                    <label for="jadwal-hari" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Hari</label>
                    <select id="jadwal-hari" x-model="form.hari" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        @foreach ($hari_list as $hari)
                            <option value="{{ $hari }}">{{ $hari }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="jadwal-guru" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Guru</label>
                    <select id="jadwal-guru" x-model="form.id_guru" required
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                        <option value="">-- Pilih Guru --</option>
                        @foreach ($guru_list as $guru)
                            <option value="{{ (int) $guru->id_guru }}">{{ $guru->nama_guru }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="jadwal-ket" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Keterangan <span class="font-normal text-slate-400 normal-case">(opsional)</span>
                    </label>
                    <input type="text" id="jadwal-ket" x-model="form.keterangan" maxlength="500"
                        placeholder="Contoh: Shift Pagi (07:00 - 12:00)"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20">
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalAddJadwal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-check-lg"></i> Simpan
                </button>
            </div>
        </form>
    </x-ui.modal>

    <!-- Modal Generate Otomatis -->
    <x-ui.modal id="modalGenerateJadwal" title="Generate Petugas Piket" icon="bi-magic" tone="emerald" size="max-w-lg">
        <form @submit.prevent="generateJadwal()" class="flex min-h-0 flex-col">
            <div class="space-y-4 overflow-y-auto p-5">
                <div class="flex items-start gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/90 p-3.5 text-xs text-amber-800">
                    <i class="bi bi-shield-exclamation mt-0.5 shrink-0 text-lg text-amber-600"></i>
                    <div>
                        <strong class="font-bold text-amber-900">Pengecualian Khusus Kepala Sekolah</strong>
                        <p class="mt-0.5 leading-relaxed text-amber-700">
                            Kepala Sekolah <strong>({{ $setting->kepsek ?? 'Kepala Sekolah' }})</strong> otomatis dikecualikan dari jadwal petugas piket.
                        </p>
                    </div>
                </div>

                <div>
                    <label for="gen-jumlah" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Jumlah Guru Piket per Hari</label>
                    <div class="relative">
                        <input type="number" id="gen-jumlah" x-model.number="generate.jumlah_per_hari" min="1" max="10" required
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 pr-24 text-sm font-bold text-slate-800 transition-all outline-hidden focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                        <span class="absolute top-1/2 right-3.5 -translate-y-1/2 text-xs font-medium text-slate-400">guru / hari</span>
                    </div>
                    <p class="mt-1 text-[11px] text-slate-400">Disarankan 2 - 3 guru per hari sesuai kebutuhan sekolah.</p>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <span class="text-xs font-semibold tracking-wider text-slate-700 uppercase">Hari yang Dijadwalkan</span>
                        <button type="button" @click="toggleAllDays()"
                            class="text-[11px] font-semibold text-emerald-600 transition-colors hover:text-emerald-700"
                            x-text="allDaysSelected ? 'Batal Semua' : 'Pilih Semua'"></button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($hari_list as $hari)
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-slate-50/60 p-2.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-100">
                                <input type="checkbox" value="{{ $hari }}" x-model="generate.hari"
                                    class="h-4 w-4 rounded text-emerald-600 focus:ring-emerald-500">
                                <span>{{ $hari }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <span class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">Metode Generate</span>
                    <div class="space-y-2">
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-700 transition-all hover:border-emerald-400">
                            <input type="radio" value="replace" x-model="generate.mode"
                                class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="block font-bold text-slate-800">Ganti Jadwal Lama (Disarankan)</span>
                                <span class="text-[11px] text-slate-500">Hapus jadwal pada hari yang dipilih dan acak ulang seluruh pembagian tugas secara merata dari awal.</span>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 bg-white p-3 text-xs text-slate-700 transition-all hover:border-emerald-400">
                            <input type="radio" value="append" x-model="generate.mode"
                                class="mt-0.5 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="block font-bold text-slate-800">Lengkapi Slot Kosong Saja</span>
                                <span class="text-[11px] text-slate-500">Pertahankan petugas yang sudah ada dan hanya tambahkan guru hingga target per hari terpenuhi.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="gen-keterangan" class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase">
                        Keterangan Label <span class="font-normal text-slate-400 normal-case">(opsional)</span>
                    </label>
                    <input type="text" id="gen-keterangan" x-model="generate.keterangan" maxlength="255"
                        placeholder="Contoh: Petugas Piket"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm transition-all outline-hidden focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                <button type="button" @click="$store.modals.close('#modalGenerateJadwal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                <button type="submit" :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700 disabled:opacity-60">
                    <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                    <i x-show="!saving" class="bi bi-magic"></i> Mulai Generate
                </button>
            </div>
        </form>
    </x-ui.modal>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('piketJadwalPage', () => ({
        config: { csrf: '', urls: {}, hariList: [] },

        saving: false,

        form: { hari: '', id_guru: '', keterangan: '' },
        generate: { jumlah_per_hari: 2, hari: [], mode: 'replace', keterangan: 'Petugas Piket' },

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.config = Object.assign({ csrf: '', urls: {}, hariList: [] }, cfg);
            this.config.urls = cfg.urls || {};
            this.config.hariList = cfg.hariList || [];

            this.form.hari = this.config.hariList[0] || '';
            this.generate.hari = [...this.config.hariList];
        },

        get allDaysSelected() {
            return this.config.hariList.length > 0 && this.generate.hari.length === this.config.hariList.length;
        },

        openAdd() {
            this.form = {
                hari: this.config.hariList[0] || '',
                id_guru: '',
                keterangan: '',
            };
            this.$store.modals.open('#modalAddJadwal');
        },

        openGenerate() {
            this.generate = {
                jumlah_per_hari: 2,
                hari: [...this.config.hariList],
                mode: 'replace',
                keterangan: 'Petugas Piket',
            };
            this.$store.modals.open('#modalGenerateJadwal');
        },

        toggleAllDays() {
            this.generate.hari = this.allDaysSelected ? [] : [...this.config.hariList];
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
                const err = new Error((result && (result.message || result.msg)) || 'Gagal menghubungi server');
                err.result = result;
                throw err;
            }

            return result;
        },

        async saveJadwal() {
            if (this.saving) return;

            if (!this.form.hari || !this.form.id_guru) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih hari dan guru terlebih dahulu.' });
                return;
            }

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.save, [
                    ['hari', this.form.hari],
                    ['id_guru', this.form.id_guru],
                    ['keterangan', this.form.keterangan],
                ]);

                if (result && result.status) {
                    this.$store.modals.close('#modalAddJadwal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message || 'Jadwal tersimpan.',
                        timer: 1500,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menyimpan jadwal' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Gagal menghubungi server' });
            } finally {
                this.saving = false;
            }
        },

        async generateJadwal() {
            if (this.saving) return;

            if (this.generate.hari.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Pilih minimal satu hari untuk dijadwalkan' });
                return;
            }

            const konfirmasi = await Swal.fire({
                title: 'Generate Jadwal Piket?',
                text: this.generate.mode === 'replace'
                    ? 'Jadwal lama pada hari terpilih akan digantikan dengan pembagian tugas baru. Lanjutkan generate otomatis?'
                    : 'Sistem akan menambahkan guru ke slot yang masih kurang. Lanjutkan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Generate!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;

            try {
                const entries = [
                    ['jumlah_per_hari', this.generate.jumlah_per_hari],
                    ['mode', this.generate.mode],
                    ['keterangan', this.generate.keterangan],
                ];

                this.generate.hari.forEach((hari) => entries.push(['hari[]', hari]));

                const result = await this.post(this.config.urls.generate, entries);

                if (result && result.status) {
                    this.$store.modals.close('#modalGenerateJadwal');
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: result.message || 'Jadwal piket berhasil digenerate.',
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal generate jadwal' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Gagal menghubungi server' });
            } finally {
                this.saving = false;
            }
        },

        async removeJadwal(id) {
            if (this.saving) return;

            const konfirmasi = await Swal.fire({
                title: 'Hapus petugas ini dari jadwal?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            });

            if (!konfirmasi.isConfirmed) return;

            this.saving = true;

            try {
                const result = await this.post(this.config.urls.delete, [['id', id]]);

                if (result && result.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        timer: 1200,
                        showConfirmButton: false,
                    }).then(() => window.location.reload());
                    return;
                }

                Swal.fire({ icon: 'error', title: 'Gagal', text: (result && result.message) || 'Gagal menghapus' });
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Gagal menghubungi server' });
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endsection
