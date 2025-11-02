<?php

namespace App\Http\Middleware;

use Closure;

class AdminBase
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
        $routeName     = request()->route()->getName();

        if(!in_array($routeName,config('guest')['admin']))
        {
            if(!auth("admin")->user()) {
                return response()->json(['success'=>false,'msg' => '对不起, 用户未登录!','data'=>[],'code'=>401],401)->send();
            }
        }
        return $next($request);
    }
}
