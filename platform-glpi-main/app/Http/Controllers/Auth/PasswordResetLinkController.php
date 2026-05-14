<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GlpiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->role === 'admin') {
            return back()->withErrors([
                'email' => 'Password reset is disabled for admin.',
            ]);
        }

        // 1. Reset Laravel normal
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // 2. ✅ API GLPI: PUT /apirest.php/lostPassword
        if ($status === Password::RESET_LINK_SENT) {
            try {
                app(GlpiService::class)->requestPasswordReset($request->email);
            } catch (\Exception $e) {
                \Log::warning('GLPI requestPasswordReset failed: ' . $e->getMessage());
            }
        }

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}