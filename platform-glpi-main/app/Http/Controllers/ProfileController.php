<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use App\Services\GlpiService;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // ✅ Validation obligatoire pour admin
        if ($user->role === 'admin') {
            $request->validate([
                'phone_mobile' => 'required|string|min:8',
                'teams_email'  => 'required|email',
            ], [
                'phone_mobile.required' => '📱 Le téléphone mobile est obligatoire pour les admins.',
                'teams_email.required'  => '💬 L\'email Microsoft Teams est obligatoire pour les admins.',
            ]);
        }

        $user->fill($request->validated());

        // ── Champs identité personnelle ──────────────────────────────────────
        // Vérification que les colonnes existent (migration peut ne pas être faite)
        $userCols = Schema::getColumnListing('users');

        if (in_array('first_name', $userCols)) {
            $user->first_name = $request->first_name;
            $user->last_name  = $request->last_name;
        }
        if (in_array('birthday', $userCols)) {
            $user->birthday = $request->birthday ?: null;
        }
        if (in_array('gender', $userCols)) {
            $user->gender = $request->gender ?: null;
        }

        // Nom complet : depuis prénom+nom si fournis, sinon champ name direct
        if ($request->filled('first_name') && $request->filled('last_name')) {
            $user->name = trim($request->first_name . ' ' . $request->last_name);
        } elseif ($request->filled('name')) {
            $user->name = $request->name;
        }

        // ── Champs contact ───────────────────────────────────────────────────
        $user->phone             = $request->phone;
        $user->phone_mobile      = $request->phone_mobile;
        $user->whatsapp          = $request->whatsapp;
        $user->teams_email       = $request->teams_email;
        $user->teams_webhook_url = $request->teams_webhook_url ?: null;
        $user->timezone          = $request->timezone ?? 'Africa/Tunis';
        $user->locale            = $request->locale ?? 'fr';

        // ── Photo de profil ──────────────────────────────────────────────────
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = $file->store('avatars', 'public');
            $user->avatar = $path;

            if ($user->glpi_user_id) {
                try {
                    $glpi      = app(GlpiService::class);
                    $glpi->initSession();
                    $fullPath  = storage_path('app/public/' . $path);
                    $docResult = $glpi->uploadDocument($fullPath, $file->getClientOriginalName());
                    $glpiDocId = $docResult['id'] ?? null;
                    if ($glpiDocId) {
                        $glpi->addItem('Document_Item', [
                            'documents_id' => $glpiDocId,
                            'itemtype'     => 'User',
                            'items_id'     => $user->glpi_user_id,
                        ]);
                    }
                    $glpi->killSession();
                } catch (\Exception $e) {
                    \Log::warning('GLPI avatar sync failed: ' . $e->getMessage());
                }
            }
        }

        // ── Profil complété (admin uniquement) ───────────────────────────────
        if ($user->role === 'admin') {
            $hasTeams  = !empty(trim($user->teams_email ?? ''));
            $hasMobile = !empty(trim($user->phone_mobile ?? ''));
            $user->profile_completed = $hasTeams && $hasMobile;
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->locale) {
            session(['locale' => $request->locale]);
            app()->setLocale($request->locale);
        }

        $user->save();

        \App\Models\AuditLog::log('UPDATE PROFILE', 'Profile', "Mise à jour profil: {$user->name}");

        // Rediriger selon le rôle
        if ($user->role === 'client') {
            return Redirect::route('client.settings')->with('status', 'profile-updated');
        }
        return Redirect::back()->with('status', 'profile-updated');
    }

    public function glpiPicture(Request $request)
    {
        $user = $request->user();

        if (!$user->glpi_user_id) {
            return response()->json(['success' => false, 'message' => 'User non synchronisé avec GLPI'], 404);
        }

        try {
            $glpi    = app(GlpiService::class);
            $glpi->initSession();
            $picture = $glpi->getUserPicture($user->glpi_user_id);
            $glpi->killSession();

            if ($picture === null) {
                return response()->json(['success' => false, 'message' => 'Aucune photo GLPI'], 204);
            }

            return response($picture, 200, ['Content-Type' => 'image/jpeg']);

        } catch (\Exception $e) {
            \Log::warning('GLPI getUserPicture failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        \App\Models\AuditLog::log('DELETE', 'Profile', "Suppression compte: {$user->name} ({$user->email})", 'warning');

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function verifyTeamsEmail(Request $request)
    {
        $email = trim($request->input('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['valid' => false, 'message' => 'Format email invalide.']);
        }

        $domain  = substr(strrchr($email, '@'), 1);
        $mxRecords = [];
        $hasMx = getmxrr($domain, $mxRecords);

        if (!$hasMx) {
            return response()->json([
                'valid'   => false,
                'message' => "Domaine \"{$domain}\" invalide - aucun serveur mail trouve.",
            ]);
        }

        $isMicrosoftDomain = collect($mxRecords)->contains(fn($mx) =>
            str_contains(strtolower($mx), 'outlook') ||
            str_contains(strtolower($mx), 'microsoft') ||
            str_contains(strtolower($mx), 'office365')
        );

        if ($isMicrosoftDomain) {
            return response()->json([
                'valid'   => true,
                'message' => "Email Microsoft/Teams confirmé ({$domain}). Les notifications Teams arriveront ici.",
            ]);
        }

        return response()->json([
            'valid'   => true,
            'message' => "Email valide ({$domain}). Note: domaine non-Microsoft, vérifiez que cet email correspond à votre compte Teams.",
        ]);
    }
}