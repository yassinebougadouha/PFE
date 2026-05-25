<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\Setting;
use App\Models\Notification;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email    = $this->input('email');
        $password = $this->input('password');

        // ── 1. Authentifier via GLPI API ──────────────────────────────────────
        [$sessionToken, $glpiUserData] = $this->authenticateWithGlpi($email, $password);

        if (!$sessionToken) {
            RateLimiter::hit($this->throttleKey());

            $attempts    = RateLimiter::attempts($this->throttleKey());
            $maxAttempts = (int) (\App\Models\Setting::get('max_login_attempts', '5'));
            if ($attempts >= 3) {
                \App\Models\Notification::sendToSuperAdmins([
                    'type'  => 'security_failed_login',
                    'icon'  => 'gpp_bad',
                    'color' => 'warning',
                    'title' => "⚠️ {$attempts}/{$maxAttempts} tentatives échouées",
                    'body'  => "Email : {$email} — IP : {$this->ip()}",
                    'url'   => route('logs'),
                ]);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // ── 2. Récupérer le rôle GLPI ─────────────────────────────────────────
        $glpiProfile = $this->getGlpiUserProfile($email);

        // ── 3. Sync user en BD ────────────────────────────────────────────────
        $this->syncUserFromGlpi($email, $password, $glpiProfile, $glpiUserData);

        // ── 4. Login ──────────────────────────────────────────────────────────
        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Compte non trouvé.',
            ]);
        }

        Auth::login($user, $this->boolean('remember'));
        $this->syncWithPythonBackend($user);
        RateLimiter::clear($this->throttleKey());
        $this->fetchAndStorePythonToken($user);
    }

    /**
     * Helper: mapper role Laravel → role Python
     */
    protected function toPythonRole(string $role): string
    {
        return match ($role) {
            'super_admin' => 'ADMIN',
            'admin'       => 'AGENT',
            default       => 'CLIENT',
        };
    }

    protected function syncWithPythonBackend(\App\Models\User $user): void
    {
        try {
            $pythonRole = $this->toPythonRole($user->role);

            Http::timeout(5)
                ->withHeaders(['X-Service-Key' => 'change-me-internal-key'])
                ->post(config('services.support_api.base_url') . '/api/v1/internal/sync-laravel-user', [
                    'laravel_user_id' => $user->id,
                    'email'           => $user->email,
                    'role'            => $pythonRole,
                ]);
        } catch (\Exception $e) {
            \Log::warning('Python sync failed: ' . $e->getMessage());
        }
    }

    /**
     * @return array{0: string|null, 1: array|null}
     */
    protected function authenticateWithGlpi(string $email, string $password): array
    {
        try {
            $glpi     = app(\App\Services\GlpiService::class);
            $glpiUser = $glpi->findUserByEmail($email);

            if (!$glpiUser) {
                \Log::info('GLPI auth failed: user not found - ' . $email);
                return [null, null];
            }

            \Log::info('User ' . $email . ' found in GLPI - login OK');
            return ['glpi_user_found', $glpiUser];

        } catch (\Exception $e) {
            \Log::error('GLPI auth exception: ' . $e->getMessage());
            return [null, null];
        }
    }

    protected function syncUserFromGlpi(
        string $email,
        string $password,
        string $glpiProfile,
        ?array $glpiUserData = null
    ): void {
        $role       = $this->mapGlpiProfileToRole($glpiProfile);
        $pythonRole = $this->toPythonRole($role); // ← role_python calculé une fois
        $user       = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            // ── Premier login ─────────────────────────────────────────────────
            if (!$glpiUserData) {
                $glpi         = app(\App\Services\GlpiService::class);
                $glpiUserData = $glpi->findUserByEmail($email);
            }

            $glpiId = $glpiUserData['id'] ?? $glpiUserData[1] ?? null;

            if ($glpiId) {
                $glpi = app(\App\Services\GlpiService::class);
                try {
                    $fullUser = $glpi->getItem('User', (int) $glpiId);
                } catch (\Exception $e) {
                    $fullUser = $glpiUserData;
                }

                $firstname   = $fullUser['firstname'] ?? $glpiUserData[8] ?? '';
                $lastname    = $fullUser['realname']  ?? $glpiUserData[9] ?? '';
                $name        = trim($firstname . ' ' . $lastname) ?: $email;
                $phone       = $fullUser['phone']     ?? null;
                $phoneMobile = $fullUser['mobile']    ?? null;
                $comment     = $fullUser['comment']   ?? null;

                \App\Models\User::create([
                    'name'                 => $name,
                    'first_name'           => $firstname ?: null,
                    'last_name'            => $lastname  ?: null,
                    'email'                => $email,
                    'password'             => bcrypt($password),
                    'role'                 => $role,
                    'role_python'          => $pythonRole, // ✅ zidt
                    'client_type'          => $role === 'client' ? 'client' : null,
                    'glpi_user_id'         => (int) $glpiId,
                    'phone'                => $phone,
                    'phone_mobile'         => $phoneMobile,
                    'is_active'            => true,
                    'must_change_password' => false,
                    'profile_completed'    => true,
                    'hashed_password'      => bcrypt($password),
                ]);
                \Log::info("Created client from GLPI: {$email} (role_python={$pythonRole}, nom={$name})");
            } else {
                \App\Models\User::create([
                    'name'                 => $email,
                    'email'                => $email,
                    'password'             => bcrypt($password),
                    'role'                 => $role,
                    'role_python'          => $pythonRole, // ✅ zidt
                    'client_type'          => $role === 'client' ? 'user' : null,
                    'is_active'            => true,
                    'must_change_password' => false,
                    'hashed_password'      => bcrypt($password),
                ]);
                \Log::info("Created new user (not in GLPI): {$email} (role_python={$pythonRole})");
            }
        } else {
            // ── User existant ─────────────────────────────────────────────────
            $updateData = [
                'password'        => bcrypt($password),
                'hashed_password' => bcrypt($password),
                'role'            => $role,
                'role_python'     => $pythonRole, // ✅ zidt — ytabda automatiquement kol login
            ];

            // ── Lier glpi_user_id si pas encore fait ──────────────────────────
            if (!$user->glpi_user_id) {
                $rawId = $glpiUserData['id'] ?? $glpiUserData[1] ?? null;
                if (!$rawId) {
                    try {
                        $glpi         = app(\App\Services\GlpiService::class);
                        $glpiUserData = $glpi->findUserByEmail($email);
                        $rawId        = $glpiUserData['id'] ?? $glpiUserData[1] ?? null;
                    } catch (\Exception $e) {
                        \Log::warning("GLPI lookup for existing user failed: " . $e->getMessage());
                    }
                }
                if ($rawId) {
                    $updateData['glpi_user_id'] = (int) $rawId;
                    \Log::info("Linked glpi_user_id for existing user: {$email}");
                }
            }

            // ── Sync infos GLPI si profil incomplet (clients seulement) ──────
            if ($role !== 'client') {
                $updateData['profile_completed'] = true;
            }

            $linkedGlpiId = $updateData['glpi_user_id'] ?? $user->glpi_user_id ?? null;
            if ($linkedGlpiId && !$user->profile_completed && $role === 'client') {
                try {
                    $glpi     = app(\App\Services\GlpiService::class);
                    $fullUser = $glpi->getItem('User', (int) $linkedGlpiId);

                    $firstname   = $fullUser['firstname'] ?? '';
                    $lastname    = $fullUser['realname']  ?? '';
                    $nameGlpi    = trim($firstname . ' ' . $lastname) ?: null;
                    $phone       = $fullUser['phone']     ?? null;
                    $phoneMobile = $fullUser['mobile']    ?? null;

                    if ($nameGlpi)    $updateData['name']         = $nameGlpi;
                    if ($firstname)   $updateData['first_name']   = $firstname;
                    if ($lastname)    $updateData['last_name']    = $lastname;
                    if ($phone)       $updateData['phone']        = $phone;
                    if ($phoneMobile) $updateData['phone_mobile'] = $phoneMobile;
                    $updateData['profile_completed'] = true;

                    \Log::info("GLPI info synced for existing user: {$email} (nom={$nameGlpi})");
                } catch (\Exception $e) {
                    \Log::warning("GLPI getItem for existing user failed: " . $e->getMessage());
                }
            }

            $user->update($updateData);
        }
    }

    protected function mapGlpiProfileToRole(string $glpiProfile): string
    {
        return match ($glpiProfile) {
            'super_admin' => 'super_admin',
            'admin', 'hotliner', 'supervisor' => 'admin',
            default => 'client',
        };
    }

    protected function getGlpiUserProfile(string $email): string
    {
        try {
            $glpiUrl  = \App\Models\Setting::get('glpi_url') ?: config('services.glpi.url');
            $appToken = \App\Models\Setting::get('glpi_app_token') ?: config('services.glpi.app_token');
            $glpiUrl  = rtrim((string) $glpiUrl, '/');

            $rawToken  = \App\Models\Setting::get('glpi_user_token');
            $userToken = null;
            if ($rawToken) {
                try { $userToken = decrypt($rawToken); } catch (\Exception $e) {}
            }
            $userToken = $userToken ?: config('services.glpi.user_token');

            $session = Http::withHeaders([
                'App-Token'     => $appToken,
                'Authorization' => 'user_token ' . $userToken,
            ])->get($glpiUrl . '/apirest.php/initSession');

            if (!$session->successful()) return 'client';

            $sessionToken = $session->json('session_token');

            $search = Http::withHeaders([
                'App-Token'     => $appToken,
                'Session-Token' => $sessionToken,
            ])->get($glpiUrl . '/apirest.php/User/', [
                'searchText[name]' => $email,
                'range' => '0-5',
            ]);

            $glpiUserId = null;
            if ($search->successful()) {
                foreach ($search->json() ?? [] as $u) {
                    if (strtolower($u['name'] ?? '') === strtolower($email)) {
                        $glpiUserId = $u['id'];
                        break;
                    }
                }
            }

            $role = 'client';

            if ($glpiUserId) {
                $profiles = Http::withHeaders([
                    'App-Token'     => $appToken,
                    'Session-Token' => $sessionToken,
                ])->get($glpiUrl . '/apirest.php/User/' . $glpiUserId . '/Profile_User');

                if ($profiles->successful()) {
                    foreach ($profiles->json() ?? [] as $p) {
                        $profileId = $p['profiles_id'] ?? null;
                        if (!$profileId) continue;

                        $profileData = Http::withHeaders([
                            'App-Token'     => $appToken,
                            'Session-Token' => $sessionToken,
                        ])->get($glpiUrl . '/apirest.php/Profile/' . $profileId);

                        $profileName = strtolower($profileData->json('name') ?? '');

                        if (str_contains($profileName, 'super')) {
                            $role = 'super_admin';
                        } elseif (in_array($profileName, ['admin', 'hotliner', 'supervisor'])) {
                            $role = 'admin';
                        }
                    }
                }
            }

            Http::withHeaders([
                'App-Token'     => $appToken,
                'Session-Token' => $sessionToken,
            ])->get($glpiUrl . '/apirest.php/killSession');

            return $role;

        } catch (\Exception $e) {
            \Log::error('getGlpiUserProfile failed: ' . $e->getMessage());
            return 'client';
        }
    }

    public function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) (\App\Models\Setting::get('max_login_attempts', '5'));
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        event(new Lockout($this));

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

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    protected function fetchAndStorePythonToken(\App\Models\User $user): void
    {
        try {
            $backendUrl = config('services.support_api.base_url');
            $response   = Http::timeout(5)
                ->withHeaders(['X-Service-Key' => 'change-me-internal-key'])
                ->post(
                    $backendUrl . '/api/v1/internal/laravel-token',
                    ['laravel_user_id' => $user->id]
                );
            if ($response->successful()) {
                session(['python_token' => $response->json('access_token')]);
                \Log::info('Python token stored for user: ' . $user->email);
            } else {
                \Log::warning('Python token failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            \Log::warning('Python token fetch failed: ' . $e->getMessage());
        }
    }
}