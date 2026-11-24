@extends('layouts.main')

@section('content')
<?php
$holidayRows = $holidays ?? [];
$currentYear = (int) date('Y');
$currentMonth = date('Y-m');
$thisYearCount = 0;
$thisMonthCount = 0;
$cutiCount = 0;
$availableYears = [];

foreach ($holidayRows as $h) {
    $yearOfRow = substr((string) $h->tanggal, 0, 4);
    if ((int) $yearOfRow === $currentYear) {
        $thisYearCount++;
    }
    if (substr((string) $h->tanggal, 0, 7) === $currentMonth) {
        $thisMonthCount++;
    }
    if (stripos((string) $h->keterangan, 'cuti') !== false) {
        $cutiCount++;
    }
    if (!in_array($yearOfRow, $availableYears, true)) {
        $availableYears[] = $yearOfRow;
    }
}
rsort($availableYears);

$pageConfig = [
    'csrf' => csrf_token(),
    'currentYear' => $currentYear,
    'today' => date('Y-m-d'),
    'urls' => [
        'save' => url('admin/presensi/libur/save'),
        'delete' => url('admin/presensi/libur/delete'),
        'sync' => url('admin/presensi/libur/sync'),
    ],
];
?>

<div x-data="holidayPage()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">

    <div class="px-4 pt-4 md:px-6 md:pt-6">
        <x-ui.page-header
            :title="$judul ?? 'Hari Libur & Kalender Absensi'"
            icon="bi-calendar-event-fill"
            tone="rose"
            subtitle="Tanggal libur yang terdaftar otomatis meniadakan penalti denda atau alfa pada absensi guru."
            :crumbs="['Presensi', 'Hari Libur']" />
    </div>

    <div class="mt-4 space-y-4 px-4 pb-6 md:px-6">

        {{-- Ringkasan --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-xl text-blue-600">
                    <i class="bi bi-calendar2-check"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Total Libur Tahun {{ $currentYear }}</div>
                    <div class="text-xl font-extrabold text-slate-900">
                        {{ $thisYearCount }} <span class="text-xs font-normal text-slate-400">Hari</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-xl text-rose-500">
                    <i class="bi bi-calendar2-day"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Libur Bulan Ini ({{ date('F') }})</div>
                    <div class="text-xl font-extrabold text-rose-500">
                        {{ $thisMonthCount }} <span class="text-xs font-normal text-slate-400">Hari</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-xl text-amber-500">
                    <i class="bi bi-calendar2-range"></i>
                </span>
                <div class="min-w-0">
                    <div class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Cuti Bersama Terdaftar</div>
                    <div class="text-xl font-extrabold text-slate-900">
                        {{ $cutiCount }} <span class="text-xs font-normal text-slate-400">Hari</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <x-ui.card id="holidayFormCard" title="Form Hari Libur" icon="bi-calendar-plus" tone="blue" bodyClass="p-5">
            <form @submit.prevent="save()" novalidate>
                <input type="hidden" x-model="form.id">

                {{-- Mode selector --}}
                <div class="mb-5 grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
                    <button type="button" @click="mode = 'single'" :aria-pressed="mode === 'single' ? 'true' : 'false'"
                        :class="mode === 'single' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2.5 text-xs font-bold transition-colors">
                        <i class="bi bi-calendar-event"></i> Satu Tanggal
                    </button>
                    <button type="button" @click="mode = 'range'" :aria-pressed="mode === 'range' ? 'true' : 'false'"
                        :class="mode === 'range' ? 'bg-white text-blue-700 shadow-xs' : 'text-slate-500 hover:text-slate-700'"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2.5 text-xs font-bold transition-colors">
                        <i class="bi bi-calendar-range"></i> Rentang Tanggal
                    </button>
                </div>

                {{-- Single date --}}
                <div x-show="mode === 'single'" class="mb-5">
                    <label for="holidayDate" class="mb-1.5 block text-xs font-bold text-slate-600">
                        Tanggal Libur <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="holidayDate" x-model="form.tanggal" :required="mode === 'single'"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                </div>

                {{-- Range dates --}}
                <div x-show="mode === 'range'" x-cloak class="mb-5 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="holidayDateStart" class="mb-1.5 block text-xs font-bold text-slate-600">
                            Mulai <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="holidayDateStart" x-model="form.tanggal_mulai" :required="mode === 'range'"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label for="holidayDateEnd" class="mb-1.5 block text-xs font-bold text-slate-600">
                            Selesai <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" id="holidayDateEnd" x-model="form.tanggal_selesai" :required="mode === 'range'"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <p class="text-[11px] text-slate-400 sm:col-span-2">Contoh: Libur Akhir Semester (20 Des s/d 5 Jan)</p>
                </div>

                <div class="mb-5">
                    <label for="holidayDesc" class="mb-1.5 block text-xs font-bold text-slate-600">
                        Keterangan / Nama Libur <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="holidayDesc" rows="3" required x-model="form.keterangan"
                        placeholder="Contoh: Hari Raya Idul Fitri / Libur Semester Ganjil"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"></textarea>
                </div>

                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="submit" :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : (form.id ? 'bi-check2-circle' : 'bi-save')"></i>
                        <span x-text="saving ? 'Menyimpan...' : (form.id ? 'Perbarui' : 'Simpan')">Simpan</span>
                    </button>
                    <button type="button" @click="reset()"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition-colors hover:bg-slate-50">
                        Batal
                    </button>
                </div>
            </form>

            <x-slot:footer>
                <p class="flex items-start gap-2 text-[11px] leading-relaxed text-slate-500">
                    <i class="bi bi-info-circle mt-0.5 text-blue-600"></i>
                    <span>Pada tanggal libur yang terdaftar, sistem akan otomatis meniadakan penalti denda atau alfa pada absensi guru.</span>
                </p>
            </x-slot:footer>
        </x-ui.card>

        {{-- Daftar hari libur --}}
        <x-ui.card title="Daftar Hari Libur" icon="bi-list-check" tone="blue" bodyClass="p-0">
            <x-slot:actions>
                <select x-model="syncYear" aria-label="Tahun sinkronisasi"
                    class="rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    @for ($y = $currentYear - 1; $y <= $currentYear + 2; $y++)
                        <option value="{{ $y }}" @selected($y === $currentYear)>{{ $y }}</option>
                    @endfor
                </select>
                <button type="button" @click="sync()" :disabled="syncBusy"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2 text-xs font-bold text-blue-700 transition-colors hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60">
                    <i class="bi bi-cloud-arrow-down-fill" :class="syncBusy && 'animate-pulse'"></i>
                    <span x-text="syncBusy ? 'Menyinkron...' : 'Sinkron Nasional'">Sinkron Nasional</span>
                </button>
            </x-slot:actions>

            <div class="p-4 md:p-5" x-data="dataTable({ perPage: 10, filters: { tahun: '{{ $currentYear }}', bulan: '' } })">
                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative w-full sm:max-w-xs">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="search" x-model="query" placeholder="Cari keterangan..." aria-label="Cari keterangan hari libur"
                            class="w-full rounded-xl border border-slate-200 bg-white py-2 pr-3 pl-8 text-xs text-slate-700 outline-hidden transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    </div>

                    <select x-model="filters.tahun" aria-label="Filter tahun"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Semua Tahun</option>
                        @foreach ($availableYears as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>

                    <select x-model="filters.bulan" aria-label="Filter bulan"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 outline-hidden focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Semua Bulan</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ sprintf('%02d', $m) }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>

                    <span class="ml-auto inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-bold text-slate-500"
                        x-text="total + ' Data'">{{ count($holidayRows) }} Data</span>
                </div>

                @if (count($holidayRows) === 0)
                    <x-ui.empty icon="bi-calendar-x" title="Belum ada data hari libur"
                        description="Sinkronkan hari libur nasional atau tambahkan tanggal libur secara manual.">
                        <button type="button" @click="sync()"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700">
                            <i class="bi bi-cloud-arrow-down"></i> Sinkronkan Hari Libur Nasional
                        </button>
                    </x-ui.empty>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <th class="w-14 px-3 py-3 text-center">#</th>
                            <th class="w-56 px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Keterangan Hari Libur</th>
                            <th class="w-28 px-3 py-3 text-center">Aksi</th>
                        </x-slot:head>

                        @foreach ($holidayRows as $i => $h)
                            @php
                                $tglTime = strtotime($h->tanggal);
                                $isCuti = stripos((string) $h->keterangan, 'cuti') !== false;
                                $isSunday = date('N', $tglTime) == 7;
                            @endphp
                            <tr class="transition-colors hover:bg-slate-50/60"
                                data-tahun="{{ date('Y', $tglTime) }}"
                                data-bulan="{{ date('m', $tglTime) }}"
                                data-search="{{ mb_strtolower($h->keterangan . ' ' . date('d/m/Y', $tglTime)) }}">
                                <td class="px-3 py-2.5 text-center">
                                    <span data-rownum class="text-xs font-bold text-slate-400">{{ $i + 1 }}</span>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2.5">
                                        <div @class([
                                            'flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl border leading-none',
                                            'border-amber-200 bg-amber-50 text-slate-800' => $isCuti,
                                            'border-rose-200 bg-rose-50 text-rose-500' => ! $isCuti && $isSunday,
                                            'border-blue-200 bg-blue-50 text-blue-600' => ! $isCuti && ! $isSunday,
                                        ])>
                                            <span class="text-base font-extrabold">{{ date('d', $tglTime) }}</span>
                                            <span class="text-[9px] font-bold uppercase">{{ date('M', $tglTime) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block font-bold text-slate-800">{{ date('d/m/Y', $tglTime) }}</span>
                                            <span class="text-xs text-slate-400">{{ buat_tanggal(date('l', $tglTime)) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="font-semibold text-slate-800">{{ $h->keterangan }}</div>
                                    @if ($isCuti)
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">
                                            <i class="bi bi-tag-fill"></i> Cuti Bersama
                                        </span>
                                    @else
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">
                                            <i class="bi bi-flag-fill"></i> Libur Resmi
                                        </span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" title="Edit Hari Libur"
                                            @click="edit({{ Js::from(['id' => (int) $h->id, 'tanggal' => $h->tanggal, 'keterangan' => $h->keterangan]) }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-500 transition-colors hover:bg-slate-50">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" title="Hapus Hari Libur"
                                            @click="remove({{ (int) $h->id }}, {{ Js::from(date('d/m/Y', $tglTime) . ' - ' . $h->keterangan) }})"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-xs text-rose-500 transition-colors hover:bg-rose-100">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>

                    <div class="mt-3 flex flex-col justify-between gap-3 border-t border-slate-100 pt-3 sm:flex-row sm:items-center">
                        <div class="text-xs font-medium text-slate-500" aria-live="polite"
                            x-text="total === 0 ? 'Tidak ada hari libur yang cocok dengan filter' : 'Menampilkan ' + from + '\u2013' + to + ' dari ' + total + ' hari libur'">
                        </div>
                        <div class="flex items-center gap-2" x-show="pages > 1" x-cloak>
                            <button type="button" @click="prev()" :disabled="page <= 1" aria-label="Halaman sebelumnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="text-xs font-semibold text-slate-500" x-text="'Halaman ' + page + ' / ' + pages"></span>
                            <button type="button" @click="next()" :disabled="page >= pages" aria-label="Halaman berikutnya"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                @endif

                <p x-show="total === 0 && {{ count($holidayRows) }} > 0" x-cloak
                    class="py-8 text-center text-xs font-medium text-slate-400">
                    Tidak ada hari libur yang cocok dengan filter.
                </p>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('holidayPage', () => ({
            csrf: '',
            urls: {},
            defaultDate: '',
            mode: 'single',
            saving: false,
            syncBusy: false,
            syncYear: new Date().getFullYear(),
            form: {
                id: '',
                tanggal: '',
                tanggal_mulai: '',
                tanggal_selesai: '',
                keterangan: '',
            },

            init() {
                const config = JSON.parse(this.$el.dataset.config || '{}');
                this.csrf = config.csrf || '';
                this.urls = config.urls || {};
                this.syncYear = Number(config.currentYear) || new Date().getFullYear();
                this.defaultDate = config.today || '';
                this.reset();
            },

            today() {
                return this.defaultDate;
            },

            reset() {
                this.form = {
                    id: '',
                    tanggal: this.defaultDate,
                    tanggal_mulai: '',
                    tanggal_selesai: '',
                    keterangan: '',
                };
                this.mode = 'single';
            },

            edit(row) {
                this.form = {
                    id: row.id ?? '',
                    tanggal: row.tanggal || this.today(),
                    tanggal_mulai: '',
                    tanggal_selesai: '',
                    keterangan: row.keterangan || '',
                };
                this.mode = 'single';

                this.$nextTick(() => {
                    document.getElementById('holidayFormCard')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },

            async request(url, payload) {
                const body = new FormData();
                body.append('_token', this.csrf);
                Object.entries(payload).forEach(([key, value]) => body.append(key, value ?? ''));

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body,
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (error) {
                    data = null;
                }

                if (!data || data.status !== true) {
                    throw new Error((data && data.message) || 'Terjadi kesalahan jaringan atau server');
                }

                return data;
            },

            async save() {
                if (this.saving) {
                    return;
                }

                if (!this.form.keterangan.trim()) {
                    Swal.fire('Perhatian', 'Keterangan hari libur wajib diisi.', 'warning');
                    return;
                }

                const isRange = this.mode === 'range';

                if (isRange && (!this.form.tanggal_mulai || !this.form.tanggal_selesai)) {
                    Swal.fire('Perhatian', 'Tanggal mulai dan tanggal selesai wajib diisi.', 'warning');
                    return;
                }

                if (!isRange && !this.form.tanggal) {
                    Swal.fire('Perhatian', 'Tanggal libur wajib diisi.', 'warning');
                    return;
                }

                this.saving = true;

                try {
                    const res = await this.request(this.urls.save, {
                        id: this.form.id,
                        is_range: isRange ? 1 : 0,
                        tanggal: this.form.tanggal,
                        tanggal_mulai: this.form.tanggal_mulai,
                        tanggal_selesai: this.form.tanggal_selesai,
                        keterangan: this.form.keterangan,
                    });

                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil Disimpan!',
                        text: res.message || 'Data hari libur berhasil diperbarui.',
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

            async remove(id, label) {
                const confirm = await Swal.fire({
                    title: 'Hapus Hari Libur?',
                    html: `Apakah Anda yakin ingin menghapus <strong>${this.escape(label)}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                });

                if (!confirm.isConfirmed) {
                    return;
                }

                try {
                    const res = await this.request(this.urls.delete, { id });

                    await Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: res.message || 'Data hari libur berhasil dihapus.',
                        timer: 1200,
                        showConfirmButton: false,
                    });

                    window.location.reload();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                }
            },

            async sync() {
                if (this.syncBusy) {
                    return;
                }

                const year = this.syncYear || new Date().getFullYear();

                const confirm = await Swal.fire({
                    title: `Sinkronisasi Libur ${year}`,
                    html: `Mengambil daftar hari libur nasional & cuti bersama resmi pemerintah Indonesia untuk tahun <strong>${year}</strong>...`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Mulai Sinkron',
                    cancelButtonText: 'Batal',
                });

                if (!confirm.isConfirmed) {
                    return;
                }

                this.syncBusy = true;
                Swal.showLoading();

                try {
                    const res = await this.request(this.urls.sync, { year });

                    await Swal.fire({
                        icon: 'success',
                        title: 'Sinkronisasi Berhasil!',
                        text: res.message || 'Data hari libur nasional berhasil disinkronkan.',
                    });

                    window.location.reload();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                } finally {
                    this.syncBusy = false;
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
