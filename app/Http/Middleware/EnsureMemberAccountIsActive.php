<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberAccountIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->is_admin) {
            return $next($request);
        }

        if ($user->is_blocked) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Your account is blocked. Please contact support.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $user->is_active) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
