<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Token;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->header('X-API-TOKEN')) {
            throw new ApiException('Unauthorized', 401);
        }

        $token = Token::where('token', $request->header('X-API-TOKEN'))
            ->where('expired_at', '>', Carbon::now())
            ->first();

        if (!$token) {
            throw new ApiException('Unauthorized', 401);
        }

        $request->user = $token->user;

        return $next($request);
    }
}
