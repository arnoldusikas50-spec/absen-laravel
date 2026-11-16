@extends('layouts.main')

@section('content')

<?php
$totalGuru = count($gurus ?? []);
$isAdmin = (bool) ($is_admin ?? false);
$guruAktif = 0;
foreach ($gurus ?? [] as $g) {
    if ((string) ($g->active ?? '') === '1') {
        $guruAktif++;
    }
}
$guruNonaktif = $totalGuru - $guruAktif;
?>

<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-xs">
                    <i class="bi bi-person-video3 text-xl"></i>
                </span>
                <span>{{ $judul ?? 'Data Guru' }}</span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="{{ url('dashboard') }}" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="text-slate-400">Data Umum</span>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800">{{ $judul ?? 'Guru' }}</span>
        </nav>
    </div>
</div>

<!-- Main Content Area -->
<div class="content-area mt-4 space-y-6">

    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs overflow-hidden">
        <!-- Card Header Toolbar -->
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h4 class="font-bold text-slate-800 flex items-center gap-2 mb-0.5">
                    <i class="bi bi-person-lines-fill text-blue-600"></i>
                    Daftar Guru
                </h4>
                <p class="text-xs text-slate-500 mb-0">Kelola profil guru, jabatan, serta mata pelajaran yang diampu</p>
                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-semibold">
                        <i class="bi bi-people-fill text-slate-400"></i> Total <span class="text-slate-800">{{ $totalGuru }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-semibold border border-emerald-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif <span>{{ $guruAktif }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 text-[11px] font-semibold border border-rose-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Nonaktif <span>{{ $guruNonaktif }}</span>
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-full sm:w-auto">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="search" id="filterGuruInput" placeholder="Cari nama, NIP, atau jabatan..."
                        aria-label="Cari guru"
                        class="w-full sm:w-64 pl-8 pr-8 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all outline-hidden">
                    <button type="button" id="clearGuruSearch"
                        class="hidden absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
                        aria-label="Hapus pencarian">
                        <i class="bi bi-x-circle-fill text-xs"></i>
                    </button>
                </div>
                <select id="filterStatus" aria-label="Filter status guru"
                    class="px-3 py-2 text-xs bg-white border border-slate-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-hidden font-medium text-slate-700">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
                <button type="button" onclick="window.location.reload()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 hover:border-slate-300 transition-colors shadow-xs">
                    <i class="bi bi-arrow-repeat"></i> Reload
                </button>
                <?php if ($isAdmin): ?>
                    <a href="{{ url('dataguru/add') }}"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors shadow-xs shadow-blue-500/20">
                        <i class="bi bi-plus-lg"></i> Tambah / Import
                    </a>
                    <button type="button" id="btn-scan-nfc-guru" onclick="openNfcGuruModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-colors shadow-xs shadow-emerald-500/20">
                        <i class="bi bi-broadcast"></i> Scan NFC Guru
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-6">
            <?php if ($totalGuru > 0): ?>
                <div class="overflow-x-auto">
                    <table id="tableGuru" class="table-tw min-w-[980px]">
                        <thead>
                            <tr>
                                <th width="56" class="text-center">No</th>
                                <th>Nama Guru</th>
                                <th>NIP / RFID</th>
                                <th>Jabatan</th>
                                <th>Mapel &amp; Ekstra</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" width="160">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($gurus as $guru):
                                $mapels_guru = $guru->mapel_kelas ?? [];
                                $ekstras_guru = $guru->ekstra_kelas ?? [];
                                $isActive = (string) ($guru->active ?? '') === '1';

                                $mapelNama = [];
                                foreach ($mapels_guru as $m) {
                                    $mapelNama[] = $m->nama_mapel ?? '-';
                                }
                                $ekstraNama = [];
                                foreach ($ekstras_guru as $e) {
                                    $ekstraNama[] = $e->nama_ekstra ?? '-';
                                }

                                $foto = $guru->foto && file_exists(public_path() . '/' . $guru->foto)
                                    ? url($guru->foto)
                                    : asset('assets/img/guru.png');

                                $jk = match (strtoupper((string) ($guru->jenis_kelamin ?? ''))) {
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                    default => '',
                                };
                                ?>
                                <tr data-status="{{ $isActive ? '1' : '0' }}">
                                    <td class="text-center font-bold text-slate-400" data-order="{{ $no }}">{{ $no++ }}</td>

                                    <td data-order="{{ $guru->nama_guru }}">
                                        <div class="flex items-center gap-3 min-w-[230px]">
                                            <div class="relative shrink-0">
                                                <img src="{{ $foto }}" alt="{{ $guru->nama_guru }}"
                                                    class="w-10 h-10 rounded-full object-cover border-2 {{ $isActive ? 'border-emerald-400' : 'border-rose-300' }} shadow-xs">
                                                <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-rose-500' }} border-2 border-white"></span>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-slate-800 text-sm truncate max-w-[220px]"
                                                    title="{{ $guru->nama_guru }}">{{ $guru->nama_guru }}</div>
                                                <div class="flex items-center gap-1.5 text-[11px] text-slate-400 font-medium">
                                                    <i class="bi bi-person-vcard"></i>
                                                    <span>{{ !empty($guru->kode_guru) ? 'Kode: ' . esc($guru->kode_guru) : 'Kode belum diisi' }}</span>
                                                    <?php if ($jk !== ''): ?>
                                                        <span class="text-slate-300">•</span>
                                                        <span>{{ $jk }}</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (!empty($guru->nip)): ?>
                                            <span class="font-mono text-xs font-semibold text-slate-700">{{ $guru->nip }}</span>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">Belum diisi</span>
                                        <?php endif; ?>
                                        <?php
                                        $rfidGuru = trim((string)($guru->nomor_rfid ?? ''));
                                        ?>
                                        <div class="mt-1">
                                            <?php if ($rfidGuru !== ''): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-bold">
                                                    <i class="bi bi-credit-card-2-front"></i>
                                                    {{ $rfidGuru }}
                                                </span>
                                            <?php elseif ($isAdmin): ?>
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-slate-100 text-slate-400 border border-slate-200 text-[10px] font-medium cursor-pointer hover:bg-emerald-50 hover:text-emerald-600 btn-tap-nfc-guru-row"
                                                    data-id="{{ $guru->id_guru }}" data-nama="{{ $guru->nama_guru }}"
                                                    title="Klik untuk tap kartu NFC guru ini">
                                                    <i class="bi bi-broadcast"></i> Belum ada RFID
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if (!empty($guru->level)): ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-violet-50 text-violet-700 border border-violet-100 text-[11px] font-semibold whitespace-nowrap">
                                                <i class="bi bi-briefcase-fill text-[10px]"></i>{{ $guru->level }}
                                            </span>
                                            <?php if (!empty($guru->nama_kelas)): ?>
                                                <div class="mt-1 text-[11px] text-slate-500 font-medium">Kelas {{ $guru->nama_kelas }}</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-100 text-slate-500 border border-slate-200 text-[11px] font-medium whitespace-nowrap">
                                                <i class="bi bi-book text-[10px]"></i>Guru Mata Pelajaran
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($mapels_guru) || !empty($ekstras_guru)): ?>
                                            <div class="flex flex-wrap items-center gap-1 max-w-[250px]">
                                                <?php foreach (array_slice($mapels_guru, 0, 2) as $mapel):
                                                    $klsList = [];
                                                    foreach (($mapel->kelas_mapel ?? []) as $kls_mpl) {
                                                        $klsId = $kls_mpl->id_kelas ?? $kls_mpl->kelas ?? null;
                                                        if ($klsId && isset($kelass[$klsId])) {
                                                            $klsList[] = $kelass[$klsId];
                                                        }
                                                    }
                                                    $title = ($mapel->nama_mapel ?? '-') . (!empty($klsList) ? ' (' . implode(', ', $klsList) . ')' : '');
                                                    ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg bg-blue-50/80 text-blue-700 border border-blue-100 text-[11px] font-medium"
                                                        title="{{ $title }}">
                                                        <i class="bi bi-book text-[10px] mr-1"></i>
                                                        <span class="truncate max-w-[110px]">{{ $mapel->nama_mapel ?? '-' }}</span>
                                                        <?php if (!empty($klsList)): ?>
                                                            <span class="ml-1 text-[10px] font-bold text-blue-500">[{{ count($klsList) }}]</span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>

                                                <?php if (count($mapels_guru) > 2): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 text-[11px] font-semibold"
                                                        title="{{ implode(', ', $mapelNama) }}">
                                                        +{{ count($mapels_guru) - 2 }} mapel
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!empty($ekstras_guru)): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-lg bg-amber-50/80 text-amber-700 border border-amber-100 text-[11px] font-medium"
                                                        title="{{ implode(', ', $ekstraNama) }}">
                                                        <i class="bi bi-star text-[10px] mr-1"></i>{{ count($ekstras_guru) }} ekstra
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">Belum ada</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border text-[10px] font-bold uppercase tracking-wide whitespace-nowrap {{ $isActive ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100' }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                            {{ $isActive ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="inline-flex items-center justify-center gap-1">
                                            <a href="{{ url('dataguru/detail/' . $guru->id_guru) }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100 transition-colors"
                                                title="Lihat detail guru">
                                                <i class="bi bi-eye text-xs"></i>
                                            </a>
                                            <?php if ($isAdmin): ?>
                                                <a href="{{ url('dataguru/edit/' . $guru->id_guru) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-colors"
                                                    title="Ubah profil & data pribadi">
                                                    <i class="bi bi-person-gear text-xs"></i>
                                                </a>
                                                <a href="{{ url('dataguru/editJabatan/' . $guru->id_guru) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 border border-amber-100 hover:bg-amber-100 transition-colors"
                                                    title="Atur wali kelas & mapel diampu">
                                                    <i class="bi bi-briefcase text-xs"></i>
                                                </a>
                                                <button type="button"
                                                    class="btn-tap-nfc-guru-row inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 hover:bg-emerald-100 transition-colors"
                                                    data-id="{{ $guru->id_guru }}" data-nama="{{ $guru->nama_guru }}"
                                                    title="Tap kartu NFC untuk guru ini">
                                                    <i class="bi bi-broadcast text-xs"></i>
                                                </button>
                                                <button type="button"
                                                    onclick="hapus('{{ (int) $guru->id_guru }}', this)"
                                                    data-nama="{{ $guru->nama_guru }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100 transition-colors"
                                                    title="Hapus data guru">
                                                    <i class="bi bi-trash3 text-xs"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-8 text-center bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center text-2xl">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="font-bold text-slate-700 mb-1">Belum Ada Data Guru</h5>
                    <p class="text-sm text-slate-400 max-w-sm mx-auto mb-4">
                        Belum ada guru yang terdaftar. Tambahkan guru secara manual atau import dari format Excel.
                    </p>
                    <a href="{{ url('dataguru/add') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-xl hover:bg-blue-700 shadow-xs shadow-blue-500/20">
                        <i class="bi bi-plus-lg"></i> Tambah / Import Guru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function () {
        if (!$('#tableGuru').length) return;

        const tableGuru = $('#tableGuru').DataTable({
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
            language: dt_lang_id,
            dom: "<'flex flex-wrap items-center justify-between gap-3 mb-4'l>rt<'flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-4'<'text-xs text-slate-500'i><'dt-pagination'p>>",
            columnDefs: [
                { targets: [0, 4, 6], orderable: false },
                { targets: [0, 6], searchable: false }
            ]
        });

        // Nomor urut mengikuti hasil filter/urutan yang sedang tampil.
        tableGuru.on('draw.dt', function () {
            tableGuru.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
                cell.textContent = i + 1;
            });
        }).draw();

        let searchTimer;
        $('#filterGuruInput').on('input', function () {
            const value = this.value;
            $('#clearGuruSearch').toggleClass('hidden', value === '');
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                tableGuru.search(value).draw();
            }, 200);
        });

        $('#clearGuruSearch').on('click', function () {
            $('#filterGuruInput').val('').trigger('focus');
            $(this).addClass('hidden');
            tableGuru.search('').draw();
        });

        let statusFilter = '';
        $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
            if (settings.nTable.id !== 'tableGuru' || statusFilter === '') return true;
            const row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            return row ? row.getAttribute('data-status') === statusFilter : true;
        });

        $('#filterStatus').on('change', function () {
            statusFilter = this.value;
            tableGuru.draw();
        });

        // Tombol NFC inline per baris
        $(document).on('click', '.btn-tap-nfc-guru-row', function () {
            const idGuru = $(this).data('id');
            const namaGuru = $(this).data('nama');
            openNfcSingleGuru(idGuru, namaGuru);
        });
    });

    function hapus(idGuru, trigger) {
        const namaGuru = trigger && trigger.dataset ? trigger.dataset.nama : '';
        Swal.fire({
            title: "Hapus " + (namaGuru || "Data Guru") + "?",
            text: "Data guru akan dihapus bila tidak lagi memiliki data terkait.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#e11d48",
            cancelButtonColor: "#64748b",
            confirmButtonText: '<i class="bi bi-trash mr-1"></i> Ya, Hapus!',
            cancelButtonText: "Batal",
            reverseButtons: true
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url('dataguru/delete') }}',
                    data: {
                        id_guru: idGuru,
                        '_token': '{{ csrf_token() }}'
                    },
                    type: "POST",
                    dataType: "json",
                    success: function (respon) {
                        if (respon.status) {
                            Swal.fire({
                                title: "Berhasil!",
                                text: "Data guru berhasil dihapus",
                                icon: "success",
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            if (respon.count > 0) {
                                Swal.fire({
                                    title: "Tidak Dapat Dihapus",
                                    text: respon.message || "Guru ini masih memiliki data jabatan, materi, jurnal, atau forum terkait.",
                                    icon: "warning",
                                    showCancelButton: true,
                                    confirmButtonText: "Lihat Detail",
                                    cancelButtonText: "Batal"
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = '{{ route('dataguru.detail', ['id' => '__ID__']) }}'.replace('__ID__', idGuru);
                                    }
                                });
                            } else {
                                Swal.fire("Gagal", respon.message || "Gagal menghapus data", "error");
                            }
                        }
                    },
                    error: function () {
                        Swal.fire("Error", "Terjadi kesalahan pada server", "error");
                    }
                });
            }
        });
    }
</script>

{{-- ─── MODAL REGISTRASI CEPAT NFC GURU ─── --}}
@if ($isAdmin)
<div id="quickNfcGuruModal" class="fixed inset-0 z-[9999] hidden" aria-modal="true" role="dialog">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="closeNfcGuruModal()"></div>

    <!-- Panel -->
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center">
                            <i class="bi bi-broadcast text-xl"></i>
                        </div>
                        <div>
                            <h5 class="font-black text-base m-0">Scan & Tautkan Kartu NFC</h5>
                            <p class="text-emerald-100 text-xs m-0">Registrasi cepat kartu RFID Guru</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeNfcGuruModal()" class="w-8 h-8 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-all">
                        <i class="bi bi-x-lg text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <!-- Dropdown Guru -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Guru</label>
                    <select id="nfc-guru-select" class="w-full px-3 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all">
                        <option value="">-- Pilih Guru --</option>
                        @foreach($gurus as $g)
                            <option value="{{ $g->id_guru }}"
                                data-nama="{{ $g->nama_guru }}"
                                data-rfid="{{ trim($g->nomor_rfid ?? '') }}">
                                {{ $g->nama_guru }}
                                @if(trim($g->nomor_rfid ?? '') !== '')
                                    [✓ {{ $g->nomor_rfid }}]
                                @else
                                    [Belum ada RFID]
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Preview Guru Aktif -->
                <div id="nfc-guru-preview" class="hidden p-4 bg-emerald-50 border border-emerald-200 rounded-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                            <i class="bi bi-person-badge text-2xl text-emerald-600"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-sm text-emerald-800 m-0 truncate" id="nfc-guru-preview-nama">-</p>
                            <p class="text-xs text-emerald-600 m-0" id="nfc-guru-preview-rfid">Belum ada kartu terdaftar</p>
                        </div>
                    </div>
                </div>

                <!-- Sensor NFC Animasi -->
                <div class="relative flex flex-col items-center justify-center py-5 bg-gradient-to-b from-slate-50 to-white rounded-2xl border border-dashed border-emerald-300" id="nfc-guru-sensor-area">
                    <div class="relative w-20 h-20 mb-3">
                        <div class="absolute inset-0 rounded-full border-4 border-emerald-200 animate-ping opacity-40"></div>
                        <div class="absolute inset-2 rounded-full border-4 border-emerald-300 animate-ping opacity-60" style="animation-delay:0.3s"></div>
                        <div class="relative w-20 h-20 rounded-full bg-emerald-500/10 border-2 border-emerald-400 flex items-center justify-center">
                            <i class="bi bi-broadcast text-4xl text-emerald-500" id="nfc-guru-icon"></i>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-slate-700 m-0" id="nfc-guru-status-text">Tempelkan kartu RFID ke HP</p>
                    <p class="text-xs text-slate-400 mt-0.5 m-0">Chrome Android v89+ dengan NFC aktif</p>

                    <div id="nfc-guru-not-supported" class="hidden mt-3 w-full px-4 py-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700 text-center">
                        <i class="bi bi-info-circle mr-1"></i> Web NFC tidak tersedia di perangkat ini.<br>
                        Gunakan input manual USB RFID Reader di bawah.
                    </div>
                </div>

                <!-- Input fallback USB / manual -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        <i class="bi bi-keyboard mr-1"></i>Input Manual / USB RFID Reader
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="nfc-guru-manual-input" placeholder="Ketik UID atau tembak reader, tekan Enter..."
                            class="flex-1 px-3 py-2 text-xs font-mono bg-white border border-slate-200 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 outline-none transition-all"
                            autocomplete="off" />
                        <button type="button" onclick="submitNfcGuruManual()"
                            class="px-3 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors">
                            Simpan
                        </button>
                    </div>
                </div>

                <!-- Auto-advance -->
                <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" id="nfc-guru-auto-advance" class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500" checked>
                    <span>Otomatis lanjut ke guru berikutnya (tanpa RFID)</span>
                </label>

                <!-- Riwayat Sesi -->
                <div id="nfc-guru-history-wrap" class="hidden">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Riwayat Sesi Ini</p>
                    <div id="nfc-guru-history" class="space-y-1 max-h-28 overflow-y-auto pr-1"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                <span class="text-xs text-slate-500" id="nfc-guru-footer-info">NFC tidak aktif</span>
                <div class="flex gap-2">
                    <button type="button" id="btn-nfc-guru-toggle" onclick="toggleNfcGuru()"
                        class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition-colors flex items-center gap-1.5">
                        <i class="bi bi-broadcast"></i> Aktifkan NFC
                    </button>
                    <button type="button" onclick="closeNfcGuruModal()"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const ASSIGN_URL = '{{ url('dataguru/assign_rfid') }}';
    const CSRF = '{{ csrf_token() }}';

    let nfcGuruReader = null;
    let nfcGuruController = null;
    let nfcGuruActive = false;
    let currentGuruId = null;
    let currentGuruNama = '';
    let guruSessionHistory = [];

    // ── Daftar guru dari Blade (untuk auto-advance) ──
    const allGuruList = @json($gurus->map(fn($g) => ['id_guru' => $g->id_guru, 'nama_guru' => $g->nama_guru, 'nomor_rfid' => $g->nomor_rfid ?? '']));

    function escHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    window.openNfcGuruModal = function () {
        document.getElementById('quickNfcGuruModal').classList.remove('hidden');
        checkNfcSupport();
        document.getElementById('nfc-guru-manual-input').addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); submitNfcGuruManual(); }
        });
    };

    window.openNfcSingleGuru = function (idGuru, namaGuru) {
        openNfcGuruModal();
        const sel = document.getElementById('nfc-guru-select');
        sel.value = idGuru;
        sel.dispatchEvent(new Event('change'));
    };

    window.closeNfcGuruModal = function () {
        stopNfcGuru();
        document.getElementById('quickNfcGuruModal').classList.add('hidden');
    };

    function checkNfcSupport() {
        if (!('NDEFReader' in window)) {
            document.getElementById('nfc-guru-not-supported').classList.remove('hidden');
        }
    }

    // ── Dropdown guru ──
    document.addEventListener('DOMContentLoaded', function () {
        const sel = document.getElementById('nfc-guru-select');
        if (!sel) return;
        sel.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            currentGuruId = this.value ? parseInt(this.value) : null;
            currentGuruNama = opt.dataset.nama || '';
            const rfid = (opt.dataset.rfid || '').trim();
            const preview = document.getElementById('nfc-guru-preview');
            if (currentGuruId) {
                preview.classList.remove('hidden');
                document.getElementById('nfc-guru-preview-nama').textContent = currentGuruNama;
                document.getElementById('nfc-guru-preview-rfid').textContent =
                    rfid !== '' ? ('RFID: ' + rfid) : 'Belum ada kartu terdaftar';
            } else {
                preview.classList.add('hidden');
            }
        });
    });

    // ── Toggle Web NFC ──
    window.toggleNfcGuru = async function () {
        if (!('NDEFReader' in window)) {
            Swal.fire('Web NFC Tidak Tersedia', 'Gunakan Chrome Android 89+ dengan NFC aktif.', 'info');
            return;
        }
        if (nfcGuruActive) {
            stopNfcGuru();
        } else {
            await startNfcGuru();
        }
    };

    async function startNfcGuru() {
        try {
            nfcGuruController = new AbortController();
            nfcGuruReader = new NDEFReader();
            await nfcGuruReader.scan({ signal: nfcGuruController.signal });
            nfcGuruActive = true;
            updateNfcGuruUI(true);

            nfcGuruReader.onreading = (event) => {
                const rawUid = event.serialNumber;
                if (!rawUid) return;
                if (navigator.vibrate) navigator.vibrate([80, 40, 80]);
                const cleanUid = rawUid.replace(/[: -]/g, '').toUpperCase();
                document.getElementById('nfc-guru-status-text').textContent = 'Membaca: ' + cleanUid;
                setTimeout(() => {
                    if (nfcGuruActive) {
                        document.getElementById('nfc-guru-status-text').textContent = 'Tempelkan kartu RFID ke HP';
                    }
                }, 1500);
                processNfcGuruScan(cleanUid);
            };

            nfcGuruReader.onreadingerror = () => {
                if (navigator.vibrate) navigator.vibrate(200);
                document.getElementById('nfc-guru-status-text').textContent = 'Gagal membaca, tempelkan kembali.';
            };
        } catch (err) {
            nfcGuruActive = false;
            updateNfcGuruUI(false);
            Swal.fire('NFC Error', err.message || 'Gagal memulai sensor NFC.', 'error');
        }
    }

    function stopNfcGuru() {
        if (nfcGuruController) { nfcGuruController.abort(); nfcGuruController = null; }
        nfcGuruActive = false;
        updateNfcGuruUI(false);
    }

    function updateNfcGuruUI(active) {
        const $btn = document.getElementById('btn-nfc-guru-toggle');
        const $footer = document.getElementById('nfc-guru-footer-info');
        if (active) {
            $btn.innerHTML = '<i class="bi bi-stop-circle mr-1"></i> Hentikan NFC';
            $btn.classList.replace('bg-emerald-600', 'bg-rose-600');
            $btn.classList.replace('hover:bg-emerald-700', 'hover:bg-rose-700');
            $footer.textContent = 'NFC Aktif – tempelkan kartu di belakang HP';
            document.getElementById('nfc-guru-icon').classList.add('text-emerald-600');
        } else {
            $btn.innerHTML = '<i class="bi bi-broadcast mr-1"></i> Aktifkan NFC';
            $btn.classList.replace('bg-rose-600', 'bg-emerald-600');
            $btn.classList.replace('hover:bg-rose-700', 'hover:bg-emerald-700');
            $footer.textContent = 'NFC tidak aktif';
            document.getElementById('nfc-guru-status-text').textContent = 'Tempelkan kartu RFID ke HP';
        }
    }

    // ── Submit NFC scan ke server ──
    function processNfcGuruScan(uid) {
        if (!currentGuruId) {
            Swal.fire('Pilih Guru Dulu', 'Pilih nama guru sebelum menempelkan kartu.', 'warning');
            return;
        }
        doAssignRfidGuru(currentGuruId, currentGuruNama, uid);
    }

    window.submitNfcGuruManual = function () {
        const val = (document.getElementById('nfc-guru-manual-input').value || '').trim().replace(/[: -]/g, '').toUpperCase();
        if (!val) return;
        document.getElementById('nfc-guru-manual-input').value = '';
        if (!currentGuruId) {
            Swal.fire('Pilih Guru Dulu', 'Pilih nama guru sebelum menyimpan kartu.', 'warning');
            return;
        }
        doAssignRfidGuru(currentGuruId, currentGuruNama, val);
    };

    function doAssignRfidGuru(idGuru, namaGuru, uid) {
        $.ajax({
            url: ASSIGN_URL,
            type: 'POST',
            dataType: 'json',
            data: { id_guru: idGuru, nomor_rfid: uid, _token: CSRF },
            success: function (res) {
                if (res.status) {
                    addGuruHistory(namaGuru, uid, true);
                    // Update preview & dropdown
                    document.getElementById('nfc-guru-preview-rfid').textContent = 'RFID: ' + uid;
                    const opt = document.querySelector(`#nfc-guru-select option[value="${idGuru}"]`);
                    if (opt) { opt.dataset.rfid = uid; opt.text = namaGuru + ' [✓ ' + uid + ']'; }
                    // Update status di allGuruList
                    const item = allGuruList.find(g => g.id_guru == idGuru);
                    if (item) item.nomor_rfid = uid;

                    if (document.getElementById('nfc-guru-auto-advance').checked) {
                        autoAdvanceGuru();
                    }
                } else {
                    addGuruHistory(namaGuru, uid, false, res.message || 'Gagal');
                    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: res.message || 'UID kartu sudah terdaftar.', timer: 3000, showConfirmButton: false });
                }
            },
            error: function () {
                Swal.fire('Error', 'Gagal menghubungi server.', 'error');
            }
        });
    }

    function autoAdvanceGuru() {
        const sel = document.getElementById('nfc-guru-select');
        const currentIdx = Array.from(sel.options).findIndex(o => o.value == currentGuruId);
        for (let i = currentIdx + 1; i < sel.options.length; i++) {
            const opt = sel.options[i];
            if (!opt.value) continue;
            const rfid = (opt.dataset.rfid || '').trim();
            if (!rfid) {
                sel.selectedIndex = i;
                sel.dispatchEvent(new Event('change'));
                return;
            }
        }
    }

    function addGuruHistory(nama, uid, ok, errorMsg = '') {
        guruSessionHistory.unshift({ nama, uid, ok, errorMsg });
        const wrap = document.getElementById('nfc-guru-history-wrap');
        const list = document.getElementById('nfc-guru-history');
        wrap.classList.remove('hidden');
        list.innerHTML = guruSessionHistory.slice(0, 8).map(h => `
            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg ${h.ok ? 'bg-emerald-50 border border-emerald-100' : 'bg-rose-50 border border-rose-100'}">
                <i class="bi ${h.ok ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle-fill text-rose-500'} text-xs shrink-0"></i>
                <span class="text-xs font-medium ${h.ok ? 'text-emerald-800' : 'text-rose-800'} truncate">${escHtml(h.nama)}</span>
                <span class="text-[10px] font-mono ${h.ok ? 'text-emerald-600' : 'text-rose-500'} ml-auto shrink-0">${escHtml(h.uid)}</span>
            </div>
        `).join('');
    }
})();
</script>
@endif
@endsection
