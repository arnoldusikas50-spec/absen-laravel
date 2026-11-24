@extends('layouts.main')

@section('content')

<div class="fade-in-up space-y-4 px-4 pt-6 md:px-6"
    x-data="siswaImport"
    data-token="{{ csrf_token() }}"
    data-kelas="{{ isset($kelas) ? (int) $kelas->id_kelas : 0 }}"
    data-url-import="{{ url('datasiswa/do_import') }}"
    data-url-list="{{ url('datasiswa') }}">

    <x-ui.page-header
        title="{{ $subjudul ?? 'Import Data Siswa' }}"
        icon="bi-file-earmark-excel"
        tone="emerald"
        subtitle="Download template, isi data, upload kembali — maksimal 2.000 baris per import"
        :crumbs="['Data Umum', 'Siswa', 'Import']">
        <x-slot:actions>
            <a href="{{ url('datasiswa') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-5">
        <div class="space-y-4 xl:col-span-2">
            <div class="flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                <i class="bi bi-info-circle-fill mt-0.5 shrink-0 text-base"></i>
                <div>
                    <strong>Catatan Penting!</strong><br>
                    Gunakan format Excel yang telah disediakan. Mohon download template terlebih dahulu sebelum mengupload data.
                </div>
            </div>

            <!-- Langkah 1 -->
            <x-ui.card title="Download Template" icon="bi-download" tone="emerald" body-class="p-4">
                <p class="mb-3 text-[11px] text-slate-500">Unduh format resmi, isi data sesuai urutan kolom, lalu upload kembali.</p>
                <?php $url = 'uploads/import/format/format_siswa.xlsx'; ?>
                <a href="{{ url($url) }}"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white no-underline shadow-xs transition-colors hover:bg-emerald-700">
                    <i class="bi bi-download"></i> Download Format
                </a>
                <p class="mt-2.5 text-[11px] leading-relaxed text-slate-400">
                    Kolom: No • NISN • NIS • Nama • JK • Username • Password • Kelas Awal • Tahun Masuk.<br>
                    Username kosong = NIS, password kosong = NIS.<br>
                    <strong>Kelas Awal</strong> otomatis memasukkan siswa ke rombel yang sesuai (jika ada).
                </p>
            </x-ui.card>

            <!-- Langkah 2 -->
            <x-ui.card title="Upload File" icon="bi-cloud-arrow-up" tone="emerald" body-class="p-4">
                <p class="-mt-2 mb-3 text-[11px] text-slate-400">XLSX/CSV, maks 10MB</p>

                <div role="button" tabindex="0" aria-label="Pilih atau seret file ke sini"
                    @click="pick()" @keydown.enter.prevent="pick()" @keydown.space.prevent="pick()"
                    @dragover.prevent="dragging = true"
                    @dragenter.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="onDrop($event)"
                    class="cursor-pointer rounded-xl border-2 border-dashed p-6 text-center transition-colors"
                    :class="dragging ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-slate-50/60 hover:border-emerald-400 hover:bg-emerald-50/40'">
                    <i class="bi bi-cloud-arrow-up mb-2 block text-3xl text-slate-300"></i>
                    <p class="text-xs font-bold text-slate-600">Seret &amp; letakkan file di sini atau <span class="text-emerald-600 underline">klik untuk memilih</span></p>
                    <p x-show="fileName" x-cloak class="mt-2 break-all font-mono text-[11px] font-bold text-emerald-700" x-text="'📄 ' + fileName"></p>
                    <button type="button" x-show="fileName" x-cloak @click.stop="reset()"
                        class="mt-2 border-none bg-transparent text-[11px] font-bold text-slate-500 hover:text-red-500">
                        <i class="bi bi-x-circle mr-1"></i>Ganti file
                    </button>
                </div>
                <input type="file" x-ref="file" class="hidden" @change="onFile($event.target.files[0])"
                    accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv">
            </x-ui.card>

            <!-- Langkah 3 -->
            <x-ui.card title="Proses ke Database" icon="bi-database-add" tone="emerald" body-class="p-4">
                @isset($kelas)
                    <div class="mb-3 flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-xs text-blue-800">
                        <i class="bi bi-check-circle-fill text-base"></i>
                        <div>Siswa otomatis masuk <strong>Kelas: {{ $kelas->nama_kelas ?? '-' }}</strong></div>
                    </div>
                @endisset

                <button type="button" @click="upload()" :disabled="uploading || countValid === 0"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-show="uploading" x-cloak class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <i class="bi bi-cloud-upload" x-show="!uploading"></i>
                    <span x-text="uploading ? 'Mengunggah...' : 'Upload ke Database'"></span>
                </button>
                <p class="mt-2 text-[11px] text-slate-400">Tombol aktif setelah file terbaca &amp; ada baris valid.</p>
            </x-ui.card>
        </div>

        <div class="xl:col-span-3">
            <x-ui.card title="Preview Data" icon="bi-eye" :subtitle="null" body-class="p-0">
                <x-slot:meta>
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600"
                        x-text="rows.length + ' baris terdeteksi'"></span>
                    <template x-if="rows.length > 0">
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold">
                            <span class="text-emerald-600" x-text="countValid + ' valid'"></span>
                            <span class="text-slate-300">•</span>
                            <span class="text-rose-600" x-text="countInvalid + ' invalid'"></span>
                        </span>
                    </template>
                </x-slot:meta>

                <div class="max-h-[560px] min-h-[320px] overflow-auto">
                    <table x-show="rows.length > 0" x-cloak class="w-full border-collapse text-left text-sm">
                        <thead class="sticky top-0 z-10 bg-white shadow-sm">
                            <tr class="border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <template x-for="(col, index) in header" :key="index">
                                    <th class="whitespace-nowrap px-3 py-3" x-text="col || ('Col ' + (index + 1))"></th>
                                </template>
                                <th class="whitespace-nowrap px-3 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="(row, index) in preview" :key="index">
                                <tr :class="row.ok ? '' : 'bg-rose-50'">
                                    <template x-for="(cell, cellIndex) in row.cells" :key="cellIndex">
                                        <td class="whitespace-nowrap px-3 py-2 text-xs text-slate-700" x-text="cell"></td>
                                    </template>
                                    <td class="whitespace-nowrap px-3 py-2 text-center">
                                        <span x-show="row.ok" class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Valid</span>
                                        <span x-show="!row.ok" :title="row.msg"
                                            class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700">Invalid</span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="rows.length > 50">
                                <td :colspan="header.length + 1" class="bg-slate-50 py-4 text-center text-xs italic text-slate-400"
                                    x-text="'... dan ' + (rows.length - 50) + ' baris lainnya ...'"></td>
                            </tr>
                        </tbody>
                    </table>

                    <div x-show="rows.length === 0" class="py-16 text-center">
                        <i class="bi bi-file-earmark-spreadsheet mb-4 block text-5xl text-slate-300"></i>
                        <p class="text-xs font-semibold text-slate-500">Preview muncul di sini setelah Anda memilih file.</p>
                        <p class="mt-1 text-[11px] text-slate-400">Baris valid hijau • baris invalid merah</p>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('siswaImport', () => ({
        fileName: '',
        header: [],
        rows: [],
        flags: [],
        dragging: false,
        busy: false,
        uploading: false,
        config: {},

        init() {
            const data = this.$el.dataset;
            this.config = {
                token: data.token,
                kelas: data.kelas,
                importUrl: data.urlImport,
                listUrl: data.urlList,
            };
        },

        get preview() {
            return this.rows.slice(0, 50)
                .map((cells, index) => ({ cells: cells, ok: this.flags[index]?.ok === true, msg: this.flags[index]?.msg || '' }))
                .filter((row) => row.cells.some((cell) => String(cell ?? '').trim() !== ''));
        },

        get countValid() {
            return this.flags.filter((flag) => flag.ok).length;
        },

        get countInvalid() {
            return this.flags.filter((flag) => !flag.ok && !flag.empty).length;
        },

        pick() {
            this.$refs.file.click();
        },

        reset() {
            this.fileName = '';
            this.header = [];
            this.rows = [];
            this.flags = [];
            this.$refs.file.value = '';
        },

        onDrop(event) {
            this.dragging = false;
            const files = event.dataTransfer && event.dataTransfer.files;

            if (files && files.length) {
                this.onFile(files[0]);
            }
        },

        async onFile(file) {
            if (!file) {
                return;
            }

            const ext = (file.name.split('.').pop() || '').toLowerCase();

            if (!['xlsx', 'csv'].includes(ext)) {
                Swal.fire('Format Salah', 'Gunakan file XLSX atau CSV dari template yang disediakan.', 'warning');
                this.$refs.file.value = '';
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file maksimal 10MB.', 'warning');
                this.$refs.file.value = '';
                return;
            }

            this.fileName = file.name;
            this.header = [];
            this.rows = [];
            this.flags = [];
            this.busy = true;

            Swal.fire({ title: 'Membaca file', text: 'Memproses data...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                let parsed = { header: [], rows: [] };

                if (ext === 'csv') {
                    parsed = await this.readCsv(file);
                } else {
                    parsed = await this.readXlsx(file);
                }

                if (parsed.rows.length === 0) {
                    Swal.fire('Peringatan', 'File tampak kosong atau tidak berisi data.', 'warning');
                    return;
                }

                this.header = parsed.header;
                this.rows = parsed.rows;
                this.flags = this.validateRows(parsed.rows);

                Swal.close();
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Gagal membaca file. Pastikan format valid (.xlsx/.csv)', 'error');
            } finally {
                this.busy = false;
            }
        },

        async readXlsx(file) {
            const workbook = new ExcelJS.Workbook();
            await workbook.xlsx.load(await file.arrayBuffer());

            const sheet = workbook.worksheets[0];
            let header = [];
            const rows = [];

            sheet.eachRow((row, rowIndex) => {
                const cells = [];

                row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
                    let value = cell.value;

                    if (typeof value === 'object' && value !== null) {
                        if (value.text) {
                            value = value.text;
                        } else if (value.result) {
                            value = value.result;
                        } else if (value.richText) {
                            value = value.richText.map((part) => part.text).join('');
                        }
                    }

                    cells[colNumber - 1] = value ?? '';
                });

                if (rowIndex === 1) {
                    header = cells;
                } else {
                    rows.push(cells);
                }
            });

            return { header: header, rows: rows };
        },

        async readCsv(file) {
            const text = (await file.text()).replace(/^\uFEFF/, '');
            const lines = text.split(/\r\n|\r|\n/).filter((line) => line.trim() !== '');

            if (lines.length < 2) {
                throw new Error('empty');
            }

            const delimiter = lines[0].split(';').length >= lines[0].split(',').length ? ';' : ',';
            const parseLine = (line) => {
                const out = [];
                let current = '';
                let quoted = false;

                for (let i = 0; i < line.length; i++) {
                    const char = line[i];

                    if (quoted) {
                        if (char === '"') {
                            if (line[i + 1] === '"') {
                                current += '"';
                                i++;
                            } else {
                                quoted = false;
                            }
                        } else {
                            current += char;
                        }
                    } else if (char === '"') {
                        quoted = true;
                    } else if (char === delimiter) {
                        out.push(current);
                        current = '';
                    } else {
                        current += char;
                    }
                }

                out.push(current);

                return out.map((value) => value.trim());
            };

            const rows = [];

            for (let r = 1; r < lines.length; r++) {
                const cells = parseLine(lines[r]);

                if (cells.every((cell) => cell === '')) {
                    continue;
                }

                rows.push(cells);
            }

            return { header: parseLine(lines[0]), rows: rows };
        },

        /** Cermin aturan server: nama ≥ 3, NIS angka wajib; username default NIS. */
        validateRows(rows) {
            const seenNis = {};
            const seenUser = {};

            return rows.map((row) => {
                const text = (value) => String(value ?? '').trim();
                const nama = text(row[3]);
                const nis = text(row[2]);
                const nisn = text(row[1]);
                const username = text(row[5] || nis);

                if (!nama && !nis && !nisn && !username) {
                    return { empty: true, ok: false, msg: '' };
                }

                if (nama.length < 3) {
                    return { ok: false, msg: 'Nama min 3 karakter' };
                }

                if (!nis || !/^\d+$/.test(nis)) {
                    return { ok: false, msg: 'NIS wajib angka' };
                }

                if (seenNis[nis]) {
                    return { ok: false, msg: 'NIS ganda dalam file' };
                }

                if (!username) {
                    return { ok: false, msg: 'Username kosong' };
                }

                if (seenUser[username.toLowerCase()]) {
                    return { ok: false, msg: 'Username ganda dalam file' };
                }

                seenNis[nis] = true;
                seenUser[username.toLowerCase()] = true;

                return { ok: true, msg: '' };
            });
        },

        /** Baris yang benar-benar akan dikirim ke server. */
        get payloadRows() {
            const students = [];

            this.rows.forEach((row, index) => {
                const flag = this.flags[index];

                if (!flag || !flag.ok) {
                    return;
                }

                if (!(row[3] && (row[2] || row[1]))) {
                    return;
                }

                students.push({
                    nisn: row[1] || '',
                    nis: row[2] || '',
                    nama: row[3] || '',
                    jenis_kelamin: row[4] || 'L',
                    username: row[5] || row[2],
                    password: row[6] || row[2],
                    kelas_awal: row[7] || '',
                    tahun_masuk: row[8] || '',
                    sekolah_asal: row[9] || '',
                    tempat_lahir: row[10] || '',
                    tanggal_lahir: row[11] || '',
                    agama: row[12] || '',
                    hp: row[13] || '',
                    email: row[14] || '',
                    anak_ke: row[15] || '',
                    status_keluarga: row[16] || '',
                    alamat: row[17] || '',
                });
            });

            return students;
        },

        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[char]));
        },

        async upload() {
            const students = this.payloadRows;

            if (students.length === 0) {
                return;
            }

            this.uploading = true;

            Swal.fire({
                title: 'Mengupload Data',
                html: 'Sedang menyimpan <b>' + students.length + '</b> siswa ke database...<br>Jangan tutup halaman ini.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const response = await fetch(this.config.importUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        id_kelas: this.config.kelas,
                        siswa: students,
                        _token: this.config.token,
                    }),
                });

                const contentType = response.headers.get('content-type') || '';

                if (contentType.indexOf('application/json') === -1) {
                    console.error('Server Error Response:', await response.text());
                    Swal.fire('Server Error', 'Terjadi kesalahan di server. Cek Console untuk detail.', 'error');
                    return;
                }

                const result = await response.json();

                if (!result.status) {
                    const message = result.errors && result.errors.length
                        ? '<ul class="inline-block list-disc pl-4 text-left">' + result.errors.map((e) => '<li>' + this.escapeHtml(e) + '</li>').join('') + '</ul>'
                        : this.escapeHtml(result.message || 'Terjadi kesalahan saat menyimpan data');

                    Swal.fire({ title: 'Gagal', html: message, icon: 'error' });
                    return;
                }

                const skipped = result.errors && result.errors.length
                    ? '<div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-left text-xs text-amber-800">'
                        + '<p class="mb-1 font-bold">' + result.skipped + ' baris dilewati:</p><ul class="list-disc space-y-0.5 pl-4">'
                        + result.errors.map((e) => '<li>' + this.escapeHtml(e) + '</li>').join('') + '</ul></div>'
                    : '';

                const kelasInfo = result.assigned_to_kelas > 0
                    ? '<div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 p-2 text-center text-xs text-emerald-800">'
                        + '<strong>' + result.assigned_to_kelas + ' siswa</strong> berhasil dimasukkan ke kelas sesuai kode kelas awal</div>'
                    : '';

                const done = await Swal.fire({
                    title: 'Selesai!',
                    html: this.escapeHtml(result.message || (students.length + ' Data siswa berhasil diimport.')) + skipped + kelasInfo,
                    icon: 'success',
                    confirmButtonText: 'OK',
                });

                if (!result.skipped) {
                    window.location.href = this.config.listUrl;
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Koneksi Gagal', 'Gagal menghubungi server. Periksa koneksi internet Anda.', 'error');
            } finally {
                this.uploading = false;
            }
        },
    }));
});
</script>
@endsection
