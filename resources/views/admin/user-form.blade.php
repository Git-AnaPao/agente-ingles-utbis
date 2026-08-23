@php
    $editingSelf = isset($user) && (string) $user->getKey() === (string) auth()->id();
    $protectAdminAccess = isset($user) && ($editingSelf || ($isLastActiveAdmin ?? false));
    $selectedRole = $protectAdminAccess ? 'admin' : old('role', $user->role ?? 'student');
    $selectedStatus = $protectAdminAccess ? 'active' : old('user_status', $user->user_status ?? 'active');
@endphp

<x-app-layout :title="isset($user) ? 'Editar Usuario' : 'Nuevo Usuario'">
    <div class="py-8 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-4 animate-fade-up">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl shrink-0 shadow-sm"
                         style="background: color-mix(in srgb, var(--color-primary) 12%, transparent); color: var(--color-primary);">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    </div>
                    <div>
                        <h1 class="font-display font-black text-xl sm:text-2xl leading-tight" style="color: var(--color-text);">
                            {{ isset($user) ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
                        </h1>
                        <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-text-secondary);">
                            Define los datos personales, accesos y permisos en la plataforma.
                        </p>
                    </div>
                </div>

                <a href="{{ route('admin.users') }}" class="btn-secondary text-xs px-3.5 py-2">
                    ← Volver a la lista
                </a>
            </div>

            {{-- Formulario Glass Card --}}
            <div class="glass-card p-6 sm:p-9 border shadow-xl animate-fade-up" style="border-color: var(--color-glass-border);">
                <form method="POST"
                      action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
                    @csrf
                    @if (isset($user))
                        @method('PATCH')
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl p-4 text-xs font-semibold border"
                             style="background: var(--color-error-surface); border-color: var(--color-error-border); color: var(--color-error-text);"
                             role="alert" tabindex="-1">
                            Por favor corrige los campos señalados antes de guardar.
                        </div>
                    @endif

                    <div class="space-y-5">
                        <div>
                            <x-input-label for="user_name" value="Nombre(s)" />
                            <x-text-input id="user_name" name="user_name" type="text"
                                          :value="old('user_name', $user->user_name ?? '')"
                                          :invalid="$errors->has('user_name')"
                                          aria-describedby="user_name-error"
                                          class="mt-1 block w-full rounded-2xl"
                                          required autofocus autocomplete="given-name" maxlength="255" />
                            <x-input-error id="user_name-error" :messages="$errors->get('user_name')" class="mt-1" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="user_last_name" value="Primer Apellido" />
                                <x-text-input id="user_last_name" name="user_last_name" type="text"
                                              :value="old('user_last_name', $user->user_last_name ?? '')"
                                              :invalid="$errors->has('user_last_name')"
                                              aria-describedby="user_last_name-error"
                                              class="mt-1 block w-full rounded-2xl"
                                              required autocomplete="family-name" maxlength="255" />
                                <x-input-error id="user_last_name-error" :messages="$errors->get('user_last_name')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="user_middle_name" value="Segundo Apellido (opcional)" />
                                <x-text-input id="user_middle_name" name="user_middle_name" type="text"
                                              :value="old('user_middle_name', $user->user_middle_name ?? '')"
                                              :invalid="$errors->has('user_middle_name')"
                                              aria-describedby="user_middle_name-error"
                                              class="mt-1 block w-full rounded-2xl"
                                              autocomplete="additional-name" maxlength="255" />
                                <x-input-error id="user_middle_name-error" :messages="$errors->get('user_middle_name')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="email" value="Correo Electrónico Institucional" />
                                <x-text-input id="email" name="email" type="email"
                                              :value="old('email', $user->user_email ?? '')"
                                              :invalid="$errors->has('email')"
                                              aria-describedby="email-error"
                                              class="mt-1 block w-full rounded-2xl"
                                              required autocomplete="email" maxlength="255" />
                                <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="user_cel" value="Teléfono Celular (opcional)" />
                                <x-text-input id="user_cel" name="user_cel" type="tel"
                                              :value="old('user_cel', $user->user_cel ?? '')"
                                              :invalid="$errors->has('user_cel')"
                                              aria-describedby="user_cel-help user_cel-error"
                                              class="mt-1 block w-full rounded-2xl"
                                              inputmode="numeric" autocomplete="tel" maxlength="12" />
                                <p id="user_cel-help" class="form-help text-[11px] text-slate-400 mt-1">Entre 7 y 12 dígitos numéricos.</p>
                                <x-input-error id="user_cel-error" :messages="$errors->get('user_cel')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 border-t pt-4" style="border-color: var(--color-border);">
                            <div>
                                <x-input-label for="password" :value="isset($user) ? 'Nueva Contraseña (opcional)' : 'Contraseña'" />
                                <x-text-input id="password" name="password" type="password"
                                              :invalid="$errors->has('password')"
                                              :aria-describedby="isset($user) ? 'password-help password-requirements password-error' : 'password-requirements password-error'"
                                              class="mt-1 block w-full rounded-2xl"
                                              autocomplete="new-password"
                                              :required="!isset($user)" />
                                @if (isset($user))
                                    <p id="password-help" class="form-help text-[11px] text-slate-400 mt-1">Vacío para conservar la actual.</p>
                                @endif
                                <x-input-error id="password-error" :messages="$errors->get('password')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="password_confirmation" :value="isset($user) ? 'Confirmar Nueva Contraseña' : 'Confirmar Contraseña'" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                                              :invalid="$errors->has('password_confirmation')"
                                              aria-describedby="password_confirmation-error"
                                              class="mt-1 block w-full rounded-2xl"
                                              autocomplete="new-password"
                                              :required="!isset($user)" />
                                <x-input-error id="password_confirmation-error" :messages="$errors->get('password_confirmation')" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 border-t pt-4" style="border-color: var(--color-border);">
                            <div>
                                <x-input-label for="role" value="Rol en la Plataforma" />
                                <select id="role" name="role" class="mt-1 w-full rounded-2xl border px-4 py-2.5 text-sm"
                                     aria-describedby="{{ $protectAdminAccess ? 'role-help role-error' : 'role-error' }}"
                                     @if ($errors->has('role')) aria-invalid="true" @endif
                                     @disabled($protectAdminAccess)
                                     style="border-color: var(--color-control-border); background: var(--color-bg); color: var(--color-text);" required>
                                    <option value="student" @selected($selectedRole === 'student')>Estudiante</option>
                                    <option value="professor" @selected($selectedRole === 'professor')>Profesor</option>
                                    <option value="admin" @selected($selectedRole === 'admin')>Administrador</option>
                                </select>
                                @if ($protectAdminAccess)
                                    <input type="hidden" name="role" value="admin">
                                    <p id="role-help" class="form-help text-[11px] text-slate-400 mt-1">Este rol se conserva para proteger el acceso administrativo.</p>
                                @endif
                                <x-input-error id="role-error" :messages="$errors->get('role')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="user_status" value="Estado de la Cuenta" />
                                <select id="user_status" name="user_status" class="mt-1 w-full rounded-2xl border px-4 py-2.5 text-sm"
                                        aria-describedby="{{ $protectAdminAccess ? 'user-status-help user_status-error' : 'user_status-error' }}"
                                        @if ($errors->has('user_status')) aria-invalid="true" @endif
                                        @disabled($protectAdminAccess)
                                        style="border-color: var(--color-control-border); background: var(--color-bg); color: var(--color-text);" required>
                                    <option value="active" @selected($selectedStatus === 'active')>Activo</option>
                                    <option value="inactive" @selected($selectedStatus === 'inactive')>Inactivo</option>
                                </select>
                                @if ($protectAdminAccess)
                                    <input type="hidden" name="user_status" value="active">
                                    <p id="user-status-help" class="form-help text-[11px] text-slate-400 mt-1">Debe permanecer activo.</p>
                                @endif
                                <x-input-error id="user_status-error" :messages="$errors->get('user_status')" class="mt-1" />
                            </div>
                        </div>

                        <div class="pt-3">
                            <button type="submit"
                                    class="w-full btn-lumina btn-3d-green py-3.5 rounded-2xl font-bold text-sm shadow-md"
                                    data-loading-text="{{ isset($user) ? 'Guardando...' : 'Creando...' }}">
                                {{ isset($user) ? 'Guardar Cambios de Usuario' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
