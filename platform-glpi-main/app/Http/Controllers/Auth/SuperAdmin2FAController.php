<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdmin2FAController extends Controller
{
    // Show 2FA form
    public function showForm()
    {
        if (!session('2fa_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.2fa-verify');
    }

    // Verify 2FA code
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $userId = session('2fa_user_id');
        if (!$userId) {
            return redirect()->route('login')->withErrors(['code' => 'Session expirée.']);
        }

        $user = User::findOrFail($userId);

        $otp = OtpCode::where('email', $user->email)
            ->where('code', $request->code)
            ->where('used', false)
            ->first();

        if (!$otp || !$otp->isValid()) {
            return back()->withErrors(['code' => 'Code invalide ou expiré.']);
        }

        $otp->update(['used' => true]);
        session()->forget('2fa_user_id');

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::log('LOGIN', 'Auth', "Connexion 2FA Super Admin: {$user->name} ({$user->email})");

        return redirect()->route('super-admin.dashboard');
    }

    // Resend code
    public function resend()
    {
        $userId = session('2fa_user_id');
        if (!$userId) return redirect()->route('login');

        $user = User::findOrFail($userId);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpCode::where('email', $user->email)->delete();
        OtpCode::create([
            'email'      => $user->email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            $gmail = app(\App\Services\GmailService::class);
            $html  = view('emails.otp-login', ['name' => $user->name, 'otp' => $code])->render();
            $gmail->send($user->email, '🔐 Code de connexion Super Admin - L2T', $html);
        } catch (\Exception $e) {
            \Log::error('2FA resend error: ' . $e->getMessage());
        }

        return back()->with('success', 'Nouveau code envoyé !');
    }
}