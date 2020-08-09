<?php

namespace Armincms\Categorizable;
 
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider; 

class CategorizableServiceProvider extends ServiceProvider implements DeferrableProvider
{  

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['migrator'];
    }
}
