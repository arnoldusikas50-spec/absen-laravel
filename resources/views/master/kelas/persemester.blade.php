@extends('layouts.main')

@section('content')
<div class="fade-in-up space-y-4 p-4 md:p-6">
    <x-ui.page-header :title="$judul" icon="bi-arrow-left-right" tone="blue"
        subtitle="Salin kelas dan daftar siswa dari Semester 1 ke Semester 2"
        :crumbs="['Data Kelas', 'Salin Semester']">
        <x-slot:actions>
            <a href="{{ url('datakelas') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-slate-100 px-3.5 py-2 text-xs font-bold text-slate-700 no-underline transition-colors hover:bg-slate-200">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :title="$subjudul" icon="bi-info-circle" tone="blue" body-class="p-5">
        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3.5 text-xs text-blue-900">
            <p class="mb-1.5 flex items-center gap-1.5 font-bold">
                <i class="bi bi-info-circle"></i> Fitur Salin Semester
            </p>
            <p class="mb-2 leading-relaxed">
                Fitur ini menyalin semua kelas dan siswa TP <b>{{ $tp_active->tahun }}</b> dari Semester 1 ke Semester 2.
            </p>
            <ul class="mb-0 list-disc space-y-1 pl-4 leading-relaxed">
                <li>Hanya kelas yang <b>belum ada</b> di semester 2 yang ditampilkan untuk disalin.</li>
                <li>Centang kotak di kolom <b>Copy</b> untuk memilih kelas.</li>
                <li>Klik <b>Simpan</b> untuk memulai proses penyalinan.</li>
            </ul>
        </div>

        @if (count($kelas) === 0)
            <div class="mt-4">
                <x-ui.empty icon="bi-exclamation-triangle" title="Tidak ada kelas untuk disalin"
                    description="Belum ada data kelas di Semester 1 yang dapat disalin ke Semester 2." />
            </div>
        @else
            @php
                $kelasTersedia = collect($kelas)->filter(fn ($nama) => ! in_array($nama, $kelas2));
            @endphp

            <form id="managekelas" class="mt-4 overflow-hidden rounded-2xl border border-slate-200"
                data-keys="{{ $kelasTersedia->keys()->values()->toJson() }}"
                x-data="{
                    selected: [],
                    keys: JSON.parse($el.dataset.keys),
                    get allChecked() {
                        return this.keys.length > 0 && this.selected.length === this.keys.length;
                    },
                    toggleAll(event) {
                        this.selected = event.target.checked ? [...this.keys] : [];
                    },
                    async save() {
                        const confirmed = await Swal.fire({
                            title: 'Salin Kelas?',
                            text: 'Kelas yang dipilih beserta daftar siswanya akan disalin ke Semester 2.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, Salin!',
                        });

                        if (!confirmed.isConfirmed) {
                            return;
                        }

                        Swal.fire({
                            title: 'Sedang menyalin...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading(),
                        });

                        try {
                            const response = await fetch('{{ url('datakelas/copyallfromsmt1') }}', {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: new FormData(this.$root),
                            });
                            const data = await response.json();

                            if (data.success) {
                                await Swal.fire('Berhasil', data.insert + ' kelas berhasil disalin', 'success');
                                window.location.reload();
                            } else {
                                Swal.fire('Gagal', 'Gagal menyalin data', 'error');
                            }
                        } catch (error) {
                            console.error(error);
                            Swal.fire('Error', 'Gagal menghubungi server', 'error');
                        }
                    },
                }" @submit.prevent="save">
                @csrf

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                    <p class="mb-0 text-xs font-semibold text-slate-500">
                        <span class="font-bold text-slate-700" x-text="selected.length">0</span>
                        kelas dipilih
                    </p>
                    <button type="submit" :disabled="selected.length === 0"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>

                <x-ui.table>
                    <x-slot:head>
                        <th class="w-14 px-4 py-3 text-center">No</th>
                        <th class="px-4 py-3">Kelas Semester 1</th>
                        <th class="px-4 py-3">Target Semester 2</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="w-20 px-4 py-3 text-center">
                            Copy
                            <input type="checkbox" x-ref="all" @change="toggleAll($event)" :checked="allChecked"
                                x-effect="$el.indeterminate = selected.length > 0 && !allChecked"
                                aria-label="Pilih semua kelas"
                                class="mt-1 h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </th>
                    </x-slot:head>

                    @foreach ($kelas as $key => $kls)
                        @php $sudahAda = in_array($kls, $kelas2); @endphp
                        <tr class="transition-colors hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-center text-xs font-semibold text-slate-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $kls }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $kls }}</td>
                            <td class="px-4 py-3">
                                @if ($sudahAda)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700">
                                        <i class="bi bi-check-circle"></i> Sudah Ada
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-[11px] font-bold text-rose-700">
                                        <i class="bi bi-x-circle"></i> Belum Ada
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($sudahAda)
                                    <i class="bi bi-check-lg text-xl text-emerald-600"></i>
                                @else
                                    <input type="checkbox" name="kelas_satu[]" value="{{ $key }}" x-model="selected"
                                        aria-label="Salin kelas {{ $kls }}"
                                        class="h-4 w-4 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </form>
        @endif
    </x-ui.card>
</div>
@endsection
