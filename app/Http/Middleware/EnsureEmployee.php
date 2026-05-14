<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployee
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */

public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user || !$user->isEmployee()) {
        return response()->json(['message' => 'Forbidden. Employee access required.'], 403);
    }

    if (!$user->organization_id) {
        return response()->json(['message' => 'You are not attached to any organization.'], 403);
    }

    return $next($request);
}
}
