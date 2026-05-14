<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use App\Models\OtpCode;
use App\Services\GmailService;
use App\Services\GlpiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $user = Auth::user();

        // Check is_active
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            AuditLog::log('LOGIN FAILED', 'Auth', "Tentative connexion compte désactivé: {$user->name} ({$user->email})", 'failed');
            return back()->withErrors(['email' => 'Votre compte est désactivé. Contactez l\'administrateur.']);
        }

        // ✅ 2FA pour admin si activé dans settings
        $twoFaEnabled = \App\Models\Setting::get('two_factor_auth', '0') === '1';
        if ($user->role === 'admin' && $twoFaEnabled) {
            Auth::logout();

            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            \App\Models\OtpCode::where('email', $user->email)->delete();
            \App\Models\OtpCode::create([
                'email'      => $user->email,
                'code'       => $code,
                'expires_at' => now()->addMinutes(10),
            ]);
            session(['2fa_user_id' => $user->id]);

            try {
                $gmail = app(\App\Services\GmailService::class);
                $html  = view('emails.otp-login', ['name' => $user->name, 'otp' => $code])->render();
                $gmail->send($user->email, '🔐 Code de connexion Admin - L2T', $html);
            } catch (\Exception $e) {
                \Log::error('2FA admin email error: ' . $e->getMessage());
            }

            return redirect()->route('2fa.form')
                ->with('success', 'Code de vérification envoyé à votre email.');
        }

        // ✅ 2FA pour super_admin
        if ($user->role === 'super_admin') {
            Auth::logout();

            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            OtpCode::where('email', $user->email)->delete();
            OtpCode::create([
                'email'      => $user->email,
                'code'       => $code,
                'expires_at' => now()->addMinutes(10),
            ]);

            session(['2fa_user_id' => $user->id]);

            try {
                $gmail = app(GmailService::class);
                $html  = view('emails.otp-login', [
                    'name' => $user->name,
                    'otp'  => $code,
                ])->render();
                $gmail->send($user->email, '🔐 Code de connexion Super Admin - L2T', $html);
            } catch (\Exception $e) {
                \Log::error('2FA email error: ' . $e->getMessage());
            }

            return redirect()->route('2fa.form')
                ->with('success', 'Code de vérification envoyé à votre email.');
        }

        $request->session()->regenerate();
        AuditLog::log('LOGIN', 'Auth', "Connexion: {$user->name} ({$user->email}) — Rôle: {$user->role}");

        // ✅ Sync GLPI silencieux au login — lie automatiquement le compte GLPI
        // Ne bloque JAMAIS le login, même si GLPI est down
        if (!$user->glpi_user_id) {
            dispatch(function () use ($user) {
                try {
                    $glpi = app(GlpiService::class);
                    $glpi->silentSyncOnLogin($user);
                } catch (\Exception $e) {
                    \Log::warning('GLPI background sync failed: ' . $e->getMessage());
                }
            })->afterResponse();
        }

        return match($user->role) {
            'admin'   => redirect()->intended(route('admin.dashboard')),
            default   => redirect()->intended(route('client.dashboard')),
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::log('LOGOUT', 'Auth', "Déconnexion: {$user->name} ({$user->email})");
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
