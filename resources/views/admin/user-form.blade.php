<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            {{ isset($user) ? '✏️ Editar Usuario' : '➕ Nuevo Usuario' }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">

            <a href="{{ route('admin.users') }}" class="inline-flex items-center gap-1 text-sm font-semibold mb-6 hover:underline"
               style="color: #27594B;">
                ← Volver a usuarios
            </a>

            <div class="rounded-2xl bg-white shadow-sm p-6 sm:p-8">
                <form method="POST"
                      action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
                    @csrf
                    @if (isset($user))
                        @method('PATCH')
                    @endif

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold mb-1" style="color: #374151;">Nombre</label>
                            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}"
                                   class="w-full rounded-xl border px-4 py-2.5 text-sm" style="border-color: #E5E7EB;"
                                   required>
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1" style="color: #374151;">Email</label>
                            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}"
                                   class="w-full rounded-xl border px-4 py-2.5 text-sm" style="border-color: #E5E7EB;"
                                   required>
                            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1" style="color: #374151;">
                                Contraseña {{ isset($user) ? '(dejar vacío para mantener)' : '' }}
                            </label>
                            <input type="password" name="password"
                                   class="w-full rounded-xl border px-4 py-2.5 text-sm" style="border-color: #E5E7EB;"
                                   {{ isset($user) ? '' : 'required' }}>
                            @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold mb-1" style="color: #374151;">Rol</label>
                            <select name="role" class="w-full rounded-xl border px-4 py-2.5 text-sm" style="border-color: #E5E7EB;" required>
                                <option value="student" {{ old('role', $user->role ?? '') === 'student' ? 'selected' : '' }}>Estudiante</option>
                                <option value="professor" {{ old('role', $user->role ?? '') === 'professor' ? 'selected' : '' }}>Profesor</option>
                                <option value="admin" {{ old('role', $user->role ?? '') === 'admin' ? 'selected' : '' }}>Administrador</option>
                            </select>
                            @error('role') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit"
                                class="w-full py-3 rounded-xl font-bold text-sm text-white shadow-md hover:shadow-lg transition"
                                style="background: linear-gradient(135deg, #518C4F, #27594B);">
                            {{ isset($user) ? 'Guardar cambios' : 'Crear usuario' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
