<?php

namespace ItsmeLaravel\Itsme\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireItsmeAuth
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures that the authenticated user was authenticated via Itsme.
     * Users authenticated through other methods will be redirected to login.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has itsme_id (authenticated via Itsme)
        if (empty($user->itsme_id)) {
            Auth::logout();

            return redirect()->route('login')
                ->with('error', __('itsme::itsme.errors.itsme_auth_required'));
        }

        return $next($request);
    }
}

