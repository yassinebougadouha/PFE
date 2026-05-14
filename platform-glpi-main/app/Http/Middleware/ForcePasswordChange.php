<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (
            $user &&
            $user->must_change_password &&
            !$request->routeIs('password.change') &&
            !$request->routeIs('password.change.update') &&
            !$request->routeIs('logout')
        ) {
            return redirect()->route('password.change')
                ->with('warning', '⚠️ Vous devez changer votre mot de passe avant de continuer.');
        }

        return $next($request);
    }
}