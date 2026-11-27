<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <?php
    $theme = $theme ?? \App\Libraries\Theme::current();
    ?>
    <meta name="theme-color" content="{{ $theme['primary']['600'] ?? '#e11d48' }}">
    <title>{{ $judul ?? 'Dashboard' }} | {{ $setting->nama_sekolah ?? 'Presensi & KBM' }}</title>

    <?php
    $setting = $setting ?? new \stdClass;
    $setting->logo_kiri = $setting->logo_kiri ?? null;
    $logo_app = ($setting->logo_kiri == null) ? asset('assets/img/favicon.png') : url(''.$setting->logo_kiri);
    ?>
    <link rel="shortcut icon" href="{{ $logo_app }}" type="image/x-icon">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('assets/app/css/lucide-icons.css') }}?v={{ @filemtime(public_path() . '/' . 'assets/app/css/lucide-icons.css') }}">

    <!-- Tailwind CSS (build lokal — npm run build) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}">
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
    <!-- Tempusdominus -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/tempus-dominus/tempus-dominus.min.css') }}">
    <!-- Summernote -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-lite.min.css') }}">
    <!-- Dropify -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/dropify/css/dropify.min.css') }}">
    <!-- Datetime picker -->
    <link rel="stylesheet" href="{{ asset('assets/plugins/jquery-datetimepicker/jquery.datetimepicker.min.css') }}">
    <!-- Pignose calendar -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/pignose/css/pignose.calendar.css') }}">
    <!-- Toastr show -->
    <link rel="stylesheet" href="{{ asset('assets/app/css/show.toast.css') }}">
    <!-- jQuery -->
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/jquery-ui/jquery-ui.min.js') }}"></script>

    <!-- App Custom CSS + Tailwind overrides -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/app/css/tailwind-admin.css') }}">

    <script>
        let appUrl = '{{ rtrim(url('/') . '/', '/') }}/';
        let globalToken;
        let CSRF_NAME = '_token';
        let CSRF_HASH = '{{ csrf_token() }}';

        let tp_active  = '{{ isset($tp_active)  ? $tp_active->tahun  : "" }}';
        let smt_active = '{{ isset($smt_active) ? $smt_active->smt   : "" }}';
        let id_tp_active  = '{{ isset($tp_active)  ? $tp_active->id_tp  : "" }}';
        let id_smt_active = '{{ isset($smt_active) ? $smt_active->id_smt : "" }}';

        function startTime() {
            var t = new Date();
            var h = String(t.getHours()).padStart(2,'0');
            var m = String(t.getMinutes()).padStart(2,'0');
            var s = String(t.getSeconds()).padStart(2,'0');
            var el = document.getElementById('live-clock');
            if (el) el.innerHTML = '<span class="font-bold">' + h + ':' + m + '</span><span class="opacity-70">:' + s + '</span>';
            setTimeout(startTime, 1000);
        }

        function ajaxcsrf() {
            var csrf = {};
            csrf['{{ '_token' }}'] = '{{ csrf_token() }}';
            $.ajaxSetup({ "data": csrf });
        }

        function logout() {
            Swal.fire({
                title: "Logout",
                text: "Anda yakin ingin logout?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Logout!'
            }).then((result) => {
                if (result.value) location.href = "{{ url('auth/logout') }}";
            });
        }

        function showToast(type, msg) {
            if (type === 'success') showSuccessToast(msg);
            else if (type === 'error') showDangerToast(msg);
            else showInfoToast(msg);
        }
    </script>

    @yield('styles')
    {!! \App\Libraries\Theme::styleTag() !!}
</head>

<body x-data="adminShell" class="bg-surface-muted font-sans text-text-primary h-full antialiased" onload="startTime()">

    <!-- Sidebar overlay (mobile) -->
    <div id="sidebar-overlay" x-show="$store.shell.open" x-cloak @click="$store.shell.close()"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-30 lg:hidden transition-opacity"
         aria-hidden="true"></div>

    <div class="flex h-screen overflow-hidden">

        <!-- ===== SIDEBAR ===== -->
        <aside id="main-sidebar"
               :class="{ 'collapsed': $store.shell.collapsed, 'translate-x-0!': $store.shell.open }"
               class="fixed lg:static z-40 flex flex-col w-64 h-screen shrink-0
                      -translate-x-full lg:translate-x-0 shadow-2xl shadow-black/30">
            @include('partials.sidebar')
        </aside>

        <!-- ===== MAIN AREA ===== -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

            <!-- Topbar -->
            <header class="sticky top-0 z-20 bg-surface/95 backdrop-blur-md
                           border-b border-border/80 shadow-sm shadow-surface/50 shrink-0">
                @include('partials.navbar')
            </header>

            <!-- Scrollable content -->
            <main id="main-content" class="flex-1 overflow-y-auto">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="shrink-0 bg-surface border-t border-border/80 px-6 py-2.5
                           flex items-center justify-between text-xs text-text-secondary">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span><strong class="font-semibold text-slate-600">Absen Online</strong>
                        &nbsp;v2.3.0 &middot; Ikasmedia</span>
                </div>
                <span class="text-slate-300 font-mono">PHP {{ phpversion() }}</span>
            </footer>
        </div>
    </div>

    @yield('modals')

    <!-- ===== JS ===== -->
    <!-- SweetAlert2 -->
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('assets/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- Moment (moment-with-locales sudah memuat moment core) -->
    <script src="{{ asset('assets/plugins/moment/moment-with-locales.min.js') }}"></script>
    <!-- Tempusdominus -->
    <script src="{{ asset('assets/plugins/tempus-dominus/tempus-dominus.min.js') }}"></script>
    <!-- Summernote -->
    <script src="{{ asset('assets/plugins/summernote/summernote-lite.min.js') }}"></script>
    <!-- Chart.js -->
    <script src="{{ asset('assets/plugins/chart.js/Chart.min.js') }}"></script>
    <!-- Datetime picker -->
    <script src="{{ asset('assets/plugins/jquery-datetimepicker/jquery.datetimepicker.full.js') }}"></script>
    <!-- TimeAgo -->
    <script src="{{ asset('assets/plugins/jquery-timeago/jquery.timeago.js') }}"></script>
    <!-- Toastr show -->
    <script src="{{ asset('assets/app/js/show.toast.js') }}"></script>

    @yield('scripts')
</body>
</html>
