<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\SmsService;
use App\Models\OtpCode;

class PasswordChangeController extends Controller
{
    public function show()
    {
        $user          = auth()->user();
        $smsConfigured = app(SmsService::class)->isConfigured();

        // Compte auto-créé = must_change_password + pas de numéro vérifié
        $needsPhone = $smsConfigured && (!$user->phone || !$user->phone_verified);

        return view('auth.force-password-change', compact('smsConfigured', 'needsPhone'));
    }

    public function update(Request $request)
    {
        $user          = auth()->user();
        $smsConfigured = app(SmsService::class)->isConfigured();
        $needsPhone    = $smsConfigured && (!$user->phone || !$user->phone_verified);

        // Règles de validation
        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        // Si SMS configuré ET numéro pas encore vérifié → vérifier le code SMS
        if ($needsPhone) {
            $rules['phone']    = ['required', 'string', 'max:20'];
            $rules['sms_code'] = ['required', 'string', 'size:6'];
        }

        $request->validate($rules, [
            'password.required'  => 'Le mot de passe est obligatoire.',
            'password.min'       => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'phone.required'     => 'Le numéro de téléphone est obligatoire.',
            'sms_code.required'  => 'Le code SMS est obligatoire.',
        ]);

        // ── Vérifier le code SMS si requis ────────────────────────────────────
        if ($needsPhone) {
            // Normaliser le numéro (même logique que sendSmsOtpForExisting)
            $normalizedPhone = app(SmsService::class)->normalizePhone($request->phone);

            // Vérifier que le numéro n'est pas pris
            if (\App\Models\User::where('phone', $normalizedPhone)->where('id', '!=', $user->id)->exists()) {
                return back()->withErrors(['phone' => 'Ce numéro est déjà associé à un autre compte.']);
            }

            $otp = OtpCode::where('email', $user->email)
                ->where('type', 'sms')
                ->where('phone', $normalizedPhone)  // ← normalized comme dans la DB
                ->where('code', $request->sms_code)
                ->where('used', false)
                ->first();

            if (!$otp || !$otp->isValid()) {
                return back()->withErrors(['sms_code' => 'Code SMS invalide ou expiré.']);
            }

            $otp->update(['used' => true]);

            // Sauvegarder le numéro vérifié (normalisé)
            $user->phone          = $normalizedPhone;
            $user->phone_verified = true;
        }

        // ── Mettre à jour le mot de passe ─────────────────────────────────────
        $user->password             = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return redirect()->route('dashboard')
            ->with('success', '✅ Mot de passe mis à jour avec succès !');
    }
}