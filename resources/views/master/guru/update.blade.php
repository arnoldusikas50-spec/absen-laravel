@extends('layouts.main')

@section('content')

<?php
$pageConfig = [
    'csrf' => csrf_token(),
    'urls' => [
        'index'        => url('dataguru'),
        'downloadData' => url('dataguru/downloadData'),
        'updateAll'    => url('dataguru/update_all'),
        'updateFoto'   => url('dataguru/update_foto'),
    ],
];
?>

<div x-data="guruBulkUpdate()"
    data-config="{{ json_encode($pageConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}"
    class="space-y-5 p-4 md:p-6">

    <x-ui.page-header
        :title="$judul ?? 'Update Data Guru'"
        icon="bi-database-gear"
        :subtitle="($subjudul ?? 'Update Data & Foto Guru') . ' — TP ' . ($tp_active->tahun ?? '-') . ' ' . ($smt_active->smt ?? '')"
        :crumbs="['Data Guru', 'Update Massal']">
        <x-slot:actions>
            <a href="{{ url('dataguru') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors no-underline">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex items-start gap-3 p-4 rounded-2xl bg-blue-50 border border-blue-100 text-blue-900 shadow-xs" role="alert">
        <i class="bi bi-exclamation-triangle-fill text-blue-500 text-lg mt-0.5 shrink-0"></i>
        <div class="min-w-0 text-xs leading-relaxed">
            <strong>Penting!</strong> Untuk memperbarui data massal, download data terlebih dahulu, edit filenya,
            lalu upload kembali. Maksimal 1.000 baris data / 500 foto per batch.
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 items-start">

        <!-- Panel download & upload -->
        <div class="xl:col-span-2">
            <x-ui.card title="Pengelolaan Data & Foto" icon="bi-database-gear" body-class="p-5 space-y-5">

                <section>
                    <div class="flex items-center gap-2.5 mb-2.5">
                        <span class="w-7 h-7 rounded-lg bg-blue-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">1</span>
                        <h3 class="font-bold text-sm text-slate-700 mb-0"><i class="bi bi-download mr-1"></i>Unduh Data Saat Ini</h3>
                    </div>
                    @if (! empty($gurus))
                        <button type="button" @click="downloadExcel()" :disabled="busy"
                            class="inline-flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-xs disabled:opacity-50">
                            <i class="bi bi-file-earmark-excel"></i> Download Excel
                        </button>
                        <p class="mt-1.5 text-[11px] text-slate-400 mb-0">Edit kolom NIP, NAMA GURU, L/P, dan USERNAME.</p>
                    @else
                        <div class="w-full text-xs text-slate-400">Belum ada data guru</div>
                    @endif
                </section>

                <hr class="border-slate-100">

                <section>
                    <div class="flex items-center gap-2.5 mb-2.5">
                        <span class="w-7 h-7 rounded-lg bg-cyan-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">2</span>
                        <h3 class="font-bold text-sm text-cyan-700 mb-0"><i class="bi bi-image mr-1"></i>Unduh Format Foto</h3>
                    </div>
                    @if (! empty($gurus))
                        <button type="button" @click="downloadWord()" :disabled="busy"
                            class="inline-flex items-center justify-center gap-1.5 w-full px-3 py-2 text-xs font-semibold rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-200 hover:bg-cyan-100 transition-colors shadow-xs disabled:opacity-50">
                            <i class="bi bi-file-earmark-word"></i> Download Word
                        </button>
                        <p class="mt-1.5 text-[11px] text-slate-400 mb-0">Ganti gambar pada kolom FOTO di file Word.</p>
                    @else
                        <div class="w-full text-xs text-slate-400">Belum ada data guru</div>
                    @endif
                </section>

                <hr class="border-slate-100">

                <section>
                    <div class="flex items-center gap-2.5 mb-2.5">
                        <span class="w-7 h-7 rounded-lg bg-emerald-600 text-white text-xs font-extrabold flex items-center justify-center shrink-0">3</span>
                        <h3 class="font-bold text-sm text-slate-700 mb-0"><i class="bi bi-cloud-arrow-up mr-1"></i>Upload File Perubahan</h3>
                    </div>

                    <div class="mb-3">
                        <label for="dropzone-excel" class="block text-xs font-semibold text-slate-700 mb-1.5">Excel (.xlsx) untuk update data profil</label>
                        <div id="dropzone-excel" role="button" tabindex="0"
                            @click="$refs.excelInput.click()"
                            @keydown.enter.prevent="$refs.excelInput.click()"
                            @keydown.space.prevent="$refs.excelInput.click()"
                            @dragover.prevent="dragExcel = true"
                            @dragleave.prevent="dragExcel = false"
                            @drop.prevent="dropFile($event, 'excel')"
                            :class="dragExcel ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-slate-50/60 hover:border-emerald-400 hover:bg-emerald-50/40'"
                            class="rounded-xl border-2 border-dashed p-4 text-center cursor-pointer transition-colors">
                            <i class="bi bi-file-earmark-excel text-2xl text-slate-300 block mb-1"></i>
                            <p class="text-[11px] font-bold text-slate-600 mb-0">Klik / seret file Excel ke sini</p>
                            <p x-show="excelFile" x-cloak class="mt-1 font-mono text-[11px] font-bold text-emerald-700 break-all mb-0"
                                x-text="excelFile ? excelFile.name : ''"></p>
                        </div>
                        <input type="file" x-ref="excelInput" class="hidden" accept=".xlsx"
                            @change="handleFile($event, 'excel')">
                    </div>

                    <div>
                        <label for="dropzone-word" class="block text-xs font-semibold text-slate-700 mb-1.5">Word (.docx) untuk update foto</label>
                        <div id="dropzone-word" role="button" tabindex="0"
                            @click="$refs.wordInput.click()"
                            @keydown.enter.prevent="$refs.wordInput.click()"
                            @keydown.space.prevent="$refs.wordInput.click()"
                            @dragover.prevent="dragWord = true"
                            @dragleave.prevent="dragWord = false"
                            @drop.prevent="dropFile($event, 'word')"
                            :class="dragWord ? 'border-cyan-500 bg-cyan-50' : 'border-slate-200 bg-slate-50/60 hover:border-cyan-400 hover:bg-cyan-50/40'"
                            class="rounded-xl border-2 border-dashed p-4 text-center cursor-pointer transition-colors">
                            <i class="bi bi-file-earmark-word text-2xl text-slate-300 block mb-1"></i>
                            <p class="text-[11px] font-bold text-slate-600 mb-0">Klik / seret file Word ke sini</p>
                            <p x-show="wordFile" x-cloak class="mt-1 font-mono text-[11px] font-bold text-cyan-700 break-all mb-0"
                                x-text="wordFile ? wordFile.name : ''"></p>
                        </div>
                        <input type="file" x-ref="wordInput" class="hidden" accept=".docx"
                            @change="handleFile($event, 'word')">
                    </div>
                </section>
            </x-ui.card>
        </div>

        <!-- Panel preview -->
        <div class="xl:col-span-3">
            <x-ui.card title="Pratinjau Data" icon="bi-eye" body-class="p-0">
                <x-slot:actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-bold" x-text="previewBadge"></span>
                        <button type="button" @click="submitUpdate()" :disabled="dataGuru.length === 0 || busy"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-xs shadow-blue-500/20">
                            <span x-show="busy" x-cloak class="inline-block w-3.5 h-3.5 border-2 border-white/70 border-t-transparent rounded-full animate-spin"></span>
                            <i x-show="!busy" class="bi bi-cloud-upload"></i>
                            <span x-text="busy ? 'Menyimpan...' : 'Update Sekarang'"></span>
                        </button>
                    </div>
                </x-slot:actions>

                <div class="max-h-[600px] min-h-[300px] overflow-auto">

                    <div x-show="!previewMode" class="text-center py-14 px-6 text-slate-400">
                        <i class="bi bi-file-earmark-arrow-up text-5xl mb-5 block opacity-25"></i>
                        <p class="text-xs font-semibold mb-1">Silakan upload file untuk melihat pratinjau data.</p>
                        <p class="text-[11px] mb-0">Baris dengan ID terisi akan diupdate &bull; baris tanpa ID dilewati</p>
                    </div>

                    <!-- Preview Excel -->
                    <table x-show="previewMode === 'excel'" x-cloak class="w-full min-w-[640px] border-collapse text-left text-xs">
                        <thead class="sticky top-0 z-10 bg-white shadow-sm">
                            <tr class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <template x-for="(cell, i) in header" :key="'h' + i">
                                    <th class="whitespace-nowrap px-3 py-2.5" x-text="cell ?? ''"></th>
                                </template>
                                <th class="whitespace-nowrap px-3 py-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <template x-for="(row, ri) in rows.slice(0, 50)" :key="'r' + ri">
                                <tr :class="hasId(row) ? '' : 'bg-rose-50/60'">
                                    <template x-for="(cell, ci) in row" :key="'c' + ri + '-' + ci">
                                        <td class="whitespace-nowrap px-3 py-2" x-text="cell ?? ''"></td>
                                    </template>
                                    <td class="whitespace-nowrap px-3 py-2 text-center">
                                        <span x-show="hasId(row)"
                                            class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-bold">Valid</span>
                                        <span x-show="!hasId(row)" title="Tanpa ID — dilewati"
                                            class="inline-flex px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 text-[10px] font-bold">Invalid</span>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="rows.length > 50">
                                <td :colspan="header.length + 1" class="px-3 py-3 text-center text-slate-400"
                                    x-text="'... ' + (rows.length - 50) + ' baris lainnya ...'"></td>
                            </tr>
                            <tr x-show="rows.length === 0">
                                <td :colspan="header.length + 1" class="px-3 py-10 text-center text-slate-400">
                                    Tidak ada baris data di file ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Preview Word -->
                    <div x-show="previewMode === 'word'" x-cloak x-html="wordHtml"
                        class="p-4 text-xs text-slate-700 [&_table]:w-full [&_table]:border-collapse [&_th]:border [&_th]:border-slate-200 [&_th]:bg-slate-50 [&_th]:px-2 [&_th]:py-1.5 [&_th]:text-left [&_td]:border [&_td]:border-slate-200 [&_td]:px-2 [&_td]:py-1.5 [&_td]:align-middle [&_img]:max-w-[60px] [&_img]:h-auto"></div>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="{{ asset('assets/app/js/excel/exceljs.js') }}"></script>
<script src="{{ asset('assets/app/js/mammoth.browser.min.js') }}"></script>
<script src="{{ asset('assets/app/js/FileSaver.min.js') }}"></script>
<script src="{{ asset('assets/app/js/html-docx.js') }}"></script>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('guruBulkUpdate', () => ({
        urls: {},
        csrf: '',

        dragExcel: false,
        dragWord: false,
        excelFile: null,
        wordFile: null,

        previewMode: null,
        header: [],
        rows: [],
        idColumn: 1,
        wordHtml: '',
        dataGuru: [],
        busy: false,

        init() {
            const cfg = JSON.parse(this.$el.dataset.config || '{}');
            this.urls = cfg.urls || {};
            this.csrf = cfg.csrf || '';
        },

        get previewBadge() {
            if (this.previewMode === 'excel') {
                return this.rows.length + ' baris \u00b7 ' + this.dataGuru.length + ' siap update';
            }
            if (this.previewMode === 'word') {
                return this.dataGuru.length + ' foto terdeteksi';
            }
            return '0 baris';
        },

        escapeHtml(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[ch]));
        },

        normalizeHeader(value) {
            return String(value == null ? '' : value).toLowerCase().replace(/[^a-z]/g, '');
        },

        findColumn(header, names) {
            for (let i = 0; i < header.length; i++) {
                if (names.includes(this.normalizeHeader(header[i]))) return i;
            }
            return -1;
        },

        columnIndex(header, names, fallback) {
            const index = this.findColumn(header, names);
            return index >= 0 ? index : fallback;
        },

        hasId(row) {
            return String(row[this.idColumn] ?? '').trim() !== '';
        },

        dropFile(event, kind) {
            if (kind === 'excel') this.dragExcel = false;
            else this.dragWord = false;

            const files = event.dataTransfer && event.dataTransfer.files;
            if (files && files.length) this.dispatchFile(files[0], kind);
        },

        handleFile(event, kind) {
            const file = event.target.files && event.target.files[0];
            if (file) this.dispatchFile(file, kind);
            event.target.value = '';
        },

        dispatchFile(file, kind) {
            if (kind === 'excel') this.readExcel(file);
            else this.readWord(file);
        },

        async readExcel(file) {
            if (!/\.xlsx$/i.test(file.name)) {
                Swal.fire('Format Salah', 'Gunakan file .xlsx hasil download di halaman ini.', 'warning');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file maksimal 10MB.', 'warning');
                return;
            }

            this.excelFile = file;
            this.wordFile = null;
            this.wordHtml = '';

            Swal.fire({ title: 'Membaca Excel...', didOpen: () => Swal.showLoading() });

            try {
                const workbook = new ExcelJS.Workbook();
                await workbook.xlsx.load(await file.arrayBuffer());
                const sheet = workbook.worksheets[0];

                let headerRow = [];
                const dataRows = [];
                sheet.eachRow((row, idx) => {
                    const values = row.values.slice(1);
                    if (idx === 1) headerRow = values;
                    else dataRows.push(values);
                });

                this.header = headerRow;
                this.rows = dataRows;
                this.idColumn = this.columnIndex(headerRow, ['id', 'idguru'], 1);

                this.processExcelData(dataRows, headerRow);
                this.previewMode = 'excel';
                Swal.close();
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Gagal membaca file Excel', 'error');
            }
        },

        async readWord(file) {
            if (!/\.docx$/i.test(file.name)) {
                Swal.fire('Format Salah', 'Gunakan file .docx hasil download di halaman ini.', 'warning');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Terlalu Besar', 'Ukuran file maksimal 10MB.', 'warning');
                return;
            }

            this.wordFile = file;
            this.excelFile = null;
            this.header = [];
            this.rows = [];

            Swal.fire({ title: 'Membaca Word...', didOpen: () => Swal.showLoading() });

            try {
                const arrayBuffer = await file.arrayBuffer();
                const result = await mammoth.convertToHtml({ arrayBuffer });
                this.wordHtml = result.value;
                this.previewMode = 'word';
                this.extractWordData();
                Swal.close();
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Gagal membaca file Word', 'error');
            }
        },

        processExcelData(rows, header) {
            const idColumn = this.idColumn;
            const nipColumn = this.columnIndex(header, ['nip', 'nuptk'], 2);
            const namaColumn = this.columnIndex(header, ['namaguru', 'nama'], 3);
            const jkColumn = this.columnIndex(header, ['lp', 'jeniskelamin', 'jk'], 4);
            const usernameColumn = this.columnIndex(header, ['username'], 5);

            const list = [];
            rows.forEach((row) => {
                const id = String(row[idColumn] ?? '').trim();
                if (!id) return;

                // Kolom kosong tidak dikirim: server menolak baris bila kolom
                // wajib (nama/NIP/username) ada tapi isinya kosong.
                const guru = { id_guru: id };

                const nip = String(row[nipColumn] ?? '').trim();
                if (nip) guru.nip = nip;

                const nama = String(row[namaColumn] ?? '').trim();
                if (nama) guru.nama_guru = nama;

                const jk = String(row[jkColumn] ?? '').trim().toUpperCase();
                if (jk === 'L' || jk === 'P') guru.jenis_kelamin = jk;

                const username = String(row[usernameColumn] ?? '').trim();
                if (username) guru.username = username;

                list.push(guru);
            });

            this.dataGuru = list;
        },

        extractWordData() {
            const doc = new DOMParser().parseFromString(this.wordHtml, 'text/html');
            const rows = doc.querySelectorAll('table tr');
            const list = [];

            rows.forEach((tr, idx) => {
                if (idx === 0) return;
                const cells = tr.querySelectorAll('td');
                if (cells.length < 6) return;

                const id = (cells[1].textContent || '').trim();
                const image = cells[5].querySelector('img');
                const src = image ? (image.getAttribute('src') || '') : '';

                if (!id || !src.startsWith('data:image/')) return;

                const sep = src.indexOf(';base64');
                if (sep < 0) return;

                list.push({
                    id_guru: id,
                    nip: (cells[2].textContent || '').trim(),
                    nama_guru: (cells[3].textContent || '').trim(),
                    jenis_kelamin: (cells[4].textContent || '').trim(),
                    foto: src.substring(sep + ';base64,'.length),
                    ext: src.substring('data:image/'.length, sep) || 'jpg',
                });
            });

            this.dataGuru = list;
        },

        async fetchGuru() {
            const response = await fetch(this.urls.downloadData, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const result = await response.json();

            if (!result.status || !Array.isArray(result.guru)) {
                Swal.fire('Gagal', result.message || 'Gagal memuat data guru', 'error');
                return null;
            }
            return result.guru;
        },

        async downloadExcel() {
            this.busy = true;
            Swal.fire({ title: 'Menyiapkan data...', didOpen: () => Swal.showLoading() });

            try {
                const guru = await this.fetchGuru();
                if (!guru) return;

                const workbook = new ExcelJS.Workbook();
                const sheet = workbook.addWorksheet('Update Data Guru');
                sheet.columns = [
                    { header: 'NO', key: 'no', width: 6 },
                    { header: 'ID', key: 'id_guru', width: 10 },
                    { header: 'NIP', key: 'nip', width: 22 },
                    { header: 'NAMA GURU', key: 'nama_guru', width: 32 },
                    { header: 'L/P', key: 'jenis_kelamin', width: 6 },
                    { header: 'USERNAME', key: 'username', width: 20 },
                ];
                guru.forEach((item) => sheet.addRow({
                    no: item.no,
                    id_guru: item.id_guru,
                    nip: item.nip ?? '',
                    nama_guru: item.nama_guru ?? '',
                    jenis_kelamin: item.jenis_kelamin ?? '',
                    username: item.username ?? '',
                }));

                const head = sheet.getRow(1);
                head.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                head.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF059669' } };
                head.alignment = { horizontal: 'center', vertical: 'middle' };
                sheet.views = [{ state: 'frozen', ySplit: 1 }];
                sheet.autoFilter = { from: 'A1', to: 'F1' };
                ['B', 'C'].forEach((col) => {
                    sheet.getColumn(col).numFmt = '@';
                });

                const buffer = await workbook.xlsx.writeBuffer();
                const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'Update_Data_Guru.xlsx';
                link.click();
                setTimeout(() => URL.revokeObjectURL(link.href), 5000);

                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Terdownload',
                    text: guru.length + ' data guru. Edit kolom NIP/NAMA GURU/L-P/USERNAME lalu upload kembali.',
                    timer: 2500,
                    showConfirmButton: false,
                });
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Gagal download data guru', 'error');
            } finally {
                this.busy = false;
            }
        },

        async downloadWord() {
            this.busy = true;
            Swal.fire({ title: 'Menyiapkan Foto...', didOpen: () => Swal.showLoading() });

            try {
                const guru = await this.fetchGuru();
                if (!guru) return;

                let html = '<h2>Update Foto Guru</h2>'
                    + '<ul>'
                    + '<li>Jangan mengubah header dan kolom NO/ID</li>'
                    + '<li>Update data pada kolom yang tersedia</li>'
                    + '<li>Ganti gambar pada kolom FOTO</li>'
                    + '</ul>'
                    + '<table border="1" style="border-collapse: collapse; width: 100%;">'
                    + '<tr style="background: #f2f2f2;">'
                    + '<th>NO</th><th>ID</th><th>NIP</th><th>NAMA GURU</th><th>L/P</th><th>FOTO</th>'
                    + '</tr>';

                guru.forEach((item, index) => {
                    html += '<tr>'
                        + '<td align="center">' + this.escapeHtml(item.no ?? (index + 1)) + '</td>'
                        + '<td align="center">' + this.escapeHtml(item.id_guru) + '</td>'
                        + '<td>' + this.escapeHtml(item.nip) + '</td>'
                        + '<td>' + this.escapeHtml(item.nama_guru) + '</td>'
                        + '<td align="center">' + this.escapeHtml(item.jenis_kelamin) + '</td>'
                        + '<td align="center"><img src="' + this.escapeHtml(item.foto) + '" width="80" alt=""></td>'
                        + '</tr>';
                });
                html += '</table>';

                const converted = htmlDocx.asBlob(html, { orientation: 'portrait' });
                saveAs(converted, 'Update_Foto_Guru.docx');

                Swal.close();
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Gagal menyiapkan file Word', 'error');
            } finally {
                this.busy = false;
            }
        },

        async submitUpdate() {
            if (this.dataGuru.length === 0) return;

            const isExcel = this.previewMode === 'excel';
            const url = isExcel ? this.urls.updateAll : this.urls.updateFoto;

            this.busy = true;
            Swal.fire({
                title: 'Memperbarui Data',
                text: 'Sedang mengirim ' + this.dataGuru.length + ' data...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            const body = new FormData();
            body.append('_token', this.csrf);

            this.dataGuru.forEach((guru, index) => {
                Object.keys(guru).forEach((key) => {
                    body.append('guru[' + index + '][' + key + ']', guru[key] ?? '');
                });
            });

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const result = await response.json();

                if (result.status) {
                    await Swal.fire('Berhasil', result.message || 'Data berhasil diperbarui', 'success');
                    window.location.href = this.urls.index;
                } else {
                    Swal.fire('Gagal', result.message || 'Data gagal diperbarui', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Koneksi terputus', 'error');
            } finally {
                this.busy = false;
            }
        },
    }));
});
</script>
@endsection
