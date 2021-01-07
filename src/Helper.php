<?php

namespace Armincms\Categorizable;
 
use Illuminate\Http\Request; 
use Armincms\Contracts\HasLayout;   
use Armincms\Helpers\{SharedResource, Common};   

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
        return SharedResource::availableResources($request, Contracts\Categorizable::class);
    } 

    /**
     * Get meta data information about all resources for client side consumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public static function resourceInformation(Request $request)
    { 
        return SharedResource::resourceInformation($request, Contracts\Categorizable::class);
    }

    /**
     * Get the categorizable resources available for the layout consumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public static function displayableResources(Request $request)
    {
        return static::availableResources($request)->filter(function($resource) {
            return Common::instanceOf($resource::$model, HasLayout::class);
        });
    } 
}
