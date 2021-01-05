<?php

namespace Armincms\Categorizable\Nova\Fields;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Laravel\Nova\{Panel, Fields\Heading};  
use Armincms\Categorizable\Helper;  
use Armincms\Tab\Tab;   

class RelatableDisplayFields extends Panel
{
	use ConditionallyLoadsAttributes;

	/**
	 * The request instance.
	 * 
	 * @var \Illuminate\Http\Request  $request
	 */
	public $request;

    /**
     * Create a new panel instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $name 
     * @return void
     */	
	public function __construct(Request $request, $name)
	{
		$this->request = $request;

		parent::__construct($name);
	} 

    /**
     * Prepare the given fields.
     *
     * @param  \Closure|array  $fields
     * @return array
     */
    protected function prepareFields($fields)
    { 
    	return $this->filter([
    		Tab::make(static::class, function($tab) {
	    		$this->resources()->each(function($resource) use ($tab) { 
		            $tab->group($resource::label(), (array) $this->relatableResourceFields($resource) ?: [
		                Heading::make(__('No configuration exists'))
		            ]);
		        });
	    	})
	    ]);
    } 

    /**
     * Get the resources available for the given interface.
     *  
     * @return \Illuminate\Support\Collection
     */
    public function resources()
    {
    	return Helper::availableResources($this->request);
    }


    /**
     * Returns the categoryable layout fields.
     *      
     * @param  string  $resource 
     * @return array                 
     */
    public function relatableResourceFields($resource)
    {   
    	if(! method_exists($resource, 'relatableCategoryFields')) return [];

    	return tap(forward_static_call([$resource, 'relatableCategoryFields'], $this->request), function($fields) use ($resource) {
    		collect($fields)->each(function($field) use ($resource) {
    			$field->attribute = 'config->relatable->'.$resource::urikey()."->{$field->attribute}";
    		});
    	}); 
    } 
}