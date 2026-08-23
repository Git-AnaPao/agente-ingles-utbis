<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $email = Str::lower(trim($validated['email']));
        $user = User::where('user_email', $email)->first();

        if (! $user
            || ! is_string($user->user_password)
            || ! Hash::check($validated['password'], $user->user_password)
            || $user->user_status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        $token = auth('api')->login($user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json($this->userPayload($this->activeUser()));
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function refresh(): JsonResponse
    {
        try {
            $token = auth('api')->refresh();
            $user = auth('api')->setToken($token)->user();
        } catch (JWTException) {
            return response()->json(['message' => 'Token no valido o fuera del periodo de renovacion.'], 401);
        }

        if (! $user instanceof User || $user->user_status !== 'active') {
            try {
                auth('api')->setToken($token)->logout();
            } catch (JWTException) {
                // The account remains blocked by its persisted status.
            }

            return response()->json(['message' => 'Cuenta no disponible.'], 403);
        }

        return response()->json([
            'token' => $token,
        ]);
    }

    private function activeUser(): User
    {
        $user = auth('api')->user();

        abort_unless($user instanceof User && $user->user_status === 'active', 403, 'Cuenta no disponible.');

        return $user;
    }

    /**
     * @return array{id: string, name: string, email: string, role: string, email_verified: bool}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->user_email,
            'role' => $user->role,
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }
}
