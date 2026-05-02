<?php

namespace App\Http\Middleware;

use App\Domains\Licensing\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasRole('Super Admin')) {
            abort_if($user->active === false, 403, 'Your user account is inactive.');

            if ($user->tenant) {
                app(LicenseService::class)->assertOperational($user->tenant);
            }
        }

        return $next($request);
    }
}
