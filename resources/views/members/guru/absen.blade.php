@extends('layouts.main')

@section('styles')
<style>
/* ── Status badge ── */
.status-badge {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.35rem 0.9rem; border-radius: 9999px;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.02em;
}
.status-hadir    { background: #dcfce7; color: #15803d; }
.status-pulang   { background: #e0f2fe; color: #0369a1; }
.status-belum    { background: #fef9c3; color: #a16207; }
.status-lengkap  { background: #dcfce7; color: #15803d; }

/* ── Jam card ── */
.jam-card {
    background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 1rem; padding: 0.875rem 1.25rem;
    display: flex; flex-direction: column; gap: 0.25rem;
}
.jam-card .jam-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
.jam-card .jam-value { font-size: 1.5rem; font-weight: 800; letter-spacing: 0.03em; font-variant-numeric: tabular-nums; }
.jam-card.masuk  .jam-value { color: #15803d; }
.jam-card.pulang .jam-value { color: #0369a1; }
.jam-card.empty  .jam-value { color: #cbd5e1; }

/* ── Action button ── */
.btn-absen {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.6rem;
    width: 100%; padding: 0.9rem 1.5rem; border-radius: 1rem;
    font-size: 1rem; font-weight: 800; letter-spacing: 0.02em;
    border: none; cursor: pointer; transition: all 0.18s ease;
    box-shadow: 0 4px 14px rgba(0,0,0,0.12);
}
.btn-absen:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.16); }
.btn-absen:active { transform: translateY(0); }
.btn-absen-masuk  { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
.btn-absen-pulang { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; }
.btn-absen-done   { background: #f1f5f9; color: #94a3b8; cursor: default; }
.btn-absen-done:hover { transform: none; box-shadow: 0 4px 14px rgba(0,0,0,0.08); }

/* ── Progress step ── */
.step-line { flex: 1; height: 2px; background: #e2e8f0; }
.step-line.done { background: #22c55e; }
.step-dot {
    width: 2rem; height: 2rem; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 800; flex-shrink: 0;
    border: 2px solid #e2e8f0; background: #fff; color: #94a3b8;
}
.step-dot.done   { background: #22c55e; border-color: #22c55e; color: #fff; }
.step-dot.active { background: #3b82f6; border-color: #3b82f6; color: #fff; }

/* ── Location/GPS card ── */
.location-card {
    background: #f0fdf4; border: 1.5px solid #bbf7d0;
    border-radius: 0.875rem; padding: 0.75rem 1rem;
    display: flex; align-items: center; gap: 0.75rem;
}
.location-card.error { background: #fef2f2; border-color: #fecaca; }
.location-card.loading { background: #eff6ff; border-color: #bfdbfe; }

/* ── Pulse animation ── */
@keyframes pulse-ring {
    0%   { transform: scale(1);   opacity: 1; }
    100% { transform: scale(1.6); opacity: 0; }
}
.pulse-ring {
    position: absolute; inset: 0; border-radius: 50%;
    background: rgba(34,197,94,0.3); animation: pulse-ring 1.6s ease-out infinite;
}
.pulse-wrapper { position: relative; display: inline-flex; align-items: center; justify-content: center; }

/* ── Camera preview ── */
#cameraContainer video { border-radius: 0.75rem; width: 100%; max-height: 280px; object-fit: cover; }
#capturedImage { border-radius: 0.75rem; max-height: 200px; object-fit: cover; }

/* ── Quick links ── */
.quick-link {
    display: flex; align-items: center; gap: 0.625rem;
    padding: 0.75rem 1rem; border-radius: 0.875rem;
    border: 1.5px solid #e2e8f0; background: #fff;
    font-size: 0.8rem; font-weight: 600; color: #475569;
    text-decoration: none; transition: all 0.15s;
}
.quick-link:hover { border-color: var(--primary-500, #6366f1); color: var(--primary-600, #4f46e5); background: var(--primary-50, #eef2ff); text-decoration: none; }
.quick-link .ql-icon {
    width: 2rem; height: 2rem; border-radius: 0.5rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
</style>
@endsection

@section('content')
<div class="fade-in-up p-4 md:p-6 space-y-5 max-w-2xl mx-auto">

    <!-- ── Page Header ── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold mb-2">
                <i class="bi bi-clock-history"></i> Absensi Harian
            </div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Presensi Guru</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ date('l, d F Y') }}</p>
        </div>
        <nav class="flex items-center gap-1.5 text-xs text-slate-500">
            <a href="{{ url('dashboard') }}" class="hover:text-blue-600 font-semibold no-underline">
                <i class="bi bi-house-door mr-1"></i>Dashboard
            </a>
            <span class="text-slate-300">/</span>
            <span class="font-bold text-indigo-600">Absensi</span>
        </nav>
    </div>

    <!-- ── Status & Jam Kartu ── -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 pb-4">

            <!-- Progress steps -->
            <div class="flex items-center gap-2 mb-5">
                <div class="step-dot {{ $attendance && $attendance->jam_masuk ? 'done' : ($attendance ? 'active' : '') }}">
                    @if($attendance && $attendance->jam_masuk)
                        <i class="bi bi-check-lg"></i>
                    @else
                        <span>1</span>
                    @endif
                </div>
                <div class="step-line {{ $attendance && $attendance->jam_masuk ? 'done' : '' }}"></div>
                <div class="step-dot {{ $attendance && $attendance->jam_pulang ? 'done' : ($attendance && $attendance->jam_masuk ? 'active' : '') }}">
                    @if($attendance && $attendance->jam_pulang)
                        <i class="bi bi-check-lg"></i>
                    @else
                        <span>2</span>
                    @endif
                </div>
                <div class="step-line {{ $attendance && $attendance->jam_pulang ? 'done' : '' }}"></div>
                <div class="step-dot {{ $attendance && $attendance->jam_masuk && $attendance->jam_pulang ? 'done' : '' }}">
                    @if($attendance && $attendance->jam_masuk && $attendance->jam_pulang)
                        <i class="bi bi-check-lg"></i>
                    @else
                        <span>3</span>
                    @endif
                </div>
                <span class="text-xs text-slate-400 font-semibold ml-1">
                    @if(!$attendance || !$attendance->jam_masuk)
                        Belum absen masuk
                    @elseif(!$attendance->jam_pulang)
                        Sudah masuk, belum pulang
                    @else
                        Absensi lengkap ✓
                    @endif
                </span>
            </div>

            <!-- Jam masuk & pulang -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="jam-card {{ $attendance && $attendance->jam_masuk ? 'masuk' : 'empty' }}">
                    <span class="jam-label"><i class="bi bi-box-arrow-in-right me-1"></i>Jam Masuk</span>
                    <span class="jam-value">
                        @if($attendance && $attendance->jam_masuk)
                            {{ date('H:i', strtotime($attendance->jam_masuk)) }}
                            <span class="text-xs font-semibold text-slate-400">WIB</span>
                        @else
                            --:--
                        @endif
                    </span>
                    @if($attendance && $attendance->jam_masuk)
                        @if(!empty($attendance->nama_lokasi_masuk))
                            <span class="text-xs text-slate-400 mt-0.5 truncate">
                                <i class="bi bi-geo-alt me-0.5"></i>{{ $attendance->nama_lokasi_masuk }}
                            </span>
                        @endif
                        @php
                            $statusMasuk = $attendance->status_masuk ?? null;
                        @endphp
                        @if($statusMasuk === 'LATE')
                            <span class="text-xs font-bold text-amber-500 mt-0.5">
                                <i class="bi bi-clock me-0.5"></i>Terlambat
                                @if(!empty($attendance->telat_menit))
                                    {{ $attendance->telat_menit }} mnt
                                @endif
                            </span>
                        @elseif($statusMasuk === 'ONTIME')
                            <span class="text-xs font-bold text-emerald-500 mt-0.5">
                                <i class="bi bi-check-circle me-0.5"></i>Tepat Waktu
                            </span>
                        @endif
                    @else
                        <span class="text-xs text-slate-300">Belum tercatat</span>
                    @endif
                </div>

                <div class="jam-card {{ $attendance && $attendance->jam_pulang ? 'pulang' : 'empty' }}">
                    <span class="jam-label"><i class="bi bi-box-arrow-right me-1"></i>Jam Pulang</span>
                    <span class="jam-value">
                        @if($attendance && $attendance->jam_pulang)
                            {{ date('H:i', strtotime($attendance->jam_pulang)) }}
                            <span class="text-xs font-semibold text-slate-400">WIB</span>
                        @else
                            --:--
                        @endif
                    </span>
                    @if($attendance && $attendance->jam_pulang)
                        @if(!empty($attendance->nama_lokasi_pulang))
                            <span class="text-xs text-slate-400 mt-0.5 truncate">
                                <i class="bi bi-geo-alt me-0.5"></i>{{ $attendance->nama_lokasi_pulang }}
                            </span>
                        @endif
                        @php
                            $statusPulang = $attendance->status_pulang ?? null;
                        @endphp
                        @if($statusPulang === 'EARLY')
                            <span class="text-xs font-bold text-amber-500 mt-0.5">
                                <i class="bi bi-arrow-left-circle me-0.5"></i>Pulang Awal
                            </span>
                        @else
                            <span class="text-xs font-bold text-sky-500 mt-0.5">
                                <i class="bi bi-check-circle me-0.5"></i>Tercatat
                            </span>
                        @endif
                    @else
                        <span class="text-xs text-slate-300">Belum tercatat</span>
                    @endif
                </div>
            </div>

            <!-- GPS / Location status indicator -->
            <div id="locationStatus" class="location-card loading mb-4 hidden">
                <div id="locIcon" class="text-blue-500 text-lg"><i class="bi bi-compass"></i></div>
                <div class="min-w-0">
                    <p id="locTitle" class="text-xs font-bold text-blue-700">Mendapatkan lokasi…</p>
                    <p id="locDesc" class="text-xs text-blue-500 truncate"></p>
                </div>
            </div>

            <!-- Camera preview -->
            <div class="mt-1 mb-3" id="cameraContainer" style="display:none;">
                <video id="videoPreview" playsinline autoplay></video>
                <button id="btnCapture" class="btn-tw btn-primary w-full mt-2 rounded-xl">
                    <i class="bi bi-camera-fill me-1"></i> Ambil Foto Selfie
                </button>
            </div>
            <canvas id="canvasCapture" class="hidden"></canvas>
            <div id="capturedWrapper" class="hidden mb-3 text-center">
                <img id="capturedImage" class="inline-block img-fluid rounded-xl" alt="foto absen">
                <p class="text-xs text-slate-400 mt-1"><i class="bi bi-check-circle-fill text-emerald-500 me-1"></i>Foto berhasil diambil</p>
            </div>

            <!-- Action button -->
            <div id="actionArea">
                @if(!$attendance || !$attendance->jam_masuk)
                    <button id="btnCheckIn" class="btn-absen btn-absen-masuk">
                        <div class="pulse-wrapper">
                            <div class="pulse-ring"></div>
                            <i class="bi bi-box-arrow-in-right" style="font-size:1.2rem"></i>
                        </div>
                        Absen Masuk
                    </button>
                @elseif(!$attendance->jam_pulang)
                    <button id="btnCheckOut" class="btn-absen btn-absen-pulang">
                        <i class="bi bi-box-arrow-right" style="font-size:1.2rem"></i>
                        Absen Pulang
                    </button>
                @else
                    <div class="btn-absen btn-absen-done text-sm">
                        <i class="bi bi-patch-check-fill text-emerald-400" style="font-size:1.1rem"></i>
                        Absensi Hari Ini Sudah Lengkap
                    </div>
                @endif
            </div>

            <p class="text-center text-xs text-slate-400 mt-3">
                <i class="bi bi-info-circle me-1"></i>
                Pastikan GPS aktif{{ $setting->foto_wajib ?? false ? ' dan kamera diizinkan' : '' }}
            </p>
        </div>

        <!-- Jadwal kerja hari ini -->
        @if(isset($setting) && (!empty($setting->jam_masuk) || !empty($setting->jam_pulang)))
        <div class="px-5 pb-4">
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                <i class="bi bi-calendar2-week text-slate-400 text-sm"></i>
                <span class="text-xs text-slate-500">
                    Jadwal hari ini:
                    <strong class="text-slate-700">{{ date('H:i', strtotime($setting->jam_masuk ?? '07:00:00')) }}</strong>
                    &ndash;
                    <strong class="text-slate-700">{{ date('H:i', strtotime($setting->jam_pulang ?? '15:00:00')) }}</strong>
                </span>
            </div>
        </div>
        @endif
    </div>

    <!-- ── Quick links ── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <a href="{{ url('presensi/rekap-denda') }}" class="quick-link">
            <span class="ql-icon bg-red-50 text-red-500"><i class="bi bi-wallet2"></i></span>
            <div>
                <p class="font-bold text-slate-800 text-xs">Rekap Denda</p>
                <p class="text-[11px] text-slate-400">Lihat & bayar denda absensi</p>
            </div>
            <i class="bi bi-chevron-right ml-auto text-slate-300 text-xs"></i>
        </a>
        <a href="{{ url('presensi/denda-detail') }}" class="quick-link">
            <span class="ql-icon bg-amber-50 text-amber-500"><i class="bi bi-receipt-cutoff"></i></span>
            <div>
                <p class="font-bold text-slate-800 text-xs">Detail Denda Bulan Ini</p>
                <p class="text-[11px] text-slate-400">Rincian per tanggal</p>
            </div>
            <i class="bi bi-chevron-right ml-auto text-slate-300 text-xs"></i>
        </a>
        <a href="{{ url('presensi/ranking') }}" class="quick-link">
            <span class="ql-icon bg-indigo-50 text-indigo-500"><i class="bi bi-trophy"></i></span>
            <div>
                <p class="font-bold text-slate-800 text-xs">Ranking Kehadiran</p>
                <p class="text-[11px] text-slate-400">Lihat posisi kamu bulan ini</p>
            </div>
            <i class="bi bi-chevron-right ml-auto text-slate-300 text-xs"></i>
        </a>
        <a href="{{ url('dashboard') }}" class="quick-link">
            <span class="ql-icon bg-sky-50 text-sky-500"><i class="bi bi-house-door"></i></span>
            <div>
                <p class="font-bold text-slate-800 text-xs">Dashboard</p>
                <p class="text-[11px] text-slate-400">Kembali ke halaman utama</p>
            </div>
            <i class="bi bi-chevron-right ml-auto text-slate-300 text-xs"></i>
        </a>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const settingFotoWajib = {{ isset($setting) && $setting->foto_wajib ? 'true' : 'false' }};
    const radiusMeter = {{ $setting->radius_meter ?? 100 }};
    const csrfTokenName = '{{ '_token' }}';
    let csrfTokenValue = '{{ csrf_token() }}';

    let currentAction = null;
    let fotoBase64 = '';

    $(document).ready(function () {

        // Cek denda yang belum dibayar
        $.get('{{ url('presensi/denda-ytd') }}', function (res) {
            if (res.status && res.data && res.data.sisa_denda > 0) {
                if (sessionStorage.getItem('denda_alert_dismissed_' + res.data.sisa_denda)) return;
                const sisaDenda  = parseInt(res.data.sisa_denda).toLocaleString('id-ID');
                const totalDenda = parseInt(res.data.total_denda || res.data.sisa_denda).toLocaleString('id-ID');
                const terbayar   = parseInt(res.data.terbayar || 0).toLocaleString('id-ID');

                Swal.fire({
                    html: `
                        <div class="px-2 py-2 text-center">
                            <div class="mb-3 d-inline-flex align-items-center justify-content-center"
                                 style="width:64px;height:64px;background:#fee2e2;color:#dc2626;border-radius:22px;box-shadow:0 8px 24px rgba(220,38,38,.15)">
                                <i class="bi bi-receipt-cutoff" style="font-size:30px"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Pemberitahuan Denda</h5>
                            <p class="text-muted small mb-3">Terdapat akumulasi denda absensi yang perlu diselesaikan.</p>
                            <div class="p-3 mb-3 text-start" style="background:#fff5f5;border:1.5px dashed #fca5a5;border-radius:18px">
                                <div class="flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted" style="font-size:10.5px;font-weight:600;text-transform:uppercase">Sisa Denda Belum Lunas</span>
                                    <span class="badge-tw badge-danger text-white rounded-pill px-2 py-0" style="font-size:9.5px">Belum Lunas</span>
                                </div>
                                <div class="fw-bold text-danger my-1" style="font-size:24px">Rp ${sisaDenda}</div>
                                <div class="flex justify-content-between text-muted pt-2 border-top" style="border-color:#fee2e2 !important;font-size:11px">
                                    <span>Total: <strong>Rp ${totalDenda}</strong></span>
                                    <span>Terbayar: <strong class="text-success">Rp ${terbayar}</strong></span>
                                </div>
                            </div>
                        </div>`,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-wallet2 me-1"></i> Lihat Detail & Bayar',
                    cancelButtonText: 'Nanti Saja',
                    buttonsStyling: false,
                    customClass: {
                        popup: 'rounded-5 border-0 shadow-lg px-2 py-3',
                        confirmButton: 'btn-tw btn-danger py-2 px-4 rounded-pill fw-bold shadow-sm me-2',
                        cancelButton: 'btn-tw btn-light py-2 px-4 rounded-pill text-muted fw-semibold',
                    },
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ url('presensi/rekap-denda') }}';
                    } else {
                        sessionStorage.setItem('denda_alert_dismissed_' + res.data.sisa_denda, '1');
                    }
                });
            }
        }).fail(() => console.error('Gagal mengambil data denda'));

        // Button handlers
        $(document).on('click', '#btnCheckIn',  () => { currentAction = 'checkin';  startProcess(); });
        $(document).on('click', '#btnCheckOut', () => { currentAction = 'checkout'; startProcess(); });
    });

    function startProcess() {
        if (settingFotoWajib) {
            initCamera();
        } else {
            requestGeolocation();
        }
    }

    // ── Camera ─────────────────────────────────────────────────────────────────
    function initCamera() {
        $('#cameraContainer').show();
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(stream => {
                const video = document.getElementById('videoPreview');
                video.srcObject = stream;
                video.play();
            })
            .catch(err => Swal.fire('Gagal Akses Kamera', err.message, 'error'));
    }

    $('#btnCapture').on('click', function () {
        const video  = document.getElementById('videoPreview');
        const canvas = document.getElementById('canvasCapture');
        canvas.width  = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        fotoBase64 = canvas.toDataURL('image/jpeg', 0.8);
        $('#capturedImage').attr('src', fotoBase64);
        $('#capturedWrapper').removeClass('hidden');
        if (video.srcObject) video.srcObject.getTracks().forEach(t => t.stop());
        $('#cameraContainer').hide();
        requestGeolocation();
    });

    // ── Geolocation ────────────────────────────────────────────────────────────
    function setLocationCard(state, title, desc) {
        const card = document.getElementById('locationStatus');
        const icon = document.getElementById('locIcon');
        card.classList.remove('hidden', 'loading', 'error');
        card.classList.add(state === 'error' ? 'error' : state === 'loading' ? 'loading' : '');
        const colors = { loading: ['text-blue-500',  'text-blue-700',  'text-blue-500'],
                         success: ['text-emerald-500','text-emerald-700','text-emerald-600'],
                         error:   ['text-red-500',    'text-red-700',    'text-red-500'] };
        const [ic, tc, dc] = colors[state] || colors.loading;
        icon.className = ic + ' text-lg';
        icon.innerHTML = { loading: '<i class="bi bi-compass"></i>', success: '<i class="bi bi-geo-alt-fill"></i>', error: '<i class="bi bi-exclamation-triangle-fill"></i>' }[state] || '';
        document.getElementById('locTitle').className = 'text-xs font-bold ' + tc;
        document.getElementById('locTitle').textContent = title;
        document.getElementById('locDesc').className = 'text-xs truncate ' + dc;
        document.getElementById('locDesc').textContent = desc || '';
    }

    function requestGeolocation() {
        if (!navigator.geolocation) {
            Swal.fire('Error', 'Geolocation tidak didukung browser ini', 'error');
            return;
        }
        setLocationCard('loading', 'Mendapatkan lokasi…', 'Mohon tunggu sebentar');
        Swal.showLoading();
        navigator.geolocation.getCurrentPosition(
            pos => {
                setLocationCard('success', 'Lokasi berhasil didapat', `${pos.coords.latitude.toFixed(5)}, ${pos.coords.longitude.toFixed(5)}`);
                sendAttendance(pos.coords.latitude, pos.coords.longitude);
            },
            err => {
                Swal.close();
                setLocationCard('error', 'Gagal mendapatkan lokasi', err.message);
                Swal.fire('Gagal Lokasi', err.message, 'error');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    // ── Send attendance ────────────────────────────────────────────────────────
    function sendAttendance(lat, lng, confirm = 0) {
        if (!lat || !lng) {
            Swal.fire('Error', 'Lokasi tidak valid. Pastikan GPS aktif.', 'error'); return;
        }
        if (!currentAction) {
            Swal.fire('Error', 'Aksi tidak valid. Silakan refresh halaman.', 'error'); return;
        }

        Swal.showLoading();
        $.ajax({
            url: `{{ url('absensi/') }}${currentAction}`,
            method: 'POST',
            data: { lat, lng, foto: fotoBase64, confirm, [csrfTokenName]: csrfTokenValue },
            dataType: 'json',
            success: res => {
                Swal.close();
                if (res.data && res.data[csrfTokenName]) csrfTokenValue = res.data[csrfTokenName];
                else if (res[csrfTokenName]) csrfTokenValue = res[csrfTokenName];

                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => location.reload());
                } else {
                    const requireConfirm = res.require_confirm || (res.data && res.data.require_confirm);
                    if (requireConfirm) {
                        Swal.fire({
                            title: 'Peringatan Absensi',
                            text: res.message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal',
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                        }).then(result => {
                            if (result.isConfirmed) {
                                Swal.showLoading();
                                sendAttendance(lat, lng, 1);
                            }
                        });
                    } else {
                        Swal.fire('Gagal', res.message, 'error');
                    }
                }
            },
            error: (xhr) => {
                Swal.close();
                const msg = xhr.responseJSON?.message || xhr.statusText || 'Terjadi kesalahan sistem';
                Swal.fire('Error', msg, 'error');
            },
        });
    }
</script>
@endsection
