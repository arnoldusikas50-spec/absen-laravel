@extends('layouts.main')

@section('content')
<div class="fade-in-up space-y-4 p-4 md:p-6" x-data="guruForm()">
    <x-ui.page-header :title="$subjudul ?? 'Tambah Data Guru'" icon="bi-person-plus" tone="blue"
        subtitle="Tambah akun dan profil guru baru secara manual atau import Excel"
        :crumbs="['Data Guru', $subjudul ?? 'Tambah Guru']">
        <x-slot:actions>
            <a href="{{ url('dataguru') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card body-class="p-5">
        <x-slot:toolbar>
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="tab = 'manual'"
                    :class="tab === 'manual' ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition-colors">
                    <i class="bi bi-person-plus"></i> Tambah Manual
                </button>
                <button type="button" @click="tab = 'import'"
                    :class="tab === 'import' ? 'bg-blue-600 text-white shadow-sm shadow-blue-500/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition-colors">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Import Excel
                </button>
            </div>
        </x-slot:toolbar>

        <!-- Tab Manual -->
        <div x-show="tab === 'manual'" x-cloak>
            <form x-ref="formManual" @submit.prevent="saveManual" autocomplete="off">
                @csrf

                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                    <i class="bi bi-info-circle mt-px shrink-0 text-sm text-blue-600"></i>
                    <span>Data Guru akan otomatis dibuatkan akun pengguna (User) dengan hak akses Guru.</span>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold text-slate-600">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_guru" required maxlength="100" placeholder="Contoh: Randy Ikas, S.Pd"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-600">NIP / NUPTK</label>
                        <input type="text" inputmode="numeric" name="nip" placeholder="Angka saja (opsional)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-600">Kode Guru</label>
                        <input type="text" name="kode_guru" maxlength="30" placeholder="Contoh: GR001 (opsional)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold text-slate-600">ID Telegram</label>
                        <input type="text" inputmode="numeric" name="telegram_id" maxlength="50" placeholder="123456789 (opsional)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-[11px] text-slate-400">Ambil ID dari bot @userinfobot di Telegram. Bisa diisi nanti lewat menu Edit.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1.5 block text-xs font-bold text-slate-600">No. WhatsApp</label>
                        <input type="tel" inputmode="tel" name="wa_number" maxlength="20" placeholder="6281234567890 (opsional)"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-[11px] text-slate-400">Tujuan notifikasi denda, surat teguran, dan alpha. Boleh ditulis 08 / +62 / 62 — otomatis dinormalisasi. Berbeda dari No. HP di data Dapodik.</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-600">Username <span class="text-red-500">*</span></label>
                        <input type="text" name="username" required minlength="4" maxlength="30" placeholder="Minimal 4 karakter"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-600">Password <span class="text-red-500">*</span></label>
                        <input type="text" name="password" required minlength="5" maxlength="72" placeholder="Minimal 5 karakter"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" :disabled="saving"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                            <i class="bi" :class="saving ? 'bi-arrow-repeat animate-spin' : 'bi-save'"></i>
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Data Guru'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tab Import -->
        <div x-show="tab === 'import'" x-cloak>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-bold text-slate-700">Import dari Excel / CSV</h3>
                <span class="text-[11px] font-semibold text-slate-400">maksimal 1.000 baris per import</span>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-5">
                <div class="space-y-4 xl:col-span-2">
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="mb-2.5 flex items-center gap-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-xs font-extrabold text-white">1</span>
                            <span class="text-xs font-bold text-slate-600">Download Format</span>
                        </div>
                        <p class="mb-3 text-[11px] text-slate-500">Unduh format resmi, isi data sesuai urutan kolom, lalu upload kembali.</p>
                        <a href="{{ asset('assets/tpl/format_guru.xlsx') }}"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white no-underline transition-colors hover:bg-emerald-700">
                            <i class="bi bi-download"></i> Download Format
                        </a>
                        <p class="mt-2.5 text-[11px] leading-relaxed text-slate-400">
                            Kolom: No • Nama • NIP • Kode Guru • Username • Password.<br>
                            Password kosong = default <span class="font-mono font-bold">123456</span>.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="mb-2.5 flex items-center gap-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-xs font-extrabold text-white">2</span>
                            <span class="text-xs font-bold text-slate-600">Upload File <span class="font-normal text-slate-400">(XLSX/CSV, maks 5MB)</span></span>
                        </div>
                        <input type="file" id="input-file-events" x-ref="file" accept=".xlsx,.csv"
                            @change="handleFile($event.target.files[0])"
                            class="block w-full cursor-pointer rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-6 text-xs text-slate-500 transition-colors hover:border-blue-400 file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white">
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="mb-2.5 flex items-center gap-2.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-xs font-extrabold text-white">3</span>
                            <span class="text-xs font-bold text-slate-600">Proses ke Database</span>
                        </div>

                        <div x-show="total > 0" class="mb-2.5 flex flex-wrap gap-1.5 text-[11px]">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 font-bold text-emerald-700">
                                <i class="bi bi-check-circle"></i> <span x-text="validCount"></span> valid
                            </span>
                            <span x-show="invalidCount > 0"
                                class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 font-bold text-red-700">
                                <i class="bi bi-x-circle"></i> <span x-text="invalidCount"></span> invalid (dilewati)
                            </span>
                        </div>

                        <button type="button" x-show="validCount > 0" @click="importData()" :disabled="importing"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-bold text-white transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                            <i class="bi bi-upload"></i> Proses Import
                        </button>
                        <p class="mt-2 text-[11px] text-slate-400">Tombol aktif setelah file terbaca & ada baris valid.</p>

                        <div x-show="result" class="mt-3 rounded-xl border px-3 py-2.5 text-xs"
                            :class="result && result.ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-red-200 bg-red-50 text-red-800'">
                            <p class="font-bold" x-text="result ? result.message : ''"></p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-4">
                                <template x-for="(item, index) in (result ? result.list : [])" :key="index">
                                    <li x-text="item"></li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-[11px] text-blue-900">
                        <p class="mb-1 text-xs font-bold">Petunjuk Import:</p>
                        <ul class="list-disc space-y-0.5 pl-4">
                            <li>Gunakan file format yang disediakan (XLSX/CSV).</li>
                            <li>Kolom NIP harus angka (boleh kosong).</li>
                            <li>Username min 4 karakter; password kosong = default <span class="font-mono font-bold">123456</span>.</li>
                            <li>Urutan kolom jangan diubah.</li>
                            <li>Baris tak valid dilewati & dilaporkan.</li>
                        </ul>
                    </div>
                </div>

                <div class="xl:col-span-3">
                    <div class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200">
                        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600"><i class="bi bi-eye mr-2"></i>Preview Data</h3>
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600">
                                <span x-text="total"></span>&nbsp;baris terdeteksi
                            </span>
                        </div>

                        <div class="flex-1 bg-slate-50/50 p-3" style="min-height: 320px; max-height: 560px; overflow-y: auto;">
                            <div x-show="total === 0 || loadingFile" class="py-16 text-center text-slate-400">
                                <div x-show="loadingFile" class="mx-auto mb-4 h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600"></div>
                                <i x-show="!loadingFile" class="bi bi-file-earmark-excel mb-4 block text-5xl opacity-40"></i>
                                <p class="mb-0 text-xs font-semibold" x-text="loadingFile ? 'Membaca file...' : 'Preview muncul di sini setelah Anda memilih file.'"></p>
                                <p x-show="!loadingFile" class="mt-1 text-[11px]">Baris valid hijau &nbsp;•&nbsp; baris invalid merah</p>
                            </div>

                            <div x-show="total > 0" class="overflow-x-auto">
                                <table class="w-full border-collapse text-left text-xs">
                                    <thead>
                                        <tr class="bg-blue-600 text-white">
                                            <template x-for="(head, index) in header" :key="index">
                                                <th class="px-2 py-2 text-center font-bold" x-text="head.label"></th>
                                            </template>
                                            <th class="px-2 py-2 text-center font-bold">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="(row, index) in preview" :key="index">
                                            <tr :class="row.bad ? 'bg-red-50' : ''">
                                                <template x-for="(cell, cellIndex) in row.cells" :key="cellIndex">
                                                    <td class="border-t border-slate-100 px-2 py-1.5" x-text="cell"></td>
                                                </template>
                                                <td class="border-t border-slate-100 px-2 py-1.5 text-center">
                                                    <span x-show="row.bad"
                                                        class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">Invalid</span>
                                                    <span x-show="!row.bad"
                                                        class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Valid</span>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="total > preview.length">
                                            <td :colspan="header.length + 1" class="px-2 py-2 text-center italic text-slate-400">
                                                ... dan <span x-text="total - preview.length"></span> data lainnya
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('guruForm', () => ({
            tab: 'manual',
            saving: false,
            loadingFile: false,
            importing: false,
            header: [],
            rows: [],
            preview: [],
            total: 0,
            validCount: 0,
            invalidCount: 0,
            result: null,

            urlManual: @json(url('dataguru/create')),
            urlImport: @json(url('dataguru/do_import')),
            urlIndex: @json(url('dataguru')),
            token: @json(csrf_token()),

            async saveManual() {
                if (this.saving) {
                    return;
                }

                this.saving = true;

                try {
                    const response = await fetch(this.urlManual, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(this.$refs.formManual),
                    });
                    const data = await response.json();

                    if (data.status) {
                        // Kanal notifikasi dilaporkan terpisah: guru bisa tersimpan
                        // dengan baik tapi nomornya ditolak format, atau ID
                        // Telegram-nya sudah dipakai akun lain.
                        const kanal = Array.isArray(data.kanal) && data.kanal.length ? data.kanal : null;

                        if (kanal) {
                            await Swal.fire('Sebagian gagal', 'Guru tersimpan, tetapi:<br>' + kanal.join('<br>'), 'warning');
                        } else {
                            await Swal.fire('Berhasil', 'Data Guru berhasil ditambahkan', 'success');
                        }

                        window.location.href = this.urlIndex;

                        return;
                    }

                    let message = 'Gagal menyimpan data';

                    if (data.errors) {
                        message = Object.values(data.errors).join('<br>');
                    } else if (data.message) {
                        message = data.message;
                    }

                    Swal.fire('Gagal', message, 'error');
                } catch (error) {
                    console.error(error);
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                } finally {
                    this.saving = false;
                }
            },

            clearFile() {
                if (this.$refs.file) {
                    this.$refs.file.value = '';
                }
            },

            resetPreviewState() {
                this.header = [];
                this.rows = [];
                this.preview = [];
                this.total = 0;
                this.validCount = 0;
                this.invalidCount = 0;
                this.result = null;
            },

            async handleFile(file) {
                this.resetPreviewState();

                if (!file) {
                    return;
                }

                const ext = (file.name.split('.').pop() || '').toLowerCase();

                if (!['xlsx', 'csv'].includes(ext)) {
                    Swal.fire('Format Salah', 'Gunakan file XLSX atau CSV dari format yang disediakan.', 'warning');
                    this.clearFile();

                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire('Terlalu Besar', 'Ukuran file maksimal 5MB.', 'warning');
                    this.clearFile();

                    return;
                }

                this.loadingFile = true;

                try {
                    const data = ext === 'csv' ? await this.readCsvFile(file) : await this.readExcelFile(file);
                    this.buildPreview(data[data.sheets[0]]);
                } catch (error) {
                    console.error(error);
                    Swal.fire('Error', 'Gagal membaca file. Pastikan file tidak rusak.', 'error');
                    this.resetPreviewState();
                } finally {
                    this.loadingFile = false;
                }
            },

            readExcelFile(file) {
                return new Promise((resolve, reject) => {
                    const workbook = new ExcelJS.Workbook();
                    const reader = new FileReader();

                    reader.onload = () => {
                        workbook.xlsx.load(reader.result).then((loaded) => {
                            const dataFiles = { sheets: [] };

                            loaded.eachSheet((sheet) => {
                                dataFiles.sheets.push(sheet.name);

                                const cols = { name: sheet.name, header: [], rows: [] };

                                sheet.eachRow({ includeEmpty: false }, (row, rowIndex) => {
                                    if (rowIndex === 1) {
                                        row.eachCell((cell, colNumber) => {
                                            cols.header.push({ label: cell.text, value: colNumber });
                                        });
                                    } else {
                                        const obj = {};
                                        row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                                            obj[colNumber] = cell.text;
                                        });
                                        cols.rows.push(obj);
                                    }
                                });

                                dataFiles[sheet.name] = cols;
                            });

                            resolve(dataFiles);
                        }).catch(reject);
                    };

                    reader.onerror = reject;
                    reader.readAsArrayBuffer(file);
                });
            },

            // Parser CSV sederhana (pemisah ; atau , otomatis, dukung tanda kutip)
            readCsvFile(file) {
                return new Promise((resolve, reject) => {
                    const reader = new FileReader();

                    reader.onload = () => {
                        try {
                            const text = String(reader.result || '').replace(/^\uFEFF/, '');
                            const lines = text.split(/\r\n|\r|\n/).filter((line) => line.trim() !== '');

                            if (lines.length < 2) {
                                reject(new Error('empty'));

                                return;
                            }

                            const delimiter = lines[0].split(';').length >= lines[0].split(',').length ? ';' : ',';
                            const parseLine = (line) => {
                                const out = [];
                                let current = '';
                                let inQuotes = false;

                                for (let i = 0; i < line.length; i++) {
                                    const char = line[i];

                                    if (inQuotes) {
                                        if (char === '"') {
                                            if (line[i + 1] === '"') {
                                                current += '"';
                                                i++;
                                            } else {
                                                inQuotes = false;
                                            }
                                        } else {
                                            current += char;
                                        }
                                    } else if (char === '"') {
                                        inQuotes = true;
                                    } else if (char === delimiter) {
                                        out.push(current);
                                        current = '';
                                    } else {
                                        current += char;
                                    }
                                }

                                out.push(current);

                                return out.map((cell) => cell.trim());
                            };

                            const headerCells = parseLine(lines[0]);
                            const cols = {
                                name: 'Sheet1',
                                header: headerCells.map((label, index) => ({ label: label || ('Kolom ' + (index + 1)), value: index + 1 })),
                                rows: [],
                            };

                            for (let r = 1; r < lines.length; r++) {
                                const cells = parseLine(lines[r]);

                                if (cells.every((cell) => cell === '')) {
                                    continue;
                                }

                                const row = {};
                                cells.forEach((cell, index) => {
                                    row[index + 1] = cell;
                                });
                                cols.rows.push(row);
                            }

                            resolve({ sheets: ['Sheet1'], Sheet1: cols });
                        } catch (error) {
                            reject(error);
                        }
                    };

                    reader.onerror = reject;
                    reader.readAsText(file, 'UTF-8');
                });
            },

            // Validasi baris di sisi klien (cermin aturan server) untuk preview
            validateRow(row, seenUser, seenNip) {
                const nama = (row['2'] || '').trim();
                const nip = (row['3'] || '').trim();
                const username = (row['5'] || '').trim();
                const password = (row['6'] || '').trim();

                if (!nama && !username && !password && !nip && !(row['4'] || '').trim()) {
                    return { empty: true };
                }

                if (!nama) {
                    return { ok: false, msg: 'Nama wajib diisi' };
                }

                if (username.length < 4 || username.length > 30 || !/^[A-Za-z0-9_.-]+$/.test(username)) {
                    return { ok: false, msg: 'Username 4–30 karakter valid' };
                }

                const lower = username.toLowerCase();

                if (seenUser[lower]) {
                    return { ok: false, msg: 'Username ganda dalam file' };
                }

                if (password !== '' && password.length < 5) {
                    return { ok: false, msg: 'Password min 5 karakter' };
                }

                if (nip !== '' && !/^\d+$/.test(nip)) {
                    return { ok: false, msg: 'NIP harus angka' };
                }

                if (nip !== '' && seenNip[nip]) {
                    return { ok: false, msg: 'NIP ganda dalam file' };
                }

                seenUser[lower] = true;

                if (nip !== '') {
                    seenNip[nip] = true;
                }

                return { ok: true };
            },

            buildPreview(sheet) {
                this.header = sheet.header || [];
                this.rows = sheet.rows || [];
                this.result = null;

                const seenUser = {};
                const seenNip = {};
                const preview = [];
                let valid = 0;
                let invalid = 0;

                this.rows.slice(0, 100).forEach((row) => {
                    const check = this.validateRow(row, seenUser, seenNip);

                    if (check.empty) {
                        return;
                    }

                    const bad = !check.ok;

                    if (bad) {
                        invalid++;
                    } else {
                        valid++;
                    }

                    preview.push({
                        cells: this.header.map((head) => row[head.value] || ''),
                        bad,
                        msg: check.msg || '',
                    });
                });

                this.preview = preview;
                this.total = this.rows.length;
                this.validCount = valid;
                this.invalidCount = invalid;

                if (valid === 0) {
                    Swal.fire('Tidak Valid', 'Tidak ada baris valid untuk diimport. Periksa kembali file Anda.', 'warning');
                }
            },

            escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            },

            async importData() {
                if (this.rows.length === 0 || this.importing) {
                    return;
                }

                const confirmed = await Swal.fire({
                    title: 'Konfirmasi Import',
                    text: `Apakah Anda yakin ingin mengimport ${this.rows.length} data guru?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Import!',
                });

                if (!confirmed.isConfirmed) {
                    return;
                }

                this.importing = true;

                Swal.fire({
                    title: 'Mengimport Data',
                    text: 'Silakan tunggu...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                const params = new URLSearchParams();
                params.append('_token', this.token);
                this.rows.forEach((row, index) => {
                    Object.keys(row).forEach((column) => {
                        params.append(`guru[${index}][${column}]`, row[column]);
                    });
                });

                try {
                    const response = await fetch(this.urlImport, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        },
                        body: params,
                    });
                    const data = await response.json();

                    if (data.status) {
                        const skipped = Array.isArray(data.errors) ? data.errors : [];

                        this.result = {
                            ok: true,
                            message: data.message || ('Berhasil mengimport ' + data.total + ' data guru'),
                            list: data.skipped && skipped.length ? [data.skipped + ' baris dilewati:', ...skipped] : [],
                        };

                        await Swal.fire('Berhasil', this.result.message, 'success');

                        if (!data.skipped) {
                            window.location.href = this.urlIndex;
                        }
                    } else {
                        const errors = Array.isArray(data.errors) ? data.errors : [];

                        this.result = {
                            ok: false,
                            message: data.message || 'Terjadi kesalahan saat mengimport data',
                            list: errors,
                        };

                        const html = errors.length
                            ? '<ul class="text-left inline-block list-disc pl-4">' + errors.map((item) => `<li>${this.escapeHtml(item)}</li>`).join('') + '</ul>'
                            : this.escapeHtml(data.message || 'Terjadi kesalahan saat mengimport data');

                        Swal.fire({ icon: 'error', title: 'Gagal', html });
                    }
                } catch (error) {
                    console.error(error);
                    Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                } finally {
                    this.importing = false;
                }
            },
        }));
    });
</script>
@endsection
