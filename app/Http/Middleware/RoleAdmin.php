<?php

namespace App\Http\Middleware;

use App\Traits\ApiService;
use Closure;
use Illuminate\Http\Request;

class RoleAdmin
{
    use ApiService;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        if ($user->role === 'user') {
            return $this->responseJson(['message' => 'Your actions are not allowed!'], 401);
        }
        return $next($request);
    }
}
