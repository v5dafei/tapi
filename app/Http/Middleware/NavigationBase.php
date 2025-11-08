<?php

namespace App\Http\Middleware;

use Closure;

class NavigationBase
{

    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @return mixed
     */
    public function handle ( $request, Closure $next ) 
    {
        $routeName     = request()->route()->getName();

        if(!in_array($routeName,config('guest')['navigation'])){
            if(!auth("navigation")->user()) {
                return response()->json(['success'=>false,'msg' => '对不起, 用户未登录!','data'=>[],'code'=>401],401)->send();
            }
        }
        return $next($request);
    }
}
