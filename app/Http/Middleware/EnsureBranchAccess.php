<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        $branch = $request->route('branch');

        if ($branch && (int) $branch->tenant_id !== (int) $user->tenant_id) {
            abort(403, 'Branch does not belong to your tenant.');
        }

        if ($branch && $user->branch_id && (int) $branch->id !== (int) $user->branch_id) {
            abort(403, 'You do not have access to this branch.');
        }

        return $next($request);
    }
}
