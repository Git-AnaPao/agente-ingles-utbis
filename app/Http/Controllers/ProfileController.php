<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $details = $request->validate([
            'user_last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'user_middle_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'user_cel' => [
                'sometimes',
                'nullable',
                'regex:/^[0-9]{7,12}$/',
                Rule::unique('users', 'user_cel')->ignore($user->getKey(), 'user_id'),
            ],
        ]);

        $user->user_name = trim($data['name']);

        if ($request->hasAny(['user_last_name', 'user_middle_name'])) {
            $user->user_last_name = trim($details['user_last_name'] ?? '');
            $user->user_middle_name = trim($details['user_middle_name'] ?? '');
        } else {
            // Legacy profile submissions put the full name in one field.
            $user->user_last_name = '';
            $user->user_middle_name = '';
        }

        if (array_key_exists('user_cel', $details)) {
            $user->user_cel = $details['user_cel'] ?: null;
        }

        if ($user->user_email !== $data['email']) {
            $user->user_email = $data['email'];
            $user->email_verified_at = null;
        }
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (blank($user->getAuthPassword())) {
            return back()->withErrors([
                'password' => 'Esta cuenta se administra con Google y no puede eliminarse con una contraseña local.',
            ], 'userDeletion');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $chatStorageKey = 'agente-ingles:chat:'.$user->getKey();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('clear_chat_storage_key', $chatStorageKey);
    }
}
