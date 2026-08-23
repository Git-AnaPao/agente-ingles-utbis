<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        if (blank($request->user()->getAuthPassword())) {
            return back()->with(
                'error',
                'Tu cuenta usa el acceso de Google y no tiene una contraseña local que actualizar.',
            );
        }

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->forceFill([
            'user_password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('status', 'password-updated');
    }
}
