<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Agente Inglés · UTBIS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen antialiased" style="background-color:#F2F2F2; font-family:Inter,sans-serif;">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] bg-white shadow-2xl md:grid-cols-2">
            
            <section class="relative flex flex-col justify-between overflow-hidden p-8 text-white md:p-12" style="background-color:#27594B;">
                <div class="absolute inset-0 opacity-[0.08]" style="background-image:radial-gradient(circle at 20% 20%, white 0 1px, transparent 1px), radial-gradient(circle at 80% 30%, white 0 1px, transparent 1px), radial-gradient(circle at 40% 80%, white 0 1px, transparent 1px); background-size:48px 48px;"></div>

                <div class="relative">
                    <span class="inline-flex rounded-full px-4 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]" style="background-color:rgba(255,255,255,0.14); font-family:Inter,sans-serif;">
                        UTBIS · Agente de IA
                    </span>

                    <div class="mt-8 flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl" style="background-color:rgba(255,255,255,0.12);">
                            <span class="text-4xl" role="img" aria-label="Búho tutor">🦉</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white/75" style="font-family:Inter,sans-serif;">Asistente de aprendizaje</p>
                            <h1 class="mt-1 text-3xl font-bold leading-tight md:text-5xl" style="font-family:'Plus Jakarta Sans',sans-serif;">
                                Aprende inglés de forma simple, clara y constante.
                            </h1>
                        </div>
                    </div>

                    <p class="mt-6 max-w-lg text-base leading-7 text-white/85" style="font-family:Inter,sans-serif;">
                        Tu tutor virtual te acompaña con lecciones, práctica guiada y feedback amable, adaptado a tu nivel del Marco Común Europeo.
                    </p>
                </div>

                <div class="relative mt-10 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl p-4" style="background-color:rgba(255,255,255,0.10);">
                        <p class="text-xs uppercase tracking-widest text-white/60">Simple</p>
                        <p class="mt-1 text-sm font-semibold">Sin distracciones</p>
                    </div>
                    <div class="rounded-2xl p-4" style="background-color:rgba(255,255,255,0.10);">
                        <p class="text-xs uppercase tracking-widest text-white/60">Adaptable</p>
                        <p class="mt-1 text-sm font-semibold">A1 → C2</p>
                    </div>
                    <div class="rounded-2xl p-4" style="background-color:rgba(255,255,255,0.10);">
                        <p class="text-xs uppercase tracking-widest text-white/60">Seguro</p>
                        <p class="mt-1 text-sm font-semibold">Aprendizaje confiable</p>
                    </div>
                </div>
            </section>

            <section class="flex items-center bg-white p-8 md:p-12">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold" style="color:#27594B; font-family:'Plus Jakarta Sans',sans-serif;">
                            Iniciar sesión
                        </h2>
                        <p class="mt-2 text-sm" style="color:#6B7280; font-family:Inter,sans-serif;">
                            Ingresa tus credenciales para continuar tu aprendizaje.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="mb-5 rounded-2xl border border-[#DDE9DD] px-4 py-3 text-sm font-medium" style="background-color:#EAF5EA; color:#518C4F;">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium" style="color:#27594B;">Correo electrónico</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                class="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition duration-150 placeholder:text-gray-400 focus:border-[#27594B] focus:bg-white focus:ring-4 focus:ring-[#27594B]/10"
                                placeholder="tu@correo.com"
                            >
                            @error('email')
                                <p class="mt-2 rounded-xl bg-[#FFF8E8] px-3 py-2 text-xs font-medium text-[#92670A]">
                                    ⚠️ {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium" style="color:#27594B;">Contraseña</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="block w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 outline-none transition duration-150 placeholder:text-gray-400 focus:border-[#27594B] focus:bg-white focus:ring-4 focus:ring-[#27594B]/10"
                                placeholder="••••••••"
                            >
                            @error('password')
                                <p class="mt-2 rounded-xl bg-[#FFF8E8] px-3 py-2 text-xs font-medium text-[#92670A]">
                                    ⚠️ {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between gap-4 text-sm">
                            <label class="flex items-center gap-2 text-gray-500">
                                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 accent-[#F28729]">
                                Recordarme
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="font-medium text-[#F28729] transition hover:underline">
                                    Olvidé mi contraseña
                                </a>
                            @endif
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-2xl px-4 py-3 text-sm font-bold text-white shadow-md transition duration-150 focus:outline-none focus:ring-4 focus:ring-[#F28729]/30 active:scale-[0.99]"
                            style="background-color:#F28729; font-family:'Plus Jakarta Sans',sans-serif;"
                            onmouseover="this.style.backgroundColor='#d97320'"
                            onmouseout="this.style.backgroundColor='#F28729'"
                        >
                            Entrar
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-gray-500">
                        ¿No tienes cuenta?
                        <a href="{{ route('register') }}" class="font-semibold text-[#27594B] transition hover:underline">
                            Crear cuenta
                        </a>
                    </p>
                </div>
            </section>
        </div>
    </main>
</body>
</html>