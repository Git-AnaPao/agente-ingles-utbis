<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $students = User::whereHas('roles', fn ($query) => $query->where('role_name', 'student'))->count();
        $professors = User::whereHas('roles', fn ($query) => $query->where('role_name', 'professor'))->count();
        $admins = User::whereHas('roles', fn ($query) => $query->where('role_name', 'admin'))->count();
        $totalUsers = User::count();

        return view('admin.dashboard', compact('students', 'professors', 'admins', 'totalUsers'));
    }

    public function users(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['student', 'professor', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $roleFilter = (string) ($filters['role'] ?? '');
        $statusFilter = (string) ($filters['status'] ?? '');

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('user_name', 'like', "%{$search}%")
                        ->orWhere('user_last_name', 'like', "%{$search}%")
                        ->orWhere('user_middle_name', 'like', "%{$search}%")
                        ->orWhere('user_email', 'like', "%{$search}%")
                        ->orWhere('user_cel', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter !== '', fn ($query) => $query->whereHas(
                'roles',
                fn ($query) => $query->where('role_name', $roleFilter),
            ))
            ->when($statusFilter !== '', fn ($query) => $query->where('user_status', $statusFilter))
            ->orderBy('user_name')
            ->orderBy('user_last_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('roleFilter', 'search', 'statusFilter', 'users'));
    }

    public function createUser(): View
    {
        return view('admin.user-form');
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_last_name' => ['required', 'string', 'max:255'],
            'user_middle_name' => ['nullable', 'string', 'max:255'],
            'user_cel' => ['nullable', 'regex:/^[0-9]{7,12}$/', 'unique:users,user_cel'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,user_email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,professor,admin'],
            'user_status' => ['required', 'in:active,inactive'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'user_name' => trim($validated['user_name']),
                'user_last_name' => trim($validated['user_last_name']),
                'user_middle_name' => trim($validated['user_middle_name'] ?? ''),
                'user_cel' => $validated['user_cel'] ?? null,
                'user_email' => $validated['email'],
                'user_password' => Hash::make($validated['password']),
                'user_status' => $validated['user_status'],
            ]);

            $role = Role::where('role_name', $validated['role'])->firstOrFail();
            $user->roles()->attach($role->role_id);

            return $user;
        });

        return redirect()->route('admin.users')
            ->with('success', "Usuario '{$this->displayName($user)}' creado exitosamente.");
    }

    public function editUser(User $user)
    {
        $user->load('roles');
        $isLastActiveAdmin = $user->user_status === 'active'
            && $user->isAdmin()
            && User::query()
                ->where('user_status', 'active')
                ->whereHas('roles', fn ($query) => $query->where('role_name', 'admin'))
                ->count() <= 1;

        return view('admin.user-form', compact('isLastActiveAdmin', 'user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $wasActive = $user->user_status === 'active';
        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_last_name' => ['required', 'string', 'max:255'],
            'user_middle_name' => ['nullable', 'string', 'max:255'],
            'user_cel' => [
                'nullable',
                'regex:/^[0-9]{7,12}$/',
                Rule::unique('users', 'user_cel')->ignore($user->getKey(), 'user_id'),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'user_email')->ignore($user->getKey(), 'user_id'),
            ],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:student,professor,admin'],
            'user_status' => ['required', 'in:active,inactive'],
        ]);

        $actorId = (string) $request->user()->getKey();
        $updatedUser = DB::transaction(function () use ($actorId, $validated, $user, $wasActive): User {
            $adminRole = Role::where('role_name', 'admin')->lockForUpdate()->firstOrFail();
            $lockedUser = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $targetIsAdmin = $lockedUser->roles()
                ->where('roles.role_id', $adminRole->getKey())
                ->exists();
            $isSelf = (string) $lockedUser->getKey() === $actorId;

            if ($isSelf && $validated['role'] !== 'admin') {
                throw ValidationException::withMessages([
                    'role' => 'No puedes quitar el rol de administrador de tu propia cuenta.',
                ]);
            }

            if ($isSelf && $validated['user_status'] !== 'active') {
                throw ValidationException::withMessages([
                    'user_status' => 'No puedes desactivar tu propia cuenta administrativa.',
                ]);
            }

            if ($targetIsAdmin) {
                $adminCount = $adminRole->users()->count();
                $activeAdminCount = $adminRole->users()
                    ->where('user_status', 'active')
                    ->count();
                $removesFinalAdminRole = $validated['role'] !== 'admin' && $adminCount <= 1;
                $removesLastActiveAdmin = $lockedUser->user_status === 'active'
                    && ($validated['role'] !== 'admin' || $validated['user_status'] !== 'active')
                    && $activeAdminCount <= 1;

                if ($removesFinalAdminRole || $removesLastActiveAdmin) {
                    $field = $validated['role'] !== 'admin' ? 'role' : 'user_status';

                    throw ValidationException::withMessages([
                        $field => 'Debe permanecer al menos un administrador activo.',
                    ]);
                }
            }

            $lockedUser->user_name = trim($validated['user_name']);
            $lockedUser->user_last_name = trim($validated['user_last_name']);
            $lockedUser->user_middle_name = trim($validated['user_middle_name'] ?? '');
            $lockedUser->user_cel = $validated['user_cel'] ?? null;
            $lockedUser->user_status = $validated['user_status'];

            if ($lockedUser->user_email !== $validated['email']) {
                $lockedUser->user_email = $validated['email'];
                $lockedUser->email_verified_at = null;
            }

            if (! empty($validated['password'])) {
                $lockedUser->user_password = Hash::make($validated['password']);
            }

            $lockedUser->save();

            if ($wasActive && $lockedUser->user_status === 'inactive') {
                $lockedUser->forceFill(['remember_token' => null])->save();
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $lockedUser->user_id)
                    ->delete();
            }

            $role = $validated['role'] === 'admin'
                ? $adminRole
                : Role::where('role_name', $validated['role'])->firstOrFail();
            $lockedUser->roles()->sync([$role->role_id]);

            return $lockedUser;
        });

        return redirect()->route('admin.users')
            ->with('success', "Usuario '{$this->displayName($updatedUser)}' actualizado.");
    }

    public function deleteUser(Request $request, User $user)
    {
        if ((string) $user->getKey() === (string) $request->user()->getKey()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $result = DB::transaction(function () use ($user): array {
            $adminRole = Role::where('role_name', 'admin')->lockForUpdate()->firstOrFail();
            $lockedUser = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $displayName = $this->displayName($lockedUser);
            $targetIsAdmin = $lockedUser->roles()
                ->where('roles.role_id', $adminRole->getKey())
                ->exists();

            if ($targetIsAdmin) {
                $isFinalAdmin = $adminRole->users()->count() <= 1;
                $isLastActiveAdmin = $lockedUser->user_status === 'active'
                    && $adminRole->users()->where('user_status', 'active')->count() <= 1;

                if ($isFinalAdmin || $isLastActiveAdmin) {
                    return ['deleted' => false, 'name' => $displayName];
                }
            }

            $lockedUser->roles()->detach();
            $lockedUser->delete();

            return ['deleted' => true, 'name' => $displayName];
        });

        if (! $result['deleted']) {
            return back()->with('error', 'Debe permanecer al menos un administrador activo.');
        }

        return redirect()->route('admin.users')
            ->with('success', "Usuario '{$result['name']}' eliminado.");
    }

    private function displayName(User $user): string
    {
        return trim(implode(' ', array_filter([
            $user->user_name,
            $user->user_last_name,
            $user->user_middle_name,
        ])));
    }
}
