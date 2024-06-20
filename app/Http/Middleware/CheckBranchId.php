<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckBranchId
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->branch_id == 0) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Admin User is not allowed to perform this action.',
                    'errors' => new \stdClass(),
                ], 403)
            );
        }

        return $next($request);
    }
}