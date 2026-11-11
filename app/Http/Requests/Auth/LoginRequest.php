<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\Contracts\SsoServiceInterface;
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

        $ssoService = app(SsoServiceInterface::class);
        $ssoResult = $ssoService->authenticate($this->input('email'), $this->input('password'));

        if ($ssoResult['success']) {
            $ssoUserData = $ssoResult['user'] ?? [];
            $email = $ssoUserData['email'] ?? $this->input('email');
            $ssoId = $ssoUserData['sso_id'] ?? null;
            $driver = (string) config('services.sso.driver');

            if ($driver === 'local') {
                $user = User::where('email', $email)->first();
            } else {
                $user = $ssoId
                    ? User::where('sso_id', $ssoId)->orWhere('email', $email)->first()
                    : User::where('email', $email)->first();

                $user ??= User::create([
                    'email' => $email,
                    'name' => $ssoUserData['name'] ?? 'SSO User',
                    'sso_id' => $ssoId,
                    'role' => $ssoUserData['role'] ?? 'requester',
                    'department' => $ssoUserData['department'] ?? 'Umum',
                    'password' => null,
                ]);
            }

            if (! $user) {
                RateLimiter::hit($this->throttleKey());

                throw ValidationException::withMessages([
                    'email' => 'Akun lokal tidak ditemukan.',
                ]);
            }

            if ($driver !== 'local' && $ssoId && ! $user->sso_id) {
                $user->update([
                    'sso_id' => $ssoId,
                    'department' => $ssoUserData['department'] ?? $user->department,
                ]);
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
