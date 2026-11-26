@extends('layouts.main')

@section('content')
<?php
$daftarRows = $daftar ?? [];
$hariIniRows = $hari_ini ?? [];
$bolehKelola = !empty($boleh);

$levelOptions = [];
foreach (($levels ?? []) as $levelKey => $levelLabel) {
    if ($levelKey === '' || $levelKey === null) {
        continue;
    }
    $levelOptions[(int) $levelKey] = $levelLabel;
}

$badgeLingkup = [
    'KELAS' => 'border-sky-200 bg-sky-50 text-sky-700',
    'TINGKAT' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    'SEMUA' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
];
$labelLingkup = [
    'KELAS' => 'Satu Kelas',
    'TINGKAT' => 'Satu Tingkat',
    'SEMUA' => 'Semua Kelas',
];

$inputClass = 'w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 outline-hidden transition-colors focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-500/20';
$labelClass = 'mb-1.5 block text-xs font-semibold tracking-wider text-slate-700 uppercase';

$pageConfig = [
    'csrf' => csrf_token(),
    'today' => date('Y-m-d'),
    'levelDefault' => (string) (array_key_first($levelOptions) ?? ''),
    'urls' => [
        'save' => url('admin/bdr/save'),
        'terapkan' => url('admin/bdr/terapkan'),
        'delete' => url('admin/bdr/delete'),
    ],
];
?>

<div x-data="bdrPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Jadwal BDR'"
            icon="bi-house-door-fill"
            tone="emerald"
            subtitle="Tandai kelas yang tidak KBM di sekolah agar absensinya tercatat BDR, bukan Alpha."
            :crumbs="['Presensi', 'Jadwal BDR']">
            @if ($bolehKelola)
                <x-slot:actions>
                    <button type="button" @click="openForm()"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700">
                        <i class="bi bi-plus-lg"></i> Tambah Jadwal BDR
                    </button>
                </x-slot:actions>
            @endif
        </x-ui.page-header>
    </div>

    <div class="mt-4 space-y-4 px-4 pb-6 md:px-6">

        {{-- Jadwal BDR yang aktif hari ini --}}
        <x-ui.card title="Hari ini, {{ date('d-m-Y') }}" icon="bi-calendar-day" tone="emerald" bodyClass="p-5">
            @if (empty($hariIniRows))
                <p class="flex items-center gap-2 text-xs text-slate-400">
                    <i class="bi bi-inbox"></i> Tidak ada jadwal BDR hari ini.
                </p>
            @else
                <div class="flex flex-wrap gap-2">
                    @foreach ($hariIniRows as $h)
                        @php
                            $lingkupHariIni = strtoupper((string) ($h->lingkup ?? 'KELAS'));
                            $badgeHariIni = $badgeLingkup[$lingkupHariIni] ?? $badgeLingkup['KELAS'];
                        @endphp
                        <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[11px] font-bold {{ $badgeHariIni }}">
                            <i class="bi bi-dot"></i>
                            {{ $h->judul ?: 'Belajar dari rumah' }}
                        </span>
                    @endforeach
                </div>
            @endif

            <x-slot:footer>
                <p class="flex items-start gap-2 text-[11px] leading-relaxed text-slate-500">
                    <i class="bi bi-clock-history mt-0.5 text-emerald-600"></i>
                    <span>Cron pukul 18:00 menandai siswa kelas tersebut BDR, bukan Alpha.</span>
                </p>
            </x-slot:footer>
        </x-ui.card>

        {{-- Daftar jadwal --}}
        <x-ui.card title="Daftar Jadwal" icon="bi-list-check" tone="blue" bodyClass="p-0">
            <x-slot:meta>
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-600">
                    <i class="bi bi-collection"></i> {{ count($daftarRows) }} jadwal
                </span>
            </x-slot:meta>

            @if (count($daftarRows) === 0)
                <div class="p-4 md:p-5">
                    <x-ui.empty icon="bi-calendar-x" title="Belum ada jadwal BDR"
                        description="{{ $bolehKelola ? 'Klik Tambah Jadwal BDR untuk membuat jadwal pertama.' : 'Belum ada jadwal BDR yang dibuat admin.' }}" />
                </div>
            @else
                <x-ui.table class="min-w-[840px]">
                    <x-slot:head>
                        <th class="w-12 px-3 py-3 text-center">#</th>
                        <th class="w-32 px-3 py-3">Lingkup</th>
                        <th class="w-44 px-3 py-3">Kelas / Tingkat</th>
                        <th class="w-44 px-3 py-3">Tanggal</th>
                        <th class="px-3 py-3">Keperluan</th>
                        @if ($bolehKelola)
                            <th class="w-40 px-3 py-3 text-center">Aksi</th>
                        @endif
                    </x-slot:head>

                    @foreach ($daftarRows as $d)
                        @php
                            $lingkup = strtoupper((string) ($d->lingkup ?? 'KELAS'));
                            $badge = $badgeLingkup[$lingkup] ?? $badgeLingkup['KELAS'];
                            $tglMulai = substr((string) $d->tgl_mulai, 0, 10);
                            $tglSelesai = $d->tgl_selesai ? substr((string) $d->tgl_selesai, 0, 10) : '';
                            $sudahLewat = ($tglSelesai ?: $tglMulai) < date('Y-m-d');
                        @endphp
                        <tr class="transition-colors hover:bg-slate-50/60">
                            <td class="px-3 py-3 text-center">
                                <span class="tabular-nums text-xs text-slate-400">{{ $loop->iteration }}</span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[10px] font-bold {{ $badge }}">
                                    {{ $labelLingkup[$lingkup] ?? $d->lingkup }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-xs font-semibold text-slate-700">
                                @if ($lingkup === 'KELAS')
                                    {{ $d->nama_kelas ?: 'Kelas #' . $d->id_kelas }}
                                @elseif ($lingkup === 'TINGKAT')
                                    Tingkat {{ $d->level ?: $d->level_id }}
                                @else
                                    Semua kelas
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-xs text-slate-600">
                                {{ date('d/m/Y', strtotime($tglMulai)) }}
                                @if ($tglSelesai !== '' && $tglSelesai !== $tglMulai)
                                    <span class="text-slate-400">&ndash; {{ date('d/m/Y', strtotime($tglSelesai)) }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs">
                                <div class="font-semibold text-slate-800">{{ $d->judul }}</div>
                                @if (!empty($d->keterangan))
                                    <div class="text-slate-400">{{ $d->keterangan }}</div>
                                @endif
                            </td>
                            @if ($bolehKelola)
                                <td class="px-3 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($sudahLewat)
                                            <button type="button" title="Ubah absensi Alpha yang sudah terlanjur tercatat menjadi BDR"
                                                @click="terapkan({{ (int) $d->id }})" :disabled="busy"
                                                class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] font-bold text-emerald-700 transition-colors hover:bg-emerald-100 disabled:opacity-40">
                                                <i class="bi bi-magic"></i> Terapkan
                                            </button>
                                        @endif
                                        <button type="button" title="Edit Jadwal BDR"
                                            @click="openForm({{ Js::from([
                                                'id' => (int) $d->id,
                                                'lingkup' => $lingkup,
                                                'id_kelas' => $d->id_kelas,
                                                'level_id' => $d->level_id,
                                                'tgl_mulai' => $tglMulai,
                                                'tgl_selesai' => $tglSelesai,
                                                'judul' => $d->judul,
                                                'keterangan' => $d->keterangan,
                                            ]) }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-500 transition-colors hover:bg-slate-50">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" title="Hapus Jadwal BDR"
                                            @click="hapus({{ (int) $d->id }}, {{ Js::from($d->judul ?: 'Jadwal BDR') }})" :disabled="busy"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-xs text-rose-500 transition-colors hover:bg-rose-100 disabled:opacity-40">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif
        </x-ui.card>
    </div>

    @if ($bolehKelola)
        <x-ui.modal id="modalBdr" size="max-w-lg">
            <x-slot:header>
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-5 py-4">
                    <h2 class="flex min-w-0 items-center gap-2.5 text-sm font-bold text-slate-800">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                            <i class="bi bi-house-door-fill text-sm"></i>
                        </span>
                        <span x-text="form.id ? 'Edit Jadwal BDR' : 'Tambah Jadwal BDR'">Tambah Jadwal BDR</span>
                    </h2>
                    <button type="button" data-ui-dismiss="modal" aria-label="Tutup"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            </x-slot:header>

            <form @submit.prevent="save()" class="flex min-h-0 flex-col">
                <div class="space-y-4 overflow-y-auto p-5">
                    <input type="hidden" name="id" x-model="form.id">

                    <div>
                        <label for="bdr-lingkup" class="{{ $labelClass }}">Lingkup yang diliburkan</label>
                        <select name="lingkup" id="bdr-lingkup" x-model="form.lingkup" class="{{ $inputClass }}">
                            <option value="KELAS">Satu kelas tertentu</option>
                            <option value="TINGKAT">Satu tingkat (kelas paralel, mis. semua kelas X)</option>
                            <option value="SEMUA">Semua kelas</option>
                        </select>
                    </div>

                    <div x-show="form.lingkup === 'KELAS'" x-cloak>
                        <label for="bdr-kelas" class="{{ $labelClass }}">Kelas</label>
                        <select name="id_kelas" id="bdr-kelas" x-model="form.id_kelas" :required="form.lingkup === 'KELAS'" class="{{ $inputClass }}">
                            <option value="">Pilih kelas :</option>
                            @foreach (($kelass ?? []) as $idKelas => $namaKelas)
                                <option value="{{ (int) $idKelas }}">{{ $namaKelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="form.lingkup === 'TINGKAT'" x-cloak>
                        <label for="bdr-level" class="{{ $labelClass }}">Tingkat</label>
                        <select name="level_id" id="bdr-level" x-model="form.level_id" :required="form.lingkup === 'TINGKAT'" class="{{ $inputClass }}">
                            @foreach ($levelOptions as $levelId => $levelLabel)
                                <option value="{{ $levelId }}">{{ $levelLabel }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-[11px] text-slate-400">Mis. 10 = kelas X, 11 = kelas XI, 12 = kelas XII.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="bdr-mulai" class="{{ $labelClass }}">Dari tanggal <span class="text-rose-500">*</span></label>
                            <input type="date" name="tgl_mulai" id="bdr-mulai" x-model="form.tgl_mulai" required class="{{ $inputClass }}">
                        </div>
                        <div>
                            <label for="bdr-selesai" class="{{ $labelClass }}">Sampai tanggal</label>
                            <input type="date" name="tgl_selesai" id="bdr-selesai" x-model="form.tgl_selesai" class="{{ $inputClass }}">
                            <p class="mt-1 text-[11px] text-slate-400">Kosongkan bila hanya satu hari.</p>
                        </div>
                    </div>

                    <div>
                        <label for="bdr-judul" class="{{ $labelClass }}">Keperluan</label>
                        <input type="text" name="judul" id="bdr-judul" x-model="form.judul" maxlength="255"
                            placeholder="Ujian Sekolah Kelas XII — kelas X & XI belajar dari rumah" class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label for="bdr-keterangan" class="{{ $labelClass }}">Keterangan</label>
                        <textarea name="keterangan" id="bdr-keterangan" rows="2" x-model="form.keterangan"
                            placeholder="Tugas yang dikerjakan dari rumah, catatan wali kelas, dsb." class="{{ $inputClass }}"></textarea>
                    </div>
                </div>

                <div class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3.5">
                    <button type="button" @click="$store.modals.close('#modalBdr')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-100">Batal</button>
                    <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs shadow-emerald-500/20 transition-colors hover:bg-emerald-700 disabled:opacity-60">
                        <span x-show="saving" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/70 border-t-transparent"></span>
                        <i x-show="!saving" class="bi bi-check-lg"></i>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan'">Simpan</span>
                    </button>
                </div>
            </form>
        </x-ui.modal>
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('bdrPage', () => ({
            config: { csrf: '', today: '', levelDefault: '', urls: {} },
            saving: false,
            busy: false,

            form: {
                id: '',
                lingkup: 'KELAS',
                id_kelas: '',
                level_id: '',
                tgl_mulai: '',
                tgl_selesai: '',
                judul: '',
                keterangan: '',
            },

            init() {
                const cfg = JSON.parse(this.$el.dataset.config || '{}');

                this.config = {
                    csrf: cfg.csrf || '',
                    today: cfg.today || '',
                    levelDefault: cfg.levelDefault || '',
                    urls: cfg.urls || {},
                };
            },

            openForm(rec) {
                rec = rec || {};

                this.form = {
                    id: rec.id ?? '',
                    lingkup: rec.lingkup || 'KELAS',
                    id_kelas: rec.id_kelas ?? '',
                    level_id: rec.level_id ?? this.config.levelDefault,
                    tgl_mulai: rec.tgl_mulai || this.config.today,
                    tgl_selesai: rec.tgl_selesai || '',
                    judul: rec.judul || '',
                    keterangan: rec.keterangan || '',
                };

                this.$store.modals.open('#modalBdr');
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

                if (!result || result.status !== true) {
                    throw new Error((result && result.message) || 'Gagal menghubungi server.');
                }

                return result;
            },

            async save() {
                if (this.saving) {
                    return;
                }

                if (!this.form.tgl_mulai) {
                    Swal.fire('Perhatian', 'Tanggal mulai wajib diisi.', 'warning');
                    return;
                }

                if (this.form.lingkup === 'KELAS' && !this.form.id_kelas) {
                    Swal.fire('Perhatian', 'Pilih kelas terlebih dahulu.', 'warning');
                    return;
                }

                if (this.form.lingkup === 'TINGKAT' && !this.form.level_id) {
                    Swal.fire('Perhatian', 'Pilih tingkat terlebih dahulu.', 'warning');
                    return;
                }

                if (this.form.tgl_selesai && this.form.tgl_selesai < this.form.tgl_mulai) {
                    Swal.fire('Perhatian', 'Tanggal selesai tidak boleh sebelum tanggal mulai.', 'warning');
                    return;
                }

                this.saving = true;

                try {
                    const res = await this.post(this.config.urls.save, [
                        ['id', this.form.id],
                        ['lingkup', this.form.lingkup],
                        ['id_kelas', this.form.lingkup === 'KELAS' ? this.form.id_kelas : ''],
                        ['level_id', this.form.lingkup === 'TINGKAT' ? this.form.level_id : ''],
                        ['tgl_mulai', this.form.tgl_mulai],
                        ['tgl_selesai', this.form.tgl_selesai],
                        ['judul', this.form.judul],
                        ['keterangan', this.form.keterangan],
                    ]);

                    this.$store.modals.close('#modalBdr');

                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || 'Tersimpan.',
                        timer: 1400,
                        showConfirmButton: false,
                    });

                    window.location.reload();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                } finally {
                    this.saving = false;
                }
            },

            async terapkan(id) {
                const confirm = await Swal.fire({
                    title: 'Terapkan jadwal ini?',
                    html: 'Absensi <strong>Alpha</strong> pada tanggal tersebut (yang belum ada jam masuknya) akan diubah menjadi <strong>BDR</strong>.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, terapkan',
                    cancelButtonText: 'Batal',
                });

                if (!confirm.isConfirmed) {
                    return;
                }

                this.busy = true;

                try {
                    const res = await this.post(this.config.urls.terapkan + '/' + id, []);

                    await Swal.fire({
                        icon: 'success',
                        title: 'Selesai',
                        text: res.message || '',
                        timer: 1400,
                        showConfirmButton: false,
                    });

                    window.location.reload();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                } finally {
                    this.busy = false;
                }
            },

            async hapus(id, label) {
                const confirm = await Swal.fire({
                    title: 'Hapus jadwal BDR?',
                    html: `Jadwal <strong>${this.escape(label)}</strong> akan dihapus.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                });

                if (!confirm.isConfirmed) {
                    return;
                }

                this.busy = true;

                try {
                    const res = await this.post(this.config.urls.delete + '/' + id, []);

                    await Swal.fire({
                        icon: 'success',
                        title: 'Terhapus',
                        text: res.message || '',
                        timer: 1200,
                        showConfirmButton: false,
                    });

                    window.location.reload();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                } finally {
                    this.busy = false;
                }
            },

            escape(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';

                return div.innerHTML;
            },
        }));
    });
</script>
@endsection
