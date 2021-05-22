<?php

namespace Armincms\Categorizable;

use Illuminate\Support\ServiceProvider; 
use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Contracts\Support\DeferrableProvider;  
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova as LaravelNova;

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
        LaravelNova::resources([
            Nova\CategoryConfig::class,
        ]);
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return [ 
            ArtisanStarting::class, 
            ServingNova::class,
        ];
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
        ];
    }
}
