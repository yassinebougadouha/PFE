<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Services\SmsService;

class OtpController extends Controller
{
    // ─── Step 1: Afficher formulaire inscription ───────────────────────────────
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // ─── Step 2: Valider données + envoyer OTP email + OTP SMS ────────────────
    public function sendOtp(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'birthday'   => ['required', 'date', 'before:-13 years'],
            'gender'     => ['required', 'in:male,female,other'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\s\-\(\)]{8,20}$/'],
            'password'   => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required'  => 'Le nom est obligatoire.',
            'birthday.required'   => 'La date de naissance est obligatoire.',
            'birthday.before'     => 'Vous devez avoir au moins 13 ans.',
            'gender.required'     => 'Veuillez sélectionner votre genre.',
            'phone.regex'         => 'Numéro de téléphone invalide (ex: 98765432).',
        ]);

        // Vérifier doublon numéro si renseigné
        if ($request->filled('phone') && User::where('phone', $request->phone)->exists()) {
            return back()->withErrors(['phone' => 'Ce numéro est déjà associé à un compte.'])->withInput();
        }

        $fullName = trim($request->first_name . ' ' . $request->last_name);

        session([
            'otp_register' => [
                'name'       => $fullName,
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'birthday'   => $request->birthday,
                'gender'     => $request->gender,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'password'   => $request->password,
            ]
        ]);

        // ── OTP Email ─────────────────────────────────────────────────────────
        $emailCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpCode::where('email', $request->email)->delete();
        OtpCode::create([
            'email'      => $request->email,
            'type'       => 'email',
            'code'       => $emailCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            $gmail = app(GmailService::class);
            $html  = view('emails.otp', ['name' => $request->name, 'otp' => $emailCode])->render();
            $gmail->send($request->email, 'Votre code de vérification - L2T Support', $html);
        } catch (\Exception $e) {
            \Log::error('OTP email send failed: ' . $e->getMessage());
        }

        // ── OTP SMS (si numéro fourni) ───────────────────────────────────────────────────────
        if ($request->filled('phone')) {
            $sms     = app(SmsService::class);
            $phone   = $sms->normalizePhone($request->phone);
            $smsCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            OtpCode::where('email', $request->email)->where('type', 'sms')->delete();
            OtpCode::create([
                'email'      => $request->email,
                'type'       => 'sms',
                'phone'      => $phone,
                'code'       => $smsCode,
                'expires_at' => now()->addMinutes(10),
            ]);

            try {
                $sms->sendOtp($phone, $smsCode, 'L2T Support');
                \Log::info("OTP SMS sent to {$phone} for registration");
            } catch (\Exception $e) {
                \Log::error('OTP SMS send failed: ' . $e->getMessage());
            }
        }

        $successMsg = "Code envoyé à {$request->email}";
        if ($request->filled('phone')) {
            $successMsg .= ' et par SMS';
        }

        return redirect()->route('otp.verify.form')
            ->with('success', $successMsg);
    }

    // ─── Step 3: Afficher formulaire vérification double OTP ──────────────────
    public function showVerifyForm()
    {
        if (!session('otp_register')) {
            return redirect()->route('register');
        }
        return view('auth.otp-verify');
    }

    // ─── Step 4: Vérifier email OTP + SMS OTP → créer compte ──────────────────
    public function verifyOtp(Request $request)
    {
        $registerData = session('otp_register');
        if (!$registerData) {
            return redirect()->route('register')->withErrors(['email_code' => 'Session expirée, recommencez.']);
        }

        $hasSmsOtp = !empty($registerData['phone'])
            && OtpCode::where('email', $registerData['email'])->where('type', 'sms')->exists();

        $rules = ['email_code' => ['required', 'string', 'size:6']];
        if ($hasSmsOtp) {
            $rules['sms_code'] = ['required', 'string', 'size:6'];
        }
        $request->validate($rules, [
            'email_code.required' => 'Code email obligatoire.',
            'sms_code.required'   => 'Code SMS obligatoire.',
        ]);

        // ── Vérifier OTP Email ─────────────────────────────────────────────────
        $emailOtp = OtpCode::where('email', $registerData['email'])
            ->where('type', 'email')
            ->where('code', $request->email_code)
            ->where('used', false)
            ->first();

        if (!$emailOtp || !$emailOtp->isValid()) {
            return back()->withErrors(['email_code' => 'Code email invalide ou expiré.']);
        }

        // ── Vérifier OTP SMS (si applicable) ──────────────────────────────────
        if ($hasSmsOtp) {
            $smsOtp = OtpCode::where('email', $registerData['email'])
                ->where('type', 'sms')
                ->where('code', $request->sms_code)
                ->where('used', false)
                ->first();

            if (!$smsOtp || !$smsOtp->isValid()) {
                return back()->withErrors(['sms_code' => 'Code SMS invalide ou expiré.']);
            }
            $smsOtp->update(['used' => true]);
        }

        $emailOtp->update(['used' => true]);

        // ── Créer le compte ────────────────────────────────────────────────────
        $user = User::create([
            'name'           => $registerData['name'],
            'first_name'     => $registerData['first_name'] ?? null,
            'last_name'      => $registerData['last_name'] ?? null,
            'birthday'       => $registerData['birthday'] ?? null,
            'gender'         => $registerData['gender'] ?? null,
            'email'          => $registerData['email'],
            'phone'          => $registerData['phone'] ?? null,
            'password'       => Hash::make($registerData['password']),
            'role'           => 'client',
            'client_type'    => 'user',
            'is_active'      => true,
            'phone_verified' => $hasSmsOtp,
        ]);

        session()->forget('otp_register');
        Auth::login($user);

        return redirect()->route('client.dashboard')
            ->with('success', 'Compte créé avec succès ! Bienvenue 👋');
    }


    // ─── Renvoyer les deux OTPs ────────────────────────────────────────────────
    public function resendOtp()
    {
        $registerData = session('otp_register');
        if (!$registerData) {
            return redirect()->route('register');
        }

        // Nouveau OTP email
        $emailCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpCode::where('email', $registerData['email'])->where('type', 'email')->delete();
        OtpCode::create([
            'email'      => $registerData['email'],
            'type'       => 'email',
            'code'       => $emailCode,
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            $gmail = app(GmailService::class);
            $html  = view('emails.otp', ['name' => $registerData['name'], 'otp' => $emailCode])->render();
            $gmail->send($registerData['email'], 'Votre code de vérification - L2T Support', $html);
        } catch (\Exception $e) {
            \Log::error('OTP resend email failed: ' . $e->getMessage());
        }

        // Renvoyer SMS si numéro existe
        if (!empty($registerData['phone'])) {
            $sms     = app(SmsService::class);
            $phone   = $sms->normalizePhone($registerData['phone']);
            $smsCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            OtpCode::where('email', $registerData['email'])->where('type', 'sms')->delete();
            OtpCode::create([
                'email'      => $registerData['email'],
                'type'       => 'sms',
                'phone'      => $phone,
                'code'       => $smsCode,
                'expires_at' => now()->addMinutes(10),
            ]);

            try {
                $sms->sendOtp($phone, $smsCode, 'L2T Support');
            } catch (\Exception $e) {
                \Log::error('OTP SMS resend failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Nouveaux codes envoyés !');
    }

    // ─── Vérification SMS pour compte auto-créé (force-change-password) ────────
    public function sendSmsOtpForExisting(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9\+\s\-\(\)]{8,20}$/'],
        ], [
            'phone.required' => 'Le numéro est obligatoire.',
            'phone.regex'    => 'Numéro invalide.',
        ]);

        $user = auth()->user();

        // Vérifier que le numéro n'est pas pris par quelqu'un d'autre
        if (User::where('phone', $request->phone)->where('id', '!=', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Ce numéro est déjà utilisé.'], 422);
        }

        $sms = app(SmsService::class);
        if (!$sms->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'SMS non configuré. Contactez l\'administrateur.'], 500);
        }

        $code  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$phone = $sms->normalizePhone($request->phone); // ← هنا أولاً

OtpCode::where('email', $user->email)->where('type', 'sms')->delete();
OtpCode::create([
    'email'      => $user->email,
    'type'       => 'sms',
    'phone'      => $phone,         // ← $phone normalized
    'code'       => $code,
    'expires_at' => now()->addMinutes(10),
]);

$sent = $sms->sendOtp($phone, $code, $user->name);

if ($sent) {
    session(['pending_phone' => $phone]);
    return response()->json(['success' => true, 'message' => "Code envoyé au {$phone}"]);
}

return response()->json(['success' => false, 'message' => 'Échec envoi SMS. Réessayez.'], 500);
}  

    // ─── Vérifier OTP SMS pour compte existant ────────────────────────────────
    public function verifySmsOtpForExisting(Request $request)
    {
        $request->validate(['sms_code' => ['required', 'string', 'size:6']]);

        $user  = auth()->user();
        $phone = session('pending_phone');

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Session expirée.'], 422);
        }

        $otp = OtpCode::where('email', $user->email)
            ->where('type', 'sms')
            ->where('phone', $phone)
            ->where('code', $request->sms_code)
            ->where('used', false)
            ->first();

        if (!$otp || !$otp->isValid()) {
            return response()->json(['success' => false, 'message' => 'Code invalide ou expiré.'], 422);
        }

        $otp->update(['used' => true]);
        $user->update(['phone' => $phone, 'phone_verified' => true]);
        session()->forget('pending_phone');

        return response()->json(['success' => true, 'message' => 'Numéro vérifié ✅']);
    }
}