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
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    private const ALLOWED_DOMAIN = 'utbispuebla.edu.mx';

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'user_last_name' => ['required', 'string', 'max:255'],
            'user_middle_name' => ['nullable', 'string', 'max:255'],
            'user_cel' => ['required', 'regex:/^[0-9]{7,12}$/', 'unique:users,user_cel'],
            'email' => [
                'bail',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Str::afterLast((string) $value, '@') !== self::ALLOWED_DOMAIN) {
                        $fail('El correo debe pertenecer al dominio @'.self::ALLOWED_DOMAIN.'.');
                    }
                },
                'unique:users,user_email',
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'user_name' => trim($validated['name']),
            'user_last_name' => trim($validated['user_last_name']),
            'user_middle_name' => trim($validated['user_middle_name'] ?? ''),
            'user_cel' => $validated['user_cel'],
            'user_email' => $validated['email'],
            'user_password' => Hash::make($validated['password']),
            'user_status' => 'active',
        ]);

        $studentRole = Role::firstOrCreate(
            ['role_name' => 'student'],
            ['role_description' => 'Estudiante']
        );
        $user->roles()->attach($studentRole->role_id);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
