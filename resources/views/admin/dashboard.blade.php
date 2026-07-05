<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-bold text-xl text-white leading-tight">
            ⚙️ Panel de Administración
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Stats --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-2xl p-5 text-center shadow-sm text-white"
                     style="background: linear-gradient(135deg, #27594B, #518C4F);">
                    <span class="block text-3xl font-bold">{{ $totalUsers }}</span>
                    <span class="text-white/80 text-xs">Usuarios totales</span>
                </div>
                <div class="rounded-2xl p-5 text-center shadow-sm text-white"
                     style="background: linear-gradient(135deg, #518C4F, #F2B950);">
                    <span class="block text-3xl font-bold">{{ $students }}</span>
                    <span class="text-white/80 text-xs">Estudiantes</span>
                </div>
                <div class="rounded-2xl p-5 text-center shadow-sm text-white"
                     style="background: linear-gradient(135deg, #F28729, #F2B950);">
                    <span class="block text-3xl font-bold">{{ $professors }}</span>
                    <span class="text-white/80 text-xs">Profesores</span>
                </div>
                <div class="rounded-2xl p-5 text-center shadow-sm text-white"
                     style="background: linear-gradient(135deg, #F2B950, #27594B);">
                    <span class="block text-3xl font-bold">{{ \App\Models\Role::count() }}</span>
                    <span class="text-white/80 text-xs">Roles</span>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('admin.users') }}"
                   class="rounded-2xl bg-white shadow-sm p-6 flex items-center gap-4 transition hover:shadow-md"
                   style="border-left: 4px solid #518C4F;">
                    <span class="text-3xl">👥</span>
                    <div>
                        <h4 class="font-display font-bold text-base" style="color: #27594B;">Gestionar Usuarios</h4>
                        <p class="text-xs mt-1" style="color: #6B7280;">Crear, editar o eliminar estudiantes, profesores y admins.</p>
                    </div>
                </a>

                <a href="{{ route('levels.index') }}"
                   class="rounded-2xl bg-white shadow-sm p-6 flex items-center gap-4 transition hover:shadow-md"
                   style="border-left: 4px solid #F28729;">
                    <span class="text-3xl">📚</span>
                    <div>
                        <h4 class="font-display font-bold text-base" style="color: #27594B;">Ver Niveles</h4>
                        <p class="text-xs mt-1" style="color: #6B7280;">Explora el mapa de aprendizaje y el progreso.</p>
                    </div>
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
