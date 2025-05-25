<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log request details
        \Log::info('Admin middleware check', [
            'user_id' => Auth::id(),
            'user_role' => Auth::user()?->role,
            'is_admin' => Auth::user()?->isAdmin(),
            'auth_check' => Auth::check(),
            'request_path' => $request->path(),
            'request_method' => $request->method(),
            'request_headers' => $request->headers->all()
        ]);

        if (!Auth::check()) {
            \Log::warning('Unauthenticated access attempt to admin route', [
                'path' => $request->path(),
                'method' => $request->method()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Please login.'
            ], 401);
        }

        if (!Auth::user()->isAdmin()) {
            \Log::warning('Non-admin access attempt', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role,
                'path' => $request->path(),
                'method' => $request->method()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        \Log::info('Admin access granted', [
            'user_id' => Auth::id(),
            'user_role' => Auth::user()->role,
            'path' => $request->path(),
            'method' => $request->method()
        ]);

        return $next($request);
    }
} 