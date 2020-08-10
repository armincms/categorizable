<?php

namespace Armincms\Categorizable;
 
use Illuminate\Support\ServiceProvider; 

class CategorizableServiceProvider extends ServiceProvider 
{    
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    } 
}
