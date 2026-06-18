<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class EnforceTwoFactorAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $enforce2fa = Config::get('vexim.website.admin_enforce_2fa', false);
        
        // Skip Livewire and AJAX requests
        if ($request->is('livewire*') || $request->ajax() || $request->header('X-Livewire')) {
            return $next($request);
        }
        
        // Don't check on profile routes
        if ($request->path() === 'profile' || str_starts_with($request->path(), 'profile/')) {
            return $next($request);
        }
        
        // Obviously don't check on logging out
        if ($request->path() === 'logout') {
            return $next($request);
        }
        
        if ($enforce2fa && $user) {
            $needs2fa = $user->hasRole('system_admin') || $user->hasRole('domain_admin');
            
            if ($needs2fa && is_null($user->getAppAuthenticationSecret())) {
                if (!$request->is('livewire*') && !$request->ajax()) {
                    return redirect()->route('filament.vexim.auth.profile');
                }
            }
        }
        
        return $next($request);
    }
}
