<x-app-layout title="Gestión de Usuarios">
    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Header --}}
            <div class="glass-card p-6 sm:p-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border animate-fade-up"
                 style="border-color: var(--color-glass-border);">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl shrink-0 shadow-sm"
                         style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold font-mono uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Directorio General</span>
                        <h1 class="font-display text-2xl sm:text-3xl font-black tracking-tight" style="color: var(--color-text);">
                            Gestión de Usuarios
                        </h1>
                        <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-text-secondary);">Administración de credenciales, roles y permisos institucionales.</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                    <a href="{{ route('admin.dashboard') }}" class="btn-secondary text-xs px-4 py-2.5">
                        ← Volver al panel
                    </a>
                    <a href="{{ route('admin.users.create') }}" class="btn-lumina btn-3d-green text-xs px-5 py-2.5 font-bold shadow-md">
                        + Nuevo Usuario
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-2xl px-5 py-3.5 text-sm font-semibold border shadow-sm"
                     style="background: var(--color-success-surface); border-color: var(--color-success-border); color: var(--color-success-text);"
                     role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl px-5 py-3.5 text-sm font-semibold border shadow-sm"
                     style="background: var(--color-error-surface); border-color: var(--color-error-border); color: var(--color-error-text);"
                     role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Filtros y Búsqueda --}}
            <section class="glass-card p-6 sm:p-7 border shadow-sm animate-fade-up" style="border-color: var(--color-glass-border);" aria-labelledby="user-filters-title">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between mb-4">
                    <div>
                        <h2 id="user-filters-title" class="font-display font-bold text-base" style="color: var(--color-text);">Filtros de Búsqueda</h2>
                        <p class="text-xs font-mono text-slate-400">
                            {{ $users->total() }} {{ $users->total() === 1 ? 'cuenta encontrada' : 'cuentas encontradas' }}.
                        </p>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.users') }}" class="grid gap-3.5 md:grid-cols-[minmax(0,2fr)_1fr_1fr_auto]" role="search">
                    <div>
                        <label for="user-search" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Nombre, correo o teléfono</label>
                        <input id="user-search" name="q" type="search" value="{{ $search }}" maxlength="100"
                               class="w-full rounded-2xl border px-4 py-2.5 text-sm"
                               style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);"
                               placeholder="Escribe para buscar...">
                    </div>
                    <div>
                        <label for="role-filter" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Rol</label>
                        <select id="role-filter" name="role" class="w-full rounded-2xl border px-3.5 py-2.5 text-sm"
                                style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);">
                            <option value="">Todos los roles</option>
                            <option value="student" @selected($roleFilter === 'student')>Estudiante</option>
                            <option value="professor" @selected($roleFilter === 'professor')>Profesor</option>
                            <option value="admin" @selected($roleFilter === 'admin')>Administrador</option>
                        </select>
                    </div>
                    <div>
                        <label for="status-filter" class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">Estado</label>
                        <select id="status-filter" name="status" class="w-full rounded-2xl border px-3.5 py-2.5 text-sm"
                                style="border-color: var(--color-border); background: var(--color-bg); color: var(--color-text);">
                            <option value="">Todos los estados</option>
                            <option value="active" @selected($statusFilter === 'active')>Activo</option>
                            <option value="inactive" @selected($statusFilter === 'inactive')>Inactivo</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn-lumina btn-3d-green min-h-11 px-5 py-2.5 text-xs font-bold" data-loading-text="Filtrando...">Aplicar</button>
                        @if ($search !== '' || $roleFilter !== '' || $statusFilter !== '')
                            <a href="{{ route('admin.users') }}" class="btn-secondary min-h-11 px-4 py-2.5 text-xs font-bold inline-flex items-center">Limpiar</a>
                        @endif
                    </div>
                </form>
            </section>

            {{-- Tabla de Usuarios --}}
            @if ($users->isEmpty())
                <div class="solid-card px-5 py-12 text-center animate-fade-up" role="status">
                    <div class="w-14 h-14 rounded-2xl bg-slate-500/10 flex items-center justify-center mx-auto mb-3 text-2xl text-slate-400">
                        👥
                    </div>
                    <h3 class="font-display text-base font-bold" style="color: var(--color-text);">No hay usuarios que coincidan</h3>
                    <p class="mt-1 text-xs max-w-sm mx-auto" style="color: var(--color-text-secondary);">
                        {{ $search !== '' || $roleFilter !== '' || $statusFilter !== ''
                            ? 'Prueba modificando tus términos de búsqueda o limpiando los filtros aplicados.'
                            : 'Crea el primer usuario haciendo clic en "+ Nuevo usuario".' }}
                    </p>
                </div>
            @else
                <div class="glass-card overflow-hidden border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);">
                    <div class="table-shell">
                        <table class="w-full min-w-[880px] text-sm">
                            <caption class="sr-only">Usuarios registrados y acciones administrativas disponibles</caption>
                            <thead>
                                <tr class="text-left border-b" style="background: var(--color-bg); border-color: var(--color-border);">
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider" style="color: var(--color-text-secondary);">Usuario</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider" style="color: var(--color-text-secondary);">Contacto</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider" style="color: var(--color-text-secondary);">Rol</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider" style="color: var(--color-text-secondary);">Estado</th>
                                    <th scope="col" class="p-4 font-bold text-xs uppercase tracking-wider text-right" style="color: var(--color-text-secondary);">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y" style="border-color: var(--color-border);">
                                @foreach ($users as $user)
                                    @php
                                        $displayName = trim(implode(' ', array_filter([
                                            $user->user_name,
                                            $user->user_last_name,
                                            $user->user_middle_name,
                                        ])));
                                    @endphp
                                    <tr class="hover:bg-slate-500/5 transition">
                                        <th scope="row" class="p-4 text-left font-bold" style="color: var(--color-text);">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs"
                                                     style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);">
                                                    {{ strtoupper(substr($user->user_name, 0, 1)) }}
                                                </div>
                                                <span>{{ $displayName }}</span>
                                            </div>
                                        </th>
                                        <td class="p-4 font-mono text-xs" style="color: var(--color-text-secondary);">
                                            <span class="block font-semibold text-slate-300">{{ $user->user_email }}</span>
                                            <span class="block text-[11px] text-slate-500 mt-0.5">{{ $user->user_cel ?: 'Sin celular' }}</span>
                                        </td>
                                        <td class="p-4">
                                            @php
                                                $roleClasses = [
                                                    'student' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                                    'professor' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                                    'admin' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $roleClasses[$user->role] ?? $roleClasses['student'] }}">
                                                {{ ['student' => 'Estudiante', 'professor' => 'Profesor', 'admin' => 'Administrador'][$user->role] ?? ucfirst($user->role) }}
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $user->user_status === 'active' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20' }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $user->user_status === 'active' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                                <span>{{ $user->user_status === 'active' ? 'Activo' : 'Inactivo' }}</span>
                                            </span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('admin.users.edit', $user) }}"
                                                   class="btn-secondary text-xs px-3 py-1.5 font-bold"
                                                   aria-label="Editar a {{ $displayName }}">
                                                     Editar
                                                </a>
                                                @if ((string) $user->getKey() !== (string) auth()->id())
                                                    <form method="POST" action="{{ route('admin.users.delete', $user) }}"
                                                          data-confirm-message="¿Eliminar permanentemente a {{ $displayName }}?">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="btn-danger text-xs px-3 py-1.5 font-bold"
                                                                data-loading-text="Eliminando..."
                                                                aria-label="Eliminar a {{ $displayName }}">
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-xs font-mono text-slate-500">Sesión activa</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($users->hasPages())
                    <div class="mt-6">
                        {{ $users->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
