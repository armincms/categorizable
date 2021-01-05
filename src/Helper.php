<?php

namespace Armincms\Categorizable;
 
use Illuminate\Http\Request;
use Laravel\Nova\Nova;


class Helper
{   
    /**
     * Get the categorizable resources available for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public static function availableResources(Request $request)
    {
        return collect(Nova::availableResources($request))->filter(function($resource) {
            return collect(class_implements($resource::$model))->contains(Contracts\Categorizable::class);
        });
    } 

    /**
     * Get meta data information about all resources for client side consumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public static function resourceInformation(Request $request)
    {
        return static::availableResources($request)->map(function($resource) {
            return [
                'label' => $resource::label(),
                'key'   => $resource::uriKey(), 
                'model' => $resource::$model, 
            ];
        });
    }
}
