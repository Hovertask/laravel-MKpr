<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiEnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user || !$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Your email address is not verified.'
            ], 403);
        }
        return $next($request);
    }
}
