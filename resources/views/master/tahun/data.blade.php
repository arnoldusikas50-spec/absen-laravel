@extends('layouts.main')

@section('content')

@php
    $tpActiveYear = $tp_active->tahun ?? 'Belum Diatur';
    $smtActiveName = $smt_active->smt ?? 'Belum Diatur';

    $tpOptions = collect($tp)->map(fn ($t) => ['id' => (int) $t->id_tp, 'tp' => $t->tahun])->values()->all();
    $smtOptions = collect($smt)->map(fn ($s) => ['id' => (int) $s->id_smt, 'Semester' => $s->smt])->values()->all();
@endphp

<div class="fade-in-up space-y-4 px-4 pt-6 md:px-6"
    x-data="periodePage"
    data-token="{{ csrf_token() }}"
    data-tp-list="{{ json_encode($tpOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    data-smt-list="{{ json_encode($smtOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    data-url-ganti-tahun="{{ url('datatahun/gantitahun') }}"
    data-url-ganti-smt="{{ url('datatahun/gantisemester') }}"
    data-url-add="{{ url('datatahun/add') }}"
    data-url-hapus-tahun="{{ url('datatahun/hapustahun') }}"
    data-url-hapus-smt="{{ url('datatahun/hapussemester') }}"
    data-url-heb="{{ url('datatahun/savehariefektif') }}">

    <x-ui.page-header
        title="Tahun Pelajaran & Semester"
        icon="bi-calendar2-range"
        subtitle="Kelola periode akademik aktif yang mengatur jadwal, presensi, dan pelaporan semester"
        :crumbs="['Data Umum', 'Periode Akademik']">
        <x-slot:actions>
            <button type="button" @click="location.reload()"
                class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-blue-700 shadow-xs transition-colors hover:bg-blue-50">
                <i class="bi bi-arrow-clockwise"></i> Segarkan
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-col gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/70 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-600 text-white">
                <i class="bi bi-info-circle-fill text-sm"></i>
            </span>
            <p class="text-xs leading-relaxed text-slate-600">
                <b class="text-amber-950">Petunjuk Penting:</b>
                Mengubah tahun pelajaran atau semester aktif mempengaruhi data kelas, jadwal pelajaran, presensi harian,
                dan pelaporan semester. Periode aktif dilindungi dan tidak dapat dihapus.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
        <!-- Tahun Pelajaran -->
        <div class="lg:col-span-7">
            <x-ui.card title="Daftar Tahun Pelajaran" icon="bi-calendar3"
                subtitle="Pilih tahun pelajaran aktif untuk operasional sekolah" body-class="p-0">
                <x-slot:actions>
                    <button type="button" @click="openCreate('master_tp')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-blue-700">
                        <i class="bi bi-plus-lg text-sm"></i> Tambah Periode
                    </button>
                </x-slot:actions>

                @if ($tp->isEmpty())
                    <div class="p-6">
                        <x-ui.empty icon="bi-calendar-x" title="Belum ada tahun pelajaran"
                            description="Tambahkan periode tahun pelajaran untuk mulai mengelola semester.">
                            <button type="button" @click="openCreate('master_tp')"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700">
                                <i class="bi bi-plus-lg"></i> Tambah Tahun Pelajaran
                            </button>
                        </x-ui.empty>
                    </div>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-14 px-4 py-3.5 text-center">No</th>
                            <th class="px-4 py-3.5">Tahun Pelajaran</th>
                            <th class="w-36 px-4 py-3.5 text-center">Status</th>
                            <th class="w-28 px-4 py-3.5 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($tp as $key => $value)
                            <tr class="transition-colors hover:bg-slate-50 {{ $value->active ? 'bg-blue-50/30' : '' }}">
                                <td class="px-4 py-3.5 text-center font-bold text-slate-400">{{ $key + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-extrabold tracking-tight text-slate-800">{{ $value->tahun }}</span>
                                        @if ($value->active)
                                            <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-extrabold text-blue-700">Aktif</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if ($value->active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-800">
                                            <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                                        </span>
                                    @else
                                        <button type="button"
                                            @click="activate('master_tp', {{ (int) $value->id_tp }}, '{{ e($value->tahun) }}')"
                                            class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 shadow-2xs transition-all hover:border-blue-600 hover:bg-blue-600 hover:text-white">
                                            <i class="bi bi-check2"></i> Aktifkan
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button"
                                            @click="openEdit('master_tp', {{ (int) $value->id_tp }}, '{{ e($value->tahun) }}')"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shadow-2xs transition-colors hover:bg-amber-100"
                                            title="Edit Periode">
                                            <i class="bi bi-pencil-fill text-xs"></i>
                                        </button>
                                        @if ($value->active)
                                            <span class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-xl bg-slate-100 text-slate-300"
                                                title="Tahun aktif tidak dapat dihapus">
                                                <i class="bi bi-trash text-xs"></i>
                                            </span>
                                        @else
                                            <button type="button" @click="destroy('master_tp', {{ (int) $value->id_tp }}, '{{ e($value->tahun) }}')"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 shadow-2xs transition-colors hover:bg-rose-100"
                                                title="Hapus Periode">
                                                <i class="bi bi-trash-fill text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                    <div class="border-t border-slate-100 bg-slate-50/50 p-4 text-center text-xs text-slate-400">
                        Klik <b>Aktifkan</b> pada baris tahun untuk mengganti tahun pelajaran yang sedang berjalan
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="space-y-4 lg:col-span-5">
            <!-- Semester -->
            <x-ui.card title="Daftar Semester" icon="bi-clock-history" tone="violet"
                subtitle="Pilih semester berjalan (Ganjil / Genap)" body-class="p-0">
                <x-slot:actions>
                    <button type="button" @click="openCreate('master_smt')"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-indigo-700">
                        <i class="bi bi-plus-lg text-sm"></i> Tambah
                    </button>
                </x-slot:actions>

                @if ($smt->isEmpty())
                    <div class="p-6">
                        <x-ui.empty icon="bi-clock" title="Belum ada semester"
                            description="Tambahkan semester Ganjil / Genap untuk periode ini." />
                    </div>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-14 px-4 py-3.5 text-center">No</th>
                            <th class="px-4 py-3.5">Nama Semester</th>
                            <th class="w-36 px-4 py-3.5 text-center">Status</th>
                            <th class="w-28 px-4 py-3.5 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($smt as $key => $value)
                            <tr class="transition-colors hover:bg-slate-50 {{ $value->active ? 'bg-indigo-50/30' : '' }}">
                                <td class="px-4 py-3.5 text-center font-bold text-slate-400">{{ $key + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="text-sm font-extrabold tracking-tight text-slate-800">{{ $value->smt }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    @if ($value->active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-100 px-3 py-1 text-xs font-extrabold text-emerald-800">
                                            <i class="bi bi-check-circle-fill text-emerald-600"></i> Aktif
                                        </span>
                                    @else
                                        <button type="button"
                                            @click="activate('master_smt', {{ (int) $value->id_smt }}, '{{ e($value->smt) }}')"
                                            class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 shadow-2xs transition-all hover:border-indigo-600 hover:bg-indigo-600 hover:text-white">
                                            <i class="bi bi-check2"></i> Aktifkan
                                        </button>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button"
                                            @click="openEdit('master_smt', {{ (int) $value->id_smt }}, '{{ e($value->smt) }}')"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 shadow-2xs transition-colors hover:bg-amber-100"
                                            title="Edit Semester">
                                            <i class="bi bi-pencil-fill text-xs"></i>
                                        </button>
                                        @if ($value->active)
                                            <span class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-xl bg-slate-100 text-slate-300"
                                                title="Semester aktif tidak dapat dihapus">
                                                <i class="bi bi-trash text-xs"></i>
                                            </span>
                                        @else
                                            <button type="button" @click="destroy('master_smt', {{ (int) $value->id_smt }}, '{{ e($value->smt) }}')"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 shadow-2xs transition-colors hover:bg-rose-100"
                                                title="Hapus Semester">
                                                <i class="bi bi-trash-fill text-xs"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>

            <!-- Hari Efektif -->
            <x-ui.card title="Hari Efektif Semester Ini" icon="bi-calculator" tone="emerald"
                :subtitle="'TP ' . $tpActiveYear . ' · ' . $smtActiveName">
                <form @submit.prevent="saveHeb()" class="space-y-3">
                    <div>
                        <label for="inputJmlHari" class="mb-1.5 block text-xs font-bold text-slate-700">
                            Jumlah Hari Efektif Pembelajaran
                        </label>
                        <div class="relative">
                            <input type="number" id="inputJmlHari" x-model="heb" min="0" max="366" required
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-4 pr-14 text-sm font-extrabold text-slate-800 outline-none transition-all focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20">
                            <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">Hari</span>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-400">
                            Total hari efektif belajar resmi untuk menghitung persentase kehadiran guru &amp; siswa pada semester aktif.
                        </p>
                    </div>

                    <button type="submit" :disabled="savingHeb"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="savingHeb" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        <i class="bi bi-save2" x-show="!savingHeb"></i>
                        <span x-text="savingHeb ? 'Menyimpan...' : 'Simpan Hari Efektif'"></span>
                    </button>
                </form>
            </x-ui.card>
        </div>
    </div>

    <!-- ═══ Modal Tambah / Edit Periode ═══ -->
    <div id="createPeriodeModal" class="modal hidden fixed inset-0 z-[100] items-center justify-center bg-slate-900/60 p-3 sm:p-4"
        tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="ui-modal-box flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                <h2 id="createModalLabel" class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white"
                        :class="form.method === 'edit' ? 'bg-amber-500' : 'bg-blue-600'">
                        <i class="bi text-sm" :class="form.method === 'edit' ? 'bi-pencil-fill' : 'bi-calendar-plus-fill'"></i>
                    </span>
                    <span class="truncate" x-text="modalTitle"></span>
                </h2>
                <button type="button" @click="closeModal()" aria-label="Tutup"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <form @submit.prevent="submitPeriode()" class="flex min-h-0 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5">
                    <div>
                        <label for="periodeLabel" class="mb-1.5 block text-xs font-bold text-slate-700">
                            Periode <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="periodeLabel" x-model="form.label" required autocomplete="off"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 outline-none transition-all placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20"
                            :class="error ? 'border-rose-300 ring-2 ring-rose-500/10' : ''"
                            placeholder="Masukkan label periode">
                        <p class="mt-1 text-[11px] text-slate-400" x-text="hint"></p>
                    </div>

                    <div x-show="error" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700" x-text="error"></div>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                    <button type="button" @click="closeModal()"
                        class="rounded-xl bg-slate-200 px-4 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-300">
                        Batal
                    </button>
                    <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-xs transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                        <i class="bi bi-check-lg" x-show="!saving"></i>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
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
    Alpine.data('periodePage', () => ({
        form: { method: 'add', tabel: 'master_tp', id: null, label: '' },
        saving: false,
        savingHeb: false,
        error: '',
        heb: {{ (int) ($jml_hari ?? 0) }},
        urls: {},
        tpList: [],
        smtList: [],

        init() {
            const data = this.$el.dataset;
            this.token = data.token;
            this.tpList = JSON.parse(data.tpList || '[]');
            this.smtList = JSON.parse(data.smtList || '[]');
            this.urls = {
                gantiTahun: data.urlGantiTahun,
                gantiSmt: data.urlGantiSmt,
                add: data.urlAdd,
                hapusTahun: data.urlHapusTahun,
                hapusSmt: data.urlHapusSmt,
                heb: data.urlHeb,
            };
        },

        get isSmt() {
            return this.form.tabel === 'master_smt';
        },

        get modalTitle() {
            const nama = this.isSmt ? 'Semester' : 'Tahun Pelajaran';

            return (this.form.method === 'edit' ? 'Edit ' : 'Tambah ') + nama;
        },

        get hint() {
            return this.isSmt
                ? 'Masukkan nama semester (contoh: Ganjil, Genap).'
                : 'Gunakan format YYYY/YYYY (contoh: 2025/2026).';
        },

        post(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ _token: this.token, ...payload }),
            }).then((response) => response.json());
        },

        openCreate(tabel) {
            this.error = '';
            this.form = { method: 'add', tabel: tabel, id: null, label: '' };
            this.$store.modals.open('#createPeriodeModal');
        },

        openEdit(tabel, id, label) {
            this.error = '';
            this.form = { method: 'edit', tabel: tabel, id: id, label: label };
            this.$store.modals.open('#createPeriodeModal');
        },

        closeModal() {
            this.$store.modals.close('#createPeriodeModal');
        },

        async submitPeriode() {
            const label = this.form.label.trim();

            if (label === '') {
                this.error = 'Label periode wajib diisi.';
                return;
            }

            this.error = '';
            this.saving = true;

            const payload = {
                method: this.form.method,
                tabel: this.form.tabel,
                label: label,
            };

            if (this.form.method === 'edit' && this.form.id) {
                if (this.isSmt) {
                    payload.id_smt = this.form.id;
                } else {
                    payload.id_tahun = this.form.id;
                }
            }

            try {
                const json = await this.post(this.urls.add, payload);

                if (!json.status) {
                    this.error = json.msg || 'Data periode gagal disimpan.';
                    return;
                }

                window.location.reload();
            } catch (e) {
                this.error = 'Terjadi kesalahan pada server.';
            } finally {
                this.saving = false;
            }
        },

        async activate(tabel, id, name) {
            const isSmt = tabel === 'master_smt';
            const label = isSmt ? 'Semester' : 'Tahun Pelajaran';

            const confirmation = await Swal.fire({
                title: 'Ganti ' + label + '?',
                text: 'Aktifkan ' + name + ' sebagai periode berjalan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '{{ \App\Libraries\Theme::metaColor('primary') }}',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Aktifkan',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            const payload = { active: id };

            if (isSmt) {
                payload.semester = JSON.stringify(this.smtList);
            } else {
                payload.tahun = JSON.stringify(this.tpList);
            }

            try {
                const json = await this.post(isSmt ? this.urls.gantiSmt : this.urls.gantiTahun, payload);

                if (json.status) {
                    window.location.reload();
                    return;
                }

                Swal.fire('Gagal', json.msg || 'Periode gagal diganti.', 'error');
            } catch (e) {
                Swal.fire('Gagal', 'Terjadi kesalahan pada server.', 'error');
            }
        },

        async destroy(tabel, id, name) {
            const isSmt = tabel === 'master_smt';
            const label = isSmt ? 'Semester' : 'Tahun Pelajaran';

            const confirmation = await Swal.fire({
                title: 'Hapus ' + label + '?',
                text: 'Hapus periode ' + name + '? Data yang berhubungan akan ikut terpengaruh.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) {
                return;
            }

            try {
                const json = await this.post(isSmt ? this.urls.hapusSmt : this.urls.hapusTahun, { hapus: id });

                if (json.status) {
                    window.location.reload();
                    return;
                }

                Swal.fire('Gagal', json.msg || 'Periode gagal dihapus.', 'error');
            } catch (e) {
                Swal.fire('Gagal', 'Terjadi kesalahan pada server.', 'error');
            }
        },

        async saveHeb() {
            const jumlah = Number(this.heb);

            if (!Number.isInteger(jumlah) || jumlah < 0 || jumlah > 366) {
                Swal.fire('Perhatian', 'Jumlah hari efektif harus bilangan bulat 0–366.', 'warning');
                return;
            }

            this.savingHeb = true;

            try {
                const json = await this.post(this.urls.heb, {
                    jml_hari: jumlah,
                    @if ($tp_active) tp_id: {{ (int) $tp_active->id_tp }}, @endif
                    @if ($smt_active) smt_id: {{ (int) $smt_active->id_smt }}, @endif
                });

                if (!json.status) {
                    Swal.fire('Gagal', json.msg || 'Hari efektif gagal disimpan.', 'error');
                    return;
                }

                Swal.fire({ icon: 'success', title: 'Tersimpan', text: json.msg, timer: 1500, showConfirmButton: false });
            } catch (e) {
                Swal.fire('Gagal', 'Terjadi kesalahan pada server.', 'error');
            } finally {
                this.savingHeb = false;
            }
        },
    }));
});
</script>
@endsection
