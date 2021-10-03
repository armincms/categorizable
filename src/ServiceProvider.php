<?php

namespace Armincms\Categorizable;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;   
use Laravel\Nova\Nova as LaravelNova;
use Zareismail\Gutenberg\Gutenberg;

class ServiceProvider extends AuthServiceProvider implements DeferrableProvider
{ 
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Models\Category::class => Policies\Category::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations'); 
        $this->registerPolicies();
        $this->conversions();
        $this->resources(); 
        $this->fragments(); 
        $this->templates();
        $this->menus();
    }

    /**
     * Set media conversions for resources.
     * 
     * @return 
     */
    protected function conversions()
    {
        $this->app->afterResolving('conversion', function($manager) {
            $manager->extend('category-image', function() {
                return new \Armincms\Conversion\CommonConversion;
            });
            $manager->extend('category-logo', function() {
                return new \Armincms\Conversion\CommonConversion;
            });
            $manager->extend('application-image', function() {
                return new \Armincms\Conversion\CommonConversion;
            });
            $manager->extend('application-logo', function() {
                return new \Armincms\Conversion\CommonConversion;
            });
        });
    }

    /**
     * Register the application's Nova resources.
     *
     * @return void
     */
    protected function resources()
    { 
        LaravelNova::resources([
            Nova\Category::class, 
        ]);
    } 

    /**
     * Register the application's Gutenberg fragments.
     *
     * @return void
     */
    protected function fragments()
    {   
        Gutenberg::fragments([
            Cypress\Fragments\Category::class, 
        ]);
    } 

    /**
     * Register the application's Gutenberg templates.
     *
     * @return void
     */
    protected function templates()
    {   
        Gutenberg::templates([
            \Armincms\Categorizable\Gutenberg\Templates\SingleCategory::class, 
        ]); 
    }

    /**
     * Register the application's menus.
     *
     * @return void
     */
    protected function menus()
    {    
        $this->app->booted(function() {  
            $menus = array_unique(array_merge((array) config('nova-menu.menu_item_types'), [
                Menus\Category::class, 
            ]));

            app('config')->set('nova-menu.menu_item_types', $menus);  
        }); 
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    } 

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return [
            \Illuminate\Console\Events\ArtisanStarting::class,
            \Laravel\Nova\Events\ServingNova::class,
        ];
    } 
}
