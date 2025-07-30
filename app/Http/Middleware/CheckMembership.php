<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has is_member field and if it's false
        if (!$user->is_member) {
            // You can customize the response based on your needs:
            
            // Option 1: Redirect to a specific page with error message
            // return redirect()->route('membership.required')
            //     ->with('error', 'You need an active membership to access this page.');
            
            // Option 2: Return a 403 Forbidden response
            abort(403, 'Access denied. Active membership required.');
            
            // Option 3: Redirect to home with flash message
            // return redirect()->route('home')
            //     ->with('warning', 'Please upgrade your membership to access this feature.');
        }

        return $next($request);
    }
}