<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         $user = $request->user();
     if(!$user|| !$user->isOwner()) {
  return response()->json([["message"=> "Forbidden. Owner access required."], 403], Response::HTTP_FORBIDDEN);
  }

   if (!$user->organization_id) {
        return response()->json(['message' => 'You are not attached to any organization.'], 403);
    }
     return $next($request);
     }
}
