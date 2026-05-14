<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role;

        // Check if user has the required role
        if ($userRole !== $role) {
            // Redirect based on actual role
            return match($userRole) {
                'super_admin' => redirect()->route('super-admin.dashboard')->with('error', 'Access denied'),
                'admin' => redirect()->route('admin.dashboard')->with('error', 'Access denied'),
                default => redirect()->route('client.dashboard')->with('error', 'Access denied'),
            };
        }

        return $next($request);
    }
}
