<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatusAndQuota
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account is not active.'], 403);
        }
        
        if ($user->role === 0) {
            return $next($request);
        }

        $apiKey = $user->apiKeys()->first();

        if (!$apiKey || $apiKey->status !== 'active') {
            return response()->json([
                'message' => 'The API key associated with this account is inactive or invalid.',
            ], 403);
        }
        return $next($request);
    }

}
