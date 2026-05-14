<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // ✅ Vérifier si l'inscription est autorisée
        if (\App\Models\Setting::get('allow_registration', '1') !== '1') {
            return redirect()->route('login')
                ->with('error', 'L\'inscription est actuellement désactivée.');
        }
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // ✅ Vérifier si l'inscription est autorisée
        if (\App\Models\Setting::get('allow_registration', '1') !== '1') {
            return redirect()->route('login')
                ->with('error', 'L\'inscription est actuellement désactivée.');
        }

        // ✅ Règles password dynamiques depuis settings
        $minLength = (int) \App\Models\Setting::get('min_password_length', '8');
        $complexity = \App\Models\Setting::get('password_complexity', '0') === '1';

        $passwordRules = ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min($minLength)];
        if ($complexity) {
            $passwordRules[] = \Illuminate\Validation\Rules\Password::min($minLength)
                ->mixedCase()->numbers()->symbols();
        }

        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => $passwordRules,
            'phone'    => ['nullable', 'string', 'max:20', 'regex:/^[0-9\+\s\-\(\)]{8,20}$/'],
        ]);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => 'client',
            'client_type' => 'user', // ✅ nouveau inscrit non classifié
            'phone'       => $request->phone ?: null,
            // phone_verified = false par défaut — vérifié via OTP SMS après inscription
        ]);

        event(new Registered($user));

        // ✅ Notifier les super-admins — nouveau client inscrit
        \App\Models\Notification::sendToSuperAdmins([
            'type'  => 'new_client',
            'icon'  => 'person_add',
            'color' => 'success',
            'title' => "Nouveau client inscrit : {$user->name}",
            'body'  => $user->email,
            'url'   => route('super-admin.clients.show', $user->id),
        ]);

        // ✅ Email verification si activée dans settings
        if (\App\Models\Setting::get('require_email_verification', '0') === '1') {
            $user->sendEmailVerificationNotification();
            return redirect()->route('verification.notice')
                ->with('status', 'Compte créé ! Vérifiez votre email pour activer votre compte.');
        }

        return redirect()->route('login')
            ->with('status', 'Compte créé avec succès. Vous pouvez maintenant vous connecter.');
}
}