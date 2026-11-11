<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $ssoService = app(\App\Services\Contracts\SsoServiceInterface::class);
        $ssoResult = $ssoService->authenticate($this->input('email'), $this->input('password'));

        if ($ssoResult['success']) {
            $ssoUserData = $ssoResult['user'] ?? [];
            $email = $ssoUserData['email'] ?? $this->input('email');

            $user = \App\Models\User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $ssoUserData['name'] ?? 'SSO User',
                    'sso_id' => $ssoUserData['sso_id'] ?? null,
                    'role' => $ssoUserData['role'] ?? 'requester',
                    'department' => $ssoUserData['department'] ?? 'Umum',
                    'password' => null,
                ]
            );

            if (isset($ssoUserData['sso_id']) && ! $user->sso_id) {
                $user->update([
                    'sso_id' => $ssoUserData['sso_id'],
                    'department' => $ssoUserData['department'] ?? $user->department,
                ]);
            }

            // Auto-assign supervisor for requesters if not yet assigned
            if ($user->role === 'requester' && ! $user->supervisor_id) {
                $defaultSupervisor = \App\Models\User::where('role', 'supervisor')->first();
                if ($defaultSupervisor) {
                    $user->update(['supervisor_id' => $defaultSupervisor->id]);
                }
            }

            if ($user->is_active === false) {
                throw ValidationException::withMessages([
                    'email' => 'Akun Anda sedang dinonaktifkan oleh Administrator Sistem.',
                ]);
            }

            Auth::login($user, $this->boolean('remember'));
            session(['sso_token' => $ssoResult['token'] ?? null]);
            RateLimiter::clear($this->throttleKey());

            return;
        }

        // Fallback for factory-seeded local users in existing tests
        $existingUser = \App\Models\User::where('email', $this->input('email'))->first();
        if ($existingUser && $existingUser->is_active === false) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda sedang dinonaktifkan oleh Administrator Sistem.',
            ]);
        }

        if (Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::clear($this->throttleKey());
            return;
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => $ssoResult['error'] ?? trans('auth.failed'),
        ]);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
