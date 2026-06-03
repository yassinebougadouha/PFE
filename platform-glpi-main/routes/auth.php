<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
// use App\Http\Controllers\Auth\EmailVerificationNotificationController;
// use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
// use App\Http\Controllers\Auth\RegisteredUserController;
// use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\OtpController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Controllers\Auth\SuperAdmin2FAController;

Route::middleware('guest')->group(function () {
    // Registration with OTP
    Route::get('register', [OtpController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [OtpController::class, 'sendOtp'])->name('otp.send');
    Route::get('verify-otp', [OtpController::class, 'showVerifyForm'])->name('otp.verify.form');
    Route::post('verify-otp', [OtpController::class, 'verifyOtp'])->name('otp.verify');
    Route::get('resend-otp', [OtpController::class, 'resendOtp'])->name('otp.resend');

    Route::get('login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('login', function (LoginRequest $request) {
    $request->authenticate();
    $request->session()->regenerate();

    $user = Auth::user();

    if ($user->role === 'super_admin') {
        // Logout mouwaqat
        Auth::logout();

        // Generate OTP
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        \App\Models\OtpCode::where('email', $user->email)->delete();
        \App\Models\OtpCode::create([
            'email'      => $user->email,
            'code'       => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        session(['2fa_user_id' => $user->id]);

        // Ib3ath OTP email
        try {
            $gmail = app(\App\Services\GmailService::class);
            $html  = view('emails.otp-login', ['name' => $user->name, 'otp' => $code])->render();
            $gmail->send($user->email, '🔐 Code Super Admin - L2T', $html);
        } catch (\Exception $e) {
            \Log::error('2FA error: ' . $e->getMessage());
        }

        return redirect()->route('2fa.form');
    }

    return redirect()->intended('/');
});

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
//    Route::get('verify-email', EmailVerificationPromptController::class)
//        ->name('verification.notice');
//
//    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
//        ->middleware(['signed', 'throttle:6,1'])
//        ->name('verification.verify');
//
//    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
//        ->middleware('throttle:6,1')
//        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');

    Route::post('logout', function (\Illuminate\Http\Request $request) {
        // Kill GLPI session if user has glpi_user_id
        try {
            $user = Auth::user();
            if ($user && $user->glpi_user_id) {
                $glpi = app(\App\Services\GlpiService::class);
                $glpi->killSession();
            }
        } catch (\Exception $e) {
            \Log::warning('GLPI session cleanup on logout failed: ' . $e->getMessage());
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
 Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});
Route::middleware('guest')->group(function () {
    Route::get('admin-verify', [SuperAdmin2FAController::class, 'showForm'])->name('2fa.form');
    Route::post('admin-verify', [SuperAdmin2FAController::class, 'verify'])->name('2fa.verify');
    Route::get('admin-verify/resend', [SuperAdmin2FAController::class, 'resend'])->name('2fa.resend');
});