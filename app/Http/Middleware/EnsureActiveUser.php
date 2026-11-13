<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! (bool) User::query()->whereKey($user->getKey())->value('is_active')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Akun Anda sudah dinonaktifkan.'], 401)
                : redirect()->route('login')->with('error', 'Akun Anda sudah dinonaktifkan.');
        }

        return $next($request);
    }
}
