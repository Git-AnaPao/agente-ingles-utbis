<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    public function dashboard()
    {
        $studentRole = Role::where('role_name', 'student')->first();
        $professorRole = Role::where('role_name', 'professor')->first();
        $adminRole = Role::where('role_name', 'admin')->first();

        $students = User::whereHas('roles', fn($q) => $q->where('user_roles.role_id', $studentRole->role_id))->count();
        $professors = User::whereHas('roles', fn($q) => $q->where('user_roles.role_id', $professorRole->role_id))->count();
        $admins = User::whereHas('roles', fn($q) => $q->where('user_roles.role_id', $adminRole->role_id))->count();
        $totalUsers = User::count();

        return view('admin.dashboard', compact('students', 'professors', 'admins', 'totalUsers'));
    }

    public function users()
    {
        $users = User::with('roles')->orderBy('user_name')->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function createUser()
    {
        return view('admin.user-form');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,user_email'],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', 'in:student,professor,admin'],
        ]);

        $user = User::create([
            'user_name' => $request->name,
            'user_email' => $request->email,
            'user_password' => Hash::make($request->password),
        ]);

        $role = Role::where('role_name', $request->role)->first();
        $user->roles()->attach($role->role_id);

        return redirect()->route('admin.users')
            ->with('success', "Usuario '{$request->name}' creado exitosamente.");
    }

    public function editUser(User $user)
    {
        $user->load('roles');
        return view('admin.user-form', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,user_email,' . $user->user_id . ',user_id'],
            'role' => ['required', 'in:student,professor,admin'],
        ]);

        $user->user_name = $request->name;
        $user->user_email = $request->email;
        $user->save();

        if ($request->filled('password')) {
            $request->validate(['password' => [Rules\Password::defaults()]]);
            $user->user_password = Hash::make($request->password);
            $user->save();
        }

        $role = Role::where('role_name', $request->role)->first();
        $user->roles()->sync([$role->role_id]);

        return redirect()->route('admin.users')
            ->with('success', "Usuario '{$request->name}' actualizado.");
    }

    public function deleteUser(User $user)
    {
        if ($user->user_id === auth()->id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "Usuario '{$user->name}' eliminado.");
    }
}
