<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_last_name' => ['required', 'string', 'max:255'],
            'user_cel' => ['required', 'string', 'max:12', 'unique:users,user_cel'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,user_email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'user_name' => $request->name,
            'user_last_name' => $request->user_last_name,
            'user_middle_name' => $request->user_middle_name ?? '',
            'user_cel' => $request->user_cel,
            'user_email' => $request->email,
            'user_password' => Hash::make($request->password),
            'user_status' => 'active',
        ]);

        $studentRole = Role::where('role_name', 'student')->first();
        $user->roles()->attach($studentRole->role_id);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
