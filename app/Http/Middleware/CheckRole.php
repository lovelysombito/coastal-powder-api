<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
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
        $roles = array_slice(func_get_args(), 2); // ['administrator', 'supervisor', 'user']
        $user = $request->user();

        if ($user && in_array($user->scope, $roles)) {
            return $next($request);
        }

        return response()->json([
            'message' => "You don't have permission to access this resource",
        ], 403);
    }
}
