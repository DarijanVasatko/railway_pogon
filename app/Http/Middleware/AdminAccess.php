<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check if user is logged in
        // 2. Check if the user is an admin using your model method
        if (Auth::check() && Auth::user()->isAdmin()) {
            return $next($request);
        }

        // If not an admin, redirect back to home with an error message
        return redirect('/')->with('error', 'Nemate ovlasti za pristup ovoj stranici.');
    }
}