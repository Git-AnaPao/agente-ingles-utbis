<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    private const ALLOWED_DOMAIN = 'utbispuebla.edu.mx';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'bail',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $domain = Str::afterLast((string) $value, '@');

                    if ($domain !== self::ALLOWED_DOMAIN) {
                        $fail('El correo debe pertenecer al dominio @'.self::ALLOWED_DOMAIN.'.');
                    }

                    $user = $this->user();
                    if ($user->google_id !== null
                        && blank($user->getAuthPassword())
                        && $value !== $user->user_email) {
                        $fail('El correo de una cuenta administrada por Google no puede cambiarse aqui.');
                    }
                },
                Rule::unique(User::class, 'user_email')->ignore($this->user()->user_id, 'user_id'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
