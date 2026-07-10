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

<body class="min-h-screen antialiased" style="background-color: var(--color-bg); font-family:Inter,sans-serif;">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="grid w-full max-w-6xl overflow-hidden rounded-[2rem] shadow-2xl md:grid-cols-2 solid-card">
            
            <section class="relative flex flex-col justify-between overflow-hidden p-8 text-white md:p-12" style="background-color:var(--color-primary-dark);">
                <div class="absolute inset-0 opacity-[0.08]" style="background-image:radial-gradient(circle at 20% 20%, white 0 1px, transparent 1px), radial-gradient(circle at 80% 30%, white 0 1px, transparent 1px), radial-gradient(circle at 40% 80%, white 0 1px, transparent 1px); background-size:48px 48px;"></div>

                <div class="relative">
                    <span class="inline-flex rounded-full px-4 py-1 text-[11px] font-semibold uppercase tracking-[0.2em]" style="background-color:rgba(255,255,255,0.14); font-family:Inter,sans-serif;">
                        UTBIS · Agente de IA
                    </span>

                    <div class="mt-8 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl shrink-0" style="background-color:rgba(255,255,255,0.12);">
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

            <section class="flex items-center p-8 md:p-12" style="background: var(--color-card);">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold" style="color: var(--color-primary); font-family:'Plus Jakarta Sans',sans-serif;">
                            Iniciar sesión
                        </h2>
                        <p class="mt-2 text-sm" style="color: var(--color-text-secondary); font-family:Inter,sans-serif;">
                            Ingresa tus credenciales para continuar tu aprendizaje.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="mb-5 rounded-2xl border px-4 py-3 text-sm font-medium"
                             style="border-color: color-mix(in srgb, var(--color-primary) 20%, transparent); background: color-mix(in srgb, var(--color-primary) 8%, transparent); color: var(--color-primary);">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium" style="color: var(--color-primary);">Correo electrónico</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                class="block w-full rounded-2xl border px-4 py-3 text-sm outline-none transition duration-150"
                                style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);"
                                placeholder="tu@correo.com"
                            >
                            @error('email')
                                <p class="mt-2 rounded-xl px-3 py-2 text-xs font-medium"
                                   style="background: color-mix(in srgb, var(--color-warning) 15%, transparent); color: var(--color-accent);">
                                    ⚠️ {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium" style="color: var(--color-primary);">Contraseña</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="block w-full rounded-2xl border px-4 py-3 text-sm outline-none transition duration-150"
                                style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);"
                                placeholder="••••••••"
                            >
                            @error('password')
                                <p class="mt-2 rounded-xl px-3 py-2 text-xs font-medium"
                                   style="background: color-mix(in srgb, var(--color-warning) 15%, transparent); color: var(--color-accent);">
                                    ⚠️ {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between gap-4 text-sm">
                            <label class="flex items-center gap-2" style="color: var(--color-text-secondary);">
                                <input type="checkbox" name="remember" class="h-4 w-4 rounded"
                                       style="accent-color: var(--color-accent);">
                                Recordarme
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="font-medium transition hover:underline"
                                   style="color: var(--color-accent);">
                                    Olvidé mi contraseña
                                </a>
                            @endif
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-2xl px-4 py-3 text-sm font-bold text-white shadow-md transition duration-150 focus:outline-none active:scale-[0.99]"
                            style="background: linear-gradient(135deg, var(--color-accent), #FF6B4A); font-family:'Plus Jakarta Sans',sans-serif;">
                            Entrar
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm" style="color: var(--color-text-secondary);">
                        ¿No tienes cuenta?
                        <a href="{{ route('register') }}" class="font-semibold transition hover:underline"
                           style="color: var(--color-primary);">
                            Crear cuenta
                        </a>
                    </p>
                </div>
            </section>
        </div>
    </main>
</body>
</html>