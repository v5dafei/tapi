<?php

namespace App\Http\Middleware;

use App\Services\Context;
use Closure;

class AgentBase
{

    use Context;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $routeName     = request()->route()->getName();
        if ( !in_array($routeName, config('guest')['agent'])) {

            if ( !auth("agent")->user() ) {
                return response()->json([ 'success' => false, 'message' => '对不起,代理未登录', 'data' => [], 'code' => 401 ], 401)->send();
            }
        }
        return $next($request);
    }
}
