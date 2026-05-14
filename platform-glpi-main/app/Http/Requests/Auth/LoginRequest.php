<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Setting;
use App\Models\Notification;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
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
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // ✅ Notifier les super-admins après échec de connexion répété 3 fois (sécurité)
            $attempts = RateLimiter::attempts($this->throttleKey());
            $maxAttempts = (int) (\App\Models\Setting::get('max_login_attempts', '5'));
            if ($attempts >= 3) {
                \App\Models\Notification::sendToSuperAdmins([
                    'type'  => 'security_failed_login',
                    'icon'  => 'gpp_bad',
                    'color' => 'warning',
                    'title' => "⚠️ {$attempts}/{$maxAttempts} tentatives échouées",
                    'body'  => "Email : {$this->input('email')} — IP : {$this->ip()}",
                    'url' => route('logs'),
                ]);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());//yams7 3adad el mohwlat eli fachlaaa 
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) (\App\Models\Setting::get('max_login_attempts', '5'));
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        // ✅ Notifier les super-admins — tentative de connexion bloquée (sécurité)
        \App\Models\Notification::sendToSuperAdmins([
            'type'  => 'security_lockout',
            'icon'  => 'security',
            'color' => 'danger',
            'title' => "⚠️ Tentative de connexion bloquée",
            'body'  => "Email : {$this->input('email')} — IP : {$this->ip()}",
            'url'   => route('super-admin.logs'),
        ]);

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