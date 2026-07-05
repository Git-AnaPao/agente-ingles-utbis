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
                   style="color: #27594B;">
                    ← Volver al panel
                </a>
                <a href="{{ route('admin.users.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl font-bold text-sm text-white shadow-sm hover:shadow-md transition"
                   style="background: linear-gradient(135deg, #518C4F, #27594B);">
                    + Nuevo usuario
                </a>
            </div>

            @if (session('success'))
                <div class="rounded-xl px-4 py-3 text-sm font-semibold text-white"
                     style="background: #518C4F;">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl px-4 py-3 text-sm font-semibold text-white"
                     style="background: #dc3545;">
                    {{ session('error') }}
                </div>
            @endif

            <div class="rounded-2xl bg-white shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b" style="background: #F9FAFB; border-color: #E5E7EB;">
                            <th class="p-4 font-semibold" style="color: #374151;">Nombre</th>
                            <th class="p-4 font-semibold" style="color: #374151;">Email</th>
                            <th class="p-4 font-semibold" style="color: #374151;">Rol</th>
                            <th class="p-4 font-semibold text-right" style="color: #374151;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-b" style="border-color: #F3F4F6;">
                                <td class="p-4 font-medium" style="color: #1f2937;">{{ $user->name }}</td>
                                <td class="p-4" style="color: #6B7280;">{{ $user->email }}</td>
                                <td class="p-4">
                                    @php
                                        $roleColors = [
                                            'student' => ['bg' => '#e2e3f1', 'text' => '#3a3a7b'],
                                            'professor' => ['bg' => '#fff3cd', 'text' => '#856404'],
                                            'admin' => ['bg' => '#d4edda', 'text' => '#155724'],
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
                                           style="background: #27594B;">
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

            <div class="mt-4">
                {{ $users->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
