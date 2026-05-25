<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckProfileComplete
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Fix: si admin n'a pas encore profile_completed → le marquer true direct
        if ($user && in_array($user->role, ['admin', 'super_admin']) && !$user->profile_completed) {
            $user->update(['profile_completed' => true]);
            $user->profile_completed = true; // refresh in-memory pour le if suivant
        }

        if (
            $user &&
            $user->role === 'admin' &&
            !$user->profile_completed &&
            !$request->routeIs('profile.*') &&
            !$request->routeIs('logout') &&
            !$request->routeIs('password.*')
        ) {
            // Déterminer ce qui manque
            $missingTeams  = empty(trim($user->teams_email ?? ''));
            $missingMobile = empty(trim($user->phone_mobile ?? ''));

            if ($missingTeams && $missingMobile) {
                $msg = '⚠️ Complétez votre profil : téléphone mobile et email Microsoft Teams obligatoires.';
            } elseif ($missingTeams) {
                $msg = '⚠️ Ajoutez votre email Microsoft Teams pour recevoir les notifications.';
            } else {
                $msg = '⚠️ Ajoutez votre numéro de téléphone mobile pour compléter votre profil.';
            }

            return redirect()->route('profile.edit')
                ->with('warning', $msg);
        }

        return $next($request);
    }
}