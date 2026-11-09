@extends('layouts.main')

@section('content')
<!-- Content Header -->
<div class="content-header fade-in-up">
    <div class="page-header">
        <div class="min-w-0">
            <h3 class="text-xl md:text-2xl font-bold text-slate-800 flex items-center gap-2.5">
                <span class="w-10 h-10 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center shadow-xs shrink-0">
                    <i class="bi bi-people-fill text-xl"></i>
                </span>
                <span>{{ $judul ?? 'Halaman' }}</span>
            </h3>
        </div>
        <nav class="breadcrumb-tw">
            <a href="{{ url('dashboard') }}" class="no-underline text-slate-500 hover:text-blue-600">
                <i class="bi bi-house-door mr-1"></i> Dashboard
            </a>
            <span class="sep">/</span>
            <span class="active font-medium text-slate-800">{{ $subjudul ?? 'Data' }}</span>
        </nav>
    </div>
</div>

<div class="content-area">
<div class="card-tw"><div class="card-tw-header">
                <h6 class="font-bold text-slate-700"><i class="bi bi-people mr-2"></i>{{ $subjudul }}</h6>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" onclick="getDataPanitia()" class="btn-tw btn-tw-sm btn-light">
                        <i class="bi bi-arrow-repeat mr-1"></i> Reload
                    </button>
                    <button type="button" data-ui-toggle="modal" data-ui-target="#generatePanitiaModal"
                        class="btn-tw btn-tw-sm btn-info"><i class="bi bi-magic mr-1"></i> Generate dari Guru
                    </button>
                    <button type="button" data-ui-toggle="modal" data-ui-target="#createPanitiaModal"
                        class="btn-tw btn-tw-sm btn-primary"><i class="bi bi-plus mr-1"></i> Tambah Panitia
                    </button>
                </div>
            </div>
            <div class="card-tw-body">
                <div class="mb-6">
                    <div class="flex flex-wrap gap-2 mb-6">
                        <span class="badge-tw badge-primary">Total: <span id="badge-total-panitia">0</span></span>
                        <span class="badge-tw badge-success">Aktif: <span id="badge-total-aktif">0</span></span>
                        <span class="badge-tw badge-warning">Nonaktif: <span
                                id="badge-total-nonaktif">0</span></span>
                        <span class="badge-tw badge-info">Perlu Reset: <span id="badge-total-reset">0</span></span>
                    </div>
                    <div class="flex gap-2 mb-6">
                        <button type="button" class="btn-tw btn-tw-sm btn-success btn-sm shadow-sm"
                            data-action="aktifkan">
                            <i class="bi bi-people mr-1"></i> Aktifkan Semua
                        </button>
                        <button type="button" class="btn-tw btn-tw-sm btn-danger btn-sm shadow-sm"
                            data-action="nonaktifkan">
                            <i class="bi bi-slash-circle mr-1"></i> Nonaktifkan Semua
                        </button>
                    </div>
                </div>
                <form id="bulk">
                    @csrf
                    <div class="table-tw">
                        <div id="table-panitia"></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Generate Modal -->
<form action="{{ url('userpanitia/generate') }}" method="post" id="generate">
@csrf
<div class="modal fade" id="generatePanitiaModal" tabindex="-1" role="dialog"
    aria-labelledby="generatePanitiaModalLabel" aria-hidden="true">
    <div class="modal-box-tw max-w-lg" role="document">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5 class="font-bold text-slate-800" id="generatePanitiaModalLabel"><i class="bi bi-magic mr-2"></i>Generate User
                    Panitia dari Guru</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body-tw">
                <div class="alert-tw alert-info">
                    Fitur ini akan membuat akun Panitia (Jabatan: Pengawas) untuk guru yang dipilih.<br>
                    Username: p_[NIP]<br>
                    Password: 123456
                </div>
                <div class="mb-6">
                    <label>Pilih Guru</label>
                    <select class="select2 form-control" multiple="multiple" name="gurus[]"
                        data-placeholder="Pilih Guru" style="width: 100%;" required>
                        <?php foreach ($gurus as $id => $nama): ?>
                            <?php if ($id != '0'): ?>
                                <option value="{{ $id }}">{{ $nama }}</option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-info">
                    <i class="bi bi-magic mr-1"></i> Generate
                </button>
            </div>
        </div>
    </div>
</div>
</form>

<!-- Modal Create -->
<form action="{{ url('userpanitia/create') }}" method="post" id="create">
@csrf
<div class="modal fade" id="createPanitiaModal" tabindex="-1" role="dialog" aria-labelledby="createPanitiaModalLabel"
    aria-hidden="true">
    <div class="modal-box-tw max-w-lg" role="document">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5 class="font-bold text-slate-800" id="createPanitiaModalLabel"><i class="bi bi-plus-circle mr-2"></i>Tambah
                    Panitia</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body-tw">
                <div class="mb-6 row">
                    <label for="nama_panitia" class="col-md-4 form-label-tw">Nama Panitia:</label>
                    <div class="flex-1">
                        <input type="text" name="nama_panitia" class="form-control" required>
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="nip" class="col-md-4 form-label-tw">NIP:</label>
                    <div class="flex-1">
                        <input type="number" name="nip" class="form-control">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="jabatan" class="col-md-4 form-label-tw">Jabatan:</label>
                    <div class="flex-1">
                        <select name="jabatan" class="form-control" id="create-jabatan">
@foreach(['' => 'Pilih Jabatan', 'Panitia' => 'Panitia', 'Pengawas' => 'Pengawas'] as $__k => $__v)
    <option value="{{ $__k }}"{{ (is_array('') ? in_array($__k, ('')) : $__k == ('')) ? ' selected' : '' }}>{{ $__v }}</option>
@endforeach
</select>
                    </div>
                </div>
                <div class="mb-6 row hidden" id="create-ruang-group">
                    <label for="ruang" class="col-md-4 form-label-tw">Ruang:</label>
                    <div class="flex-1">
                        <?php
                        $ruang_options = ['' => 'Pilih Ruang'] + (isset($ruangs) ? (array) $ruangs : []);
                        // Assuming ruangs is array or object, casting to array if needed.
                        // If ruangs are objects in options, CI4 form_dropdown handles array options.
                        ?>
<select name="ruang" class="form-control">
@foreach($ruang_options as $__k => $__v)
    <option value="{{ $__k }}"{{ (is_array('') ? in_array($__k, ('')) : $__k == ('')) ? ' selected' : '' }}>{{ $__v }}</option>
@endforeach
</select>
<?php
                        ?>
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="username" class="col-md-4 form-label-tw">Username:</label>
                    <div class="flex-1">
                        <input type="text" name="username" class="form-control" required minlength="4" maxlength="50">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="password" class="col-md-4 form-label-tw">Password:</label>
                    <div class="flex-1">
                        <input type="password" name="password" class="form-control" required minlength="5" maxlength="50">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="email" class="col-md-4 form-label-tw">Email:</label>
                    <div class="flex-1">
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="no_hp" class="col-md-4 form-label-tw">No HP:</label>
                    <div class="flex-1">
                        <input type="text" name="no_hp" class="form-control">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="instansi" class="col-md-4 form-label-tw">Instansi/Sekolah Asal:</label>
                    <div class="flex-1">
                        <input type="text" name="instansi" class="form-control" placeholder="Asal Sekolah/Instansi">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="foto" class="col-md-4 form-label-tw">Foto Profil:</label>
                    <div class="flex-1">
                        <input type="file" name="foto" class="form-control-tw" accept="image/*">
                        <span class="text-sm text-slate-400">Format: JPG, PNG, JPEG. Maks: 2MB</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="reset" class="btn-tw btn-light">
                    <i class="bi bi-arrow-repeat mr-1"></i> Reset
                </button>
                <button type="submit" class="btn-tw btn-primary">
                    <i class="bi bi-save mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
</form>

<!-- Modal Edit -->
<form action="{{ url('userpanitia/edit') }}" method="post" id="edit">
@csrf
<div class="modal fade" id="editPanitiaModal" tabindex="-1" role="dialog" aria-labelledby="editPanitiaModalLabel"
    aria-hidden="true">
    <div class="modal-box-tw max-w-lg" role="document">
        <div class="bg-white">
            <div class="modal-header-tw">
                <h5 class="font-bold text-slate-800" id="editPanitiaModalLabel"><i class="bi bi-pencil mr-2"></i>Edit Panitia</h5>
                <button type="button" class="btn-close" data-ui-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body-tw">
                <input type="hidden" name="id_panitia">
                <div class="mb-6 row">
                    <label for="nama_panitia" class="col-md-4 form-label-tw">Nama Panitia:</label>
                    <div class="flex-1">
                        <input type="text" name="nama_panitia" class="form-control" required>
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="nip" class="col-md-4 form-label-tw">NIP:</label>
                    <div class="flex-1">
                        <input type="number" name="nip" class="form-control">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="jabatan" class="col-md-4 form-label-tw">Jabatan:</label>
                    <div class="flex-1">
                        <select name="jabatan" class="form-control" id="edit-jabatan">
@foreach(['' => 'Pilih Jabatan', 'Panitia' => 'Panitia', 'Pengawas' => 'Pengawas'] as $__k => $__v)
    <option value="{{ $__k }}"{{ (is_array('') ? in_array($__k, ('')) : $__k == ('')) ? ' selected' : '' }}>{{ $__v }}</option>
@endforeach
</select>
                    </div>
                </div>
                <div class="mb-6 row hidden" id="edit-ruang-group">
                    <label for="ruang" class="col-md-4 form-label-tw">Ruang:</label>
                    <div class="flex-1">
                        <?php
                        // Reuse options from create
                        // $ruang_options defined above
                        ?>
<select name="ruang" class="form-control">
@foreach($ruang_options as $__k => $__v)
    <option value="{{ $__k }}"{{ (is_array('') ? in_array($__k, ('')) : $__k == ('')) ? ' selected' : '' }}>{{ $__v }}</option>
@endforeach
</select>
<?php
                        ?>
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="username" class="col-md-4 form-label-tw">Username:</label>
                    <div class="flex-1">
                        <input type="text" name="username" class="form-control" required minlength="4" maxlength="50">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="password" class="col-md-4 form-label-tw">Password:</label>
                    <div class="flex-1">
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                        <span class="text-sm text-slate-400">Kosongkan jika tidak ingin mengubah password</span>
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="email" class="col-md-4 form-label-tw">Email:</label>
                    <div class="flex-1">
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="no_hp" class="col-md-4 form-label-tw">No HP:</label>
                    <div class="flex-1">
                        <input type="text" name="no_hp" class="form-control">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="instansi" class="col-md-4 form-label-tw">Instansi/Sekolah Asal:</label>
                    <div class="flex-1">
                        <input type="text" name="instansi" class="form-control" placeholder="Asal Sekolah/Instansi">
                    </div>
                </div>
                <div class="mb-6 row">
                    <label for="foto" class="col-md-4 form-label-tw">Foto Profil:</label>
                    <div class="flex-1">
                        <input type="file" name="foto" class="form-control-tw" accept="image/*">
                        <span class="text-sm text-slate-400">Format: JPG, PNG, JPEG. Maks: 2MB</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer-tw">
                <button type="button" class="btn-tw btn-secondary" data-ui-dismiss="modal">Batal</button>
                <button type="submit" class="btn-tw btn-primary">
                    <i class="bi bi-save mr-1"></i> Simpan
                </button>
            </div>
        </div>
    </div>
</div>
</form>
<form action="" method="post" id="up">
@csrf
</form>

<script src="{{ url('/') . '/' }}assets/app/js/ajax_request.js" type="text/javascript"></script>
<script>
    const user_id = '{{ $user->id }}';

    $(document).ready(function () {
        $('.select2').select2({
            theme: 'bootstrap4'
        });
        // ajaxcsrf();

        $('#edit').on('submit', async function (e) {
            e.preventDefault();
            let formData = new FormData($(this)[0]);

            // Handle file upload
            const fileInput = $(this).find('input[name="foto"]')[0];
            if (fileInput.files.length > 0) {
                formData.append('foto', fileInput.files[0]);
            }

            await ajaxPost(base_url + 'userpanitia/edit', formData, function (error, response) {
                $('#editPanitiaModal').appModal('hide');
                if (error) {
                    Swal.fire('Error', error, 'error');
                } else {
                    if (response.status) {
                        Swal.fire('Sukses', response.msg, 'success').then(() => {
                            getDataPanitia();
                        });
                    } else {
                        let html = '<ul>';
                        if (response.errors) {
                            Object.keys(response.errors).forEach(function (key) {
                                if (response.errors[key]) {
                                    html += '<li>' + response.errors[key] + '';
                                }
                            });
                        }
                        html += '</ul>';
                        Swal.fire('ERROR', html || response.msg, 'error');
                    }
                }
            });
        });

        // Toggle Ruang inputs
        $('#create-jabatan').change(function () {
            if ($(this).val() === 'Pengawas') {
                $('#create-ruang-group').removeClass('hidden');
            } else {
                $('#create-ruang-group').addClass('hidden');
            }
        });

        $('#edit-jabatan').change(function () {
            if ($(this).val() === 'Pengawas') {
                $('#edit-ruang-group').removeClass('hidden');
            } else {
                $('#edit-ruang-group').addClass('hidden');
            }
        });

        $('#create').on('submit', async function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            let formData = new FormData($(this)[0]);

            // Handle file upload
            const fileInput = $(this).find('input[name="foto"]')[0];
            if (fileInput.files.length > 0) {
                formData.append('foto', fileInput.files[0]);
            }

            await ajaxPost(base_url + 'userpanitia/create', formData, function (error, response) {
                $('#createPanitiaModal').appModal('hide');
                $('#createPanitiaModal').on('hidden', function () {
                    $(this).data('modal', null);
                });

                if (error) {
                    Swal.fire('Error', error, 'error');
                } else {
                    if (response.status) {
                        Swal.fire('Sukses', response.msg || 'Panitia berhasil ditambahkan', 'success').then(() => {
                            getDataPanitia();
                        });
                    } else {
                        let html = '<ul>';
                        if (response.errors) {
                            Object.keys(response.errors).forEach(function (key) {
                                if (response.errors[key]) {
                                    html += '<li>' + response.errors[key] + '';
                                }
                            });
                        }
                        html += '</ul>';
                        Swal.fire('ERROR', html || response.msg || 'Terjadi kesalahan', 'error');
                    }
                }
            });
        });

        $('#generate').on('submit', async function (e) {
            e.preventDefault();
            let formData = new FormData($(this)[0]);

            $('#loading').removeClass('hidden');
            await ajaxPost(base_url + 'userpanitia/generate', formData, function (error, response) {
                $('#loading').addClass('hidden');
                $('#generatePanitiaModal').appModal('hide');
                if (error) {
                    Swal.fire('Error', error.message, 'error');
                } else {
                    Swal.fire(response.status ? 'Sukses' : 'Info', response.msg, response.status ? 'success' : 'info').then(() => {
                        getDataPanitia();
                    });
                }
            });
        });

        $(".btn-action").on("click", function () {
            const action = $(this).data("action");
            Swal.fire({
                title: action === 'aktifkan' ? "Aktifkan Semua Panitia" : "Nonaktifkan Semua Panitia",
                icon: "info",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Lanjutkan"
            }).then(async result => {
                if (result.value) {
                    $('#loading').removeClass('hidden');
                    let formActivate = new FormData($('#up')[0]);
                    formActivate.append('is_active', action);
                    await ajaxPost(base_url + "userpanitia/aktifkansemua", formActivate, function (error, response) {
                        $('#loading').addClass('hidden');
                        if (error) {
                            Swal.fire('Error', error.message, 'error');
                        } else {
                            Swal.fire(response.status ? 'Sukses' : 'Error', response.msg, response.status ? 'success' : 'error');
                            if (response.status) {
                                getDataPanitia();
                            }
                        }
                    });
                }
            });
        });

        $("#table-panitia").on("click", ".btn-reset", function () {
            const username = $(this).data("username");
            const nama = $(this).data("nama");
            $('#loading').removeClass('hidden');
            let form = new FormData($('#up')[0]);
            form.append('username', username);
            form.append('nama', nama);
            ajaxPost('userpanitia/reset_login', form, function (error, response) {
                $('#loading').addClass('hidden');
                if (error) {
                    Swal.fire('Error', error.message, 'error');
                } else {
                    Swal.fire(response.status ? 'Sukses' : 'Error', response.msg, response.status ? 'success' : 'error');
                    if (response.status) {
                        getDataPanitia();
                    }
                }
            });
        });

        getDataPanitia();
    });

    function getDataPanitia() {
        $('#loading').removeClass('hidden');
        ajaxGet(base_url + 'userpanitia/data', function (error, response) {
            $('#loading').addClass('hidden');
            if (error) {
                console.error('Error:', error);
                Swal.fire('Error', error.message || 'Terjadi kesalahan saat mengambil data', 'error');
            } else {
                populateTable(response);
            }
        });
    }

    function populateTable(data) {
        const tableContainer = document.getElementById("table-panitia");
        tableContainer.innerHTML = '';

        if (!data || data.length === 0) {
            tableContainer.innerHTML = '<div class="alert-tw alert-info">Tidak ada data panitia</div>';
            $('#loading').addClass('hidden');
            return;
        }

        const table = document.createElement("table");
        table.id = "users";
        table.className = "w-full table table-striped table-bordered table-hover dataTable";

        const headers = ["No.", "Nama", "Username", "Password", "Jabatan", "Reset Login", "Status", "Aksi"];
        const thead = document.createElement("thead");
        const headerRow = document.createElement("tr");
        headers.forEach((text, index) => {
            const th = document.createElement("th");
            if (index === 0) th.setAttribute('width', '40');
            th.className = "align-middle text-center p-0";
            if (text === "No.") th.className = "align-middle text-center p-0";
            th.textContent = text;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement("tbody");

        let total = data.length;
        let totalAktif = 0;
        let totalReset = 0;

        data.forEach((panitia, index) => {
            if (panitia.active === "1") totalAktif++;
            if (panitia.reset !== null && panitia.reset !== "0") totalReset++;

            const row = document.createElement("tr");

            row.appendChild(createCell(index + 1, 'text-center align-middle'));

            const cellName = document.createElement("td");
            cellName.className = "align-middle";
            cellName.innerHTML = `<div class="flex items-center">
                <img class="avatar rounded-full border shadow-sm mr-3" 
                     src="${panitia.foto || base_url + 'assets/img/user.png'}" 
                     style="width: 45px; height: 45px; object-fit: cover;" 
                     alt="Panitia" 
                     onerror="this.src=base_url+'assets/img/user.png'">
                <div class="overflow-hidden">
                    <div class="font-bold text-slate-800 truncate" style="max-width: 200px;">${panitia.nama_panitia ?? '-'}</div>
                    <span class="text-sm text-slate-400 font-mono">${panitia.nip ?? '-'}</span>
                </div>
            </div>`;
            row.appendChild(cellName);

            row.appendChild(createCell(panitia.username ?? '-', 'text-center align-middle'));
            row.appendChild(createCell(panitia.password ?? '-', 'text-center align-middle text-monospace'));
            row.appendChild(createCell(panitia.jabatan ?? '-', 'text-center align-middle'));

            // Reset Login UI
            const cellReset = document.createElement("td");
            cellReset.className = "text-center align-middle";
            const btnReset = document.createElement("button");
            const needReset = panitia.reset !== null && panitia.reset !== "0";
            btnReset.type = 'button';
            btnReset.className = `btn btn-sm ${needReset ? 'btn-danger shadow-sm' : 'btn-secondary'} btn-reset`;
            btnReset.dataset.username = panitia.username;
            btnReset.dataset.nama = panitia.nama_panitia;
            btnReset.setAttribute('title', 'Reset Login');
            btnReset.innerHTML = '<i class="bi bi-arrow-repeat text-white"></i>';
            if (!needReset) {
                btnReset.setAttribute('disabled', 'disabled');
            }
            cellReset.appendChild(btnReset);
            row.appendChild(cellReset);

            // Status Badge
            const isActive = panitia.active === "1";
            const cellStatus = document.createElement("td");
            cellStatus.className = "text-center align-middle";
            cellStatus.innerHTML = `<span class="badge ${isActive ? 'bg-success' : 'bg-danger'}">${isActive ? 'Aktif' : 'Nonaktif'}</span>`;
            row.appendChild(cellStatus);

            // Action UI
            const cellAction = document.createElement("td");
            cellAction.className = 'text-center align-middle';

            const actionGroup = document.createElement('div');
            actionGroup.className = 'flex justify-center gap-1';

            // Edit
            const btnEdit = document.createElement('button');
            btnEdit.type = 'button';
            btnEdit.className = 'btn-tw btn-tw-sm btn-info shadow-sm';
            btnEdit.innerHTML = '<i class="bi bi-pencil text-white"></i>';
            btnEdit.setAttribute('title', 'Edit');
            btnEdit.onclick = () => editPanitia(panitia);
            actionGroup.appendChild(btnEdit);

            // Toggle Active
            const btnAction = document.createElement('button');
            btnAction.type = 'button';
            btnAction.className = `btn btn-sm ${isActive ? 'btn-warning' : 'btn-success'} shadow-sm`;
            btnAction.innerHTML = `<i class="bi ${isActive ? 'bi-slash-circle' : 'bi-person-plus-fill'} text-white"></i>`;
            btnAction.setAttribute('title', isActive ? 'Nonaktifkan' : 'Aktifkan');
            btnAction.onclick = () => toggleActive(panitia, isActive ? 'nonaktifkan' : 'aktifkan');
            actionGroup.appendChild(btnAction);

            // Delete
            const btnDelete = document.createElement('button');
            btnDelete.type = 'button';
            btnDelete.className = 'btn-tw btn-tw-sm btn-danger shadow-sm';
            btnDelete.innerHTML = '<i class="bi bi-trash text-white"></i>';
            btnDelete.setAttribute('title', 'Hapus');
            btnDelete.onclick = () => deletePanitia(panitia);
            actionGroup.appendChild(btnDelete);

            cellAction.appendChild(actionGroup);
            row.appendChild(cellAction);

            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        tableContainer.appendChild(table);
        $("#users").DataTable();
        $('#loading').addClass('hidden');

        document.getElementById('badge-total-panitia').innerText = total;
        document.getElementById('badge-total-aktif').innerText = totalAktif;
        document.getElementById('badge-total-nonaktif').innerText = total - totalAktif;
        document.getElementById('badge-total-reset').innerText = totalReset;
    }

    function toggleActive(panitia, action) {
        $('#loading').removeClass('hidden');
        let form = new FormData($('#up')[0]);
        if (panitia.id) form.append('id', panitia.id);
        form.append('id_panitia', panitia.id_panitia);
        form.append('nama', panitia.nama_panitia);
        form.append('is_active', action);
        ajaxPost(base_url + 'userpanitia/activate', form, function (error, response) {
            $('#loading').addClass('hidden');
            if (error) {
                Swal.fire('Error', error.message, 'error');
            } else {
                Swal.fire(response.status ? 'Sukses' : 'Error', response.msg, response.status ? 'success' : 'error');
                if (response.status) {
                    getDataPanitia();
                }
            }
        });
    }

    function deletePanitia(panitia) {
        Swal.fire({
            title: 'Anda yakin?',
            text: `Data panitia "${panitia.nama_panitia}" akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, hapus!'
        }).then(async (result) => {
            if (result.value) {
                $('#loading').removeClass('hidden');
                let formData = new FormData($('#up')[0]);
                formData.append('id_panitia', panitia.id_panitia);
                formData.append('nama', panitia.nama_panitia);

                await ajaxPost(base_url + 'userpanitia/delete', formData, function (error, response) {
                    $('#loading').addClass('hidden');
                    if (error) {
                        Swal.fire('Error', error.message, 'error');
                    } else {
                        Swal.fire(response.status ? 'Sukses' : 'Error', response.msg, response.status ? 'success' : 'error');
                        if (response.status) {
                            getDataPanitia();
                        }
                    }
                });
            }
        });
    }

    function editPanitia(panitia) {
        // Populate modal fields with existing data
        $('#editPanitiaModal input[name="nama_panitia"]').val(panitia.nama_panitia || '');
        $('#editPanitiaModal input[name="nip"]').val(panitia.nip || '');
        $('#editPanitiaModal select[name="jabatan"]').val(panitia.jabatan || '');
        $('#editPanitiaModal input[name="username"]').val(panitia.username || '');
        $('#editPanitiaModal input[name="email"]').val(panitia.email || '');
        $('#editPanitiaModal input[name="no_hp"]').val(panitia.no_hp || '');
        $('#editPanitiaModal input[name="no_hp"]').val(panitia.no_hp || '');
        $('#editPanitiaModal input[name="instansi"]').val(panitia.instansi || '');
        $('#editPanitiaModal input[name="id_panitia"]').val(panitia.id_panitia || '');
        $('#editPanitiaModal select[name="ruang"]').val(panitia.ruang || '');

        // Trigger change to update Ruang visibility
        $('#edit-jabatan').trigger('change');

        // Show modal
        $('#editPanitiaModal').appModal('show');
    }

    function createCell(text, classes = '') {
        const td = document.createElement('td');
        td.className = classes;
        td.innerHTML = text;
        return td;
    }
</script>
@endsection