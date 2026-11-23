<?php

namespace App\Http\Middleware;

use App\Models\ActiveSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {

            $sessionId = $request->session()->getId();

            $activeSession = ActiveSession::where(
                'session_id',
                $sessionId
            )->first();

            // ActiveSession record nahi mila
            if (!$activeSession) {

                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login');
            }

            // Last heartbeat 60 seconds se purana hai
            if (
                $activeSession->last_activity &&
                $activeSession->last_activity->lessThanOrEqualTo(
                    now()->subSeconds(120)
                )
            ) {

                $activeSession->delete();

                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login');
            }
        }

        return $next($request);
    }
}