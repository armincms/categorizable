<?php

namespace Armincms\Categorizable;
 
use Illuminate\Support\ServiceProvider; 

class CategorizableServiceProvider extends ServiceProvider 
{  
    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    } 
}
