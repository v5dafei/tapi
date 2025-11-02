<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = 'App\Http\Controllers';
    public const HOME = '/home';
    public function boot()
    {
        parent::boot();
    }
    public function map()
    {
        $this->mapCarrierRoutes();
        $this->mapAdminRoutes();
        $this->mapWebRoutes();
        $this->mapAgentRoutes();
    }

    protected function mapCarrierRoutes()
    {
        Route::prefix('carrier')
             ->middleware('carrier')
             ->namespace($this->namespace)
             ->group(base_path('routes/carrier.php'));
    }

    protected function mapAdminRoutes()
    {
        Route::prefix('admin')
             ->middleware('admin')
             ->namespace($this->namespace)
             ->group(base_path('routes/admin.php'));
    }

    protected function mapWebRoutes()
    {
        Route::prefix('api')
             ->middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    protected function mapAgentRoutes()
    {
        Route::prefix('agent')
             ->middleware('agent')
             ->namespace($this->namespace)
             ->group(base_path('routes/agent.php'));
    }
}
