<!DOCTYPE html>
<html lang="id" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="{{ \App\Libraries\Theme::metaColor('guru') }}">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Sistem Presensi &amp; Biometrik - Login</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/x-icon">

    <link href="{{ asset('assets/app/css/lucide-icons.css') }}?v={{ @filemtime(public_path() . '/' . 'assets/app/css/lucide-icons.css') }}" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! \App\Libraries\Theme::loginStyleTag() !!}

    <style>
        @keyframes loginMasuk {
            from { opacity: 0; transform: translateY(28px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-masuk { animation: loginMasuk 0.7s cubic-bezier(0.16, 1, 0.3, 1); }

        @keyframes pindaiWajah {
            0% { top: 14%; opacity: 0.25; }
            50% { opacity: 0.9; }
            100% { top: 84%; opacity: 0.25; }
        }

        .pindai-wajah {
            animation: pindaiWajah 2.8s ease-in-out infinite alternate;
        }
    </style>
</head>

<body class="relative min-h-screen overflow-hidden bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 -right-24 h-[420px] w-[420px] rounded-full bg-brand-500/20 blur-[120px]"></div>
        <div class="absolute -bottom-32 -left-24 h-[420px] w-[420px] rounded-full bg-amber-400/20 blur-[120px]"></div>
    </div>

    <main class="relative flex min-h-screen items-center justify-center px-4 py-10">
        <div x-data="loginGuru('{{ url('presensi/auth') }}', '{{ url('presensi') }}', {{ json_encode(session('error')) }})" class="w-full max-w-[430px]">
            <div class="login-masuk relative overflow-hidden rounded-[32px] border border-white/80 bg-white/90 px-7 pt-9 pb-7 shadow-2xl shadow-slate-900/10 backdrop-blur-xl">
                <span class="absolute inset-x-0 top-0 h-1.5 bg-linear-to-r from-brand-600 via-brand-500 to-amber-500" aria-hidden="true"></span>

                {{-- Brand --}}
                <div class="mb-7 flex flex-col items-center text-center">
                    <div class="relative mb-4 flex h-[72px] w-[72px] items-center justify-center rounded-[22px] bg-linear-to-br from-brand-500 to-brand-700 shadow-lg shadow-brand-500/30">
                        <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-11 w-11">
                            <path d="M12 21V15C12 13.3431 13.3431 12 15 12H21" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M43 12H49C50.6569 12 52 13.3431 52 15V21" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M52 43V49C52 50.6569 50.6569 52 49 52H43" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 52H15C13.3431 52 12 50.6569 12 49V43" stroke="#ffffff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="24" cy="27" r="2.8" fill="#ffffff"/>
                            <circle cx="40" cy="27" r="2.8" fill="#ffffff"/>
                            <path d="M32 25V33L29 35" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 41C27 44.5 37 44.5 40 41" stroke="#ffffff" stroke-width="2.8" stroke-linecap="round"/>
                        </svg>
                        <span class="pindai-wajah pointer-events-none absolute inset-x-3 h-[3px] rounded-full bg-white/90 shadow-[0_0_10px_#fff]" aria-hidden="true"></span>
                    </div>

                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Absensi</h1>
                    <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                        <i class="bi bi-mortarboard-fill text-brand-600"></i>
                        {{ $setting->nama_sekolah ?? ($setting->nama_aplikasi ?? 'Presensi Guru') }}
                    </p>
                </div>

                <form @submit.prevent="masuk($event.target)" class="space-y-3.5">
                    @csrf

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <input type="text" name="username" x-model="username" required autofocus autocomplete="username"
                            placeholder="Username"
                            class="h-[52px] w-full rounded-2xl border-[1.5px] border-slate-200 bg-white pr-4 pl-11 text-sm font-medium text-slate-800 outline-hidden transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15">
                    </div>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                            <i class="bi bi-key-fill"></i>
                        </span>
                        <input :type="tampilSandi ? 'text' : 'password'" name="password" x-model="password" required autocomplete="current-password"
                            placeholder="Kata Sandi"
                            class="h-[52px] w-full rounded-2xl border-[1.5px] border-slate-200 bg-white pr-12 pl-11 text-sm font-medium text-slate-800 outline-hidden transition-colors placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15">
                        <button type="button" @click="tampilSandi = !tampilSandi"
                            :aria-label="tampilSandi ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition-colors hover:text-slate-700">
                            <i class="bi" :class="tampilSandi ? 'bi-eye-slash' : 'bi-eye'"></i>
                        </button>
                    </div>

                    <p x-show="galat !== ''" x-cloak x-text="galat"
                        class="flex items-start gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700"></p>

                    <button type="submit" :disabled="memproses"
                        class="mt-1.5 flex h-[52px] w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-brand-500 to-brand-700 text-sm font-extrabold tracking-wide text-white shadow-lg shadow-brand-500/30 transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-500/40 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0">
                        <i class="bi bi-arrow-repeat animate-spin text-base" x-show="memproses" x-cloak></i>
                        <i class="bi bi-box-arrow-in-right text-base" x-show="!memproses"></i>
                        <span x-text="memproses ? 'Memverifikasi...' : 'L O G I N'">L O G I N</span>
                    </button>
                </form>

                @if ($setting->google_login_enabled ?? 1)
                    <div class="my-4 flex items-center gap-3 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                        <span class="h-px flex-1 bg-slate-200"></span> Atau <span class="h-px flex-1 bg-slate-200"></span>
                    </div>

                    <a href="{{ url('presensi/google') }}"
                        class="flex h-12 w-full items-center justify-center gap-2.5 rounded-xl border-[1.5px] border-slate-200 bg-white text-sm font-semibold text-slate-700 no-underline shadow-xs transition-all hover:-translate-y-px hover:border-slate-300 hover:bg-slate-50">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="19" height="19" alt="Google">
                        <span>Masuk dengan Google</span>
                    </a>
                @endif

                <div class="mt-6 flex items-center justify-center gap-2 border-t border-slate-100 pt-4 text-[11px] font-semibold text-slate-400">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span>v2.3.0 &bull; ikasmedia</span>
                </div>
            </div>

            <p class="mt-4 flex items-center justify-center gap-1.5 text-[11px] font-semibold text-slate-400">
                <i class="bi bi-shield-lock-fill text-emerald-500"></i> Koneksi aman &amp; terenkripsi
            </p>
        </div>
    </main>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loginGuru', (urlAuth, urlFallback, pesanAwal) => ({
            username: '',
            password: '',
            tampilSandi: false,
            memproses: false,
            galat: pesanAwal || '',

            masuk(form) {
                if (this.memproses) {
                    return;
                }

                this.memproses = true;
                this.galat = '';

                fetch(urlAuth, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                    },
                })
                    .then((response) => response.json())
                    .then((res) => {
                        if (!res.status) {
                            this.galat = res.message || 'Akses ditolak. Periksa username dan kata sandi.';
                            this.password = '';

                            return;
                        }

                        window.location.href = (res.data && res.data.redirect) ? res.data.redirect : urlFallback;
                    })
                    .catch(() => {
                        this.galat = 'Gagal menghubungi server. Silakan periksa koneksi internet Anda.';
                    })
                    .finally(() => {
                        this.memproses = false;
                    });
            },
        }));
    });
    </script>
</body>

</html>
