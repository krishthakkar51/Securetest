<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\ActiveSession;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    $user = Auth::user();

    ActiveSession::create([
        'user_id' => $user->id,
        'session_id' => $request->session()->getId(),
        'last_activity' => now(),
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);

    return redirect()->intended(route('dashboard', absolute: false));
}

public function heartbeat(Request $request)
{
    $user = Auth::guard('web')->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    ActiveSession::where('session_id', $request->session()->getId())
        ->update([
            'last_activity' => now(),
        ]);

    return response()->json([
        'message' => 'Heartbeat updated'
    ]);
}
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if ($user) {
        ActiveSession::where('user_id', $user->id)->delete();
        }
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
