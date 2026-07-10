<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            👥 Gestionar Usuarios
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold hover:underline"
                   style="color: var(--color-primary);">
                    ← Volver al panel
                </a>
                <a href="{{ route('admin.users.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl font-bold text-sm text-white shadow-sm hover:shadow-md transition"
                   style="background: linear-gradient(135deg, var(--color-primary-light), var(--color-primary));">
                    + Nuevo usuario
                </a>
            </div>

            @if (session('success'))
                <div class="rounded-xl px-4 py-3 text-sm font-semibold text-white"
                     style="background: var(--color-primary);">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl px-4 py-3 text-sm font-semibold text-white"
                     style="background: #dc3545;">
                    {{ session('error') }}
                </div>
            @endif

            <div class="solid-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b" style="background: var(--color-glass); border-color: var(--color-border);">
                                <th class="p-4 font-semibold" style="color: var(--color-text);">Nombre</th>
                                <th class="p-4 font-semibold" style="color: var(--color-text);">Email</th>
                                <th class="p-4 font-semibold" style="color: var(--color-text);">Rol</th>
                                <th class="p-4 font-semibold text-right" style="color: var(--color-text);">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-b" style="border-color: var(--color-border);">
                                    <td class="p-4 font-medium" style="color: var(--color-text);">{{ $user->name }}</td>
                                    <td class="p-4" style="color: var(--color-text-secondary);">{{ $user->email }}</td>
                                    <td class="p-4">
                                        @php
                                            $roleColors = [
                                                'student' => ['bg' => 'color-mix(in srgb, var(--color-primary) 15%, transparent)', 'text' => 'var(--color-primary)'],
                                                'professor' => ['bg' => 'color-mix(in srgb, var(--color-warning) 20%, transparent)', 'text' => 'var(--color-warning)'],
                                                'admin' => ['bg' => 'color-mix(in srgb, var(--color-accent) 15%, transparent)', 'text' => 'var(--color-accent)'],
                                            ];
                                            $c = $roleColors[$user->role] ?? $roleColors['student'];
                                        @endphp
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                              style="background: {{ $c['bg'] }}; color: {{ $c['text'] }};">
                                            {{ ucfirst($user->role) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="px-3 py-1.5 rounded-lg text-xs font-bold text-white transition hover:scale-105"
                                               style="background: var(--color-primary);">
                                                Editar
                                            </a>
                                            @if ($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('admin.users.delete', $user) }}"
                                                      onsubmit="return confirm('¿Eliminar a {{ $user->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="px-3 py-1.5 rounded-lg text-xs font-bold text-white transition hover:scale-105"
                                                            style="background: #dc3545;">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
