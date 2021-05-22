<?php

namespace Armincms\Categorizable\Nova;
  
use Illuminate\Support\Str;   
use Illuminate\Http\Request;   
use Laravel\Nova\{Nova, Panel};
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{Heading, Text, Number, Select, Textarea, BooleanGroup, BelongsTo};
use OptimistDigital\MultiselectField\Multiselect;
use Whitecube\NovaFlexibleContent\Flexible; 
use Zareismail\Fields\Complex; 
use Armincms\Helpers\{SharedResource, Common};  
use Armincms\Contracts\HasLayout;  
use Armincms\Bios\Resource;  

class CategoryConfig extends Resource
{   
    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = null;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Armincms\Categorizable\Models\Category::class; 

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return __("Category");
    }
 
    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return $this->availableResources($request)->map(function($category, $key) use ($request) { 
            return new Panel(__($category::group()), [  

                Select::make(__('Display Layout'), $category::optionKey('layout'))
                    ->options($options = collect($category::newModel()->singleLayouts())->map->label()) 
                    ->rules('required')
                    ->required()
                    ->resolveUsing([$this, 'resolveCallback']), 

                Complex::make(__('Internal Layout'), function() use ($request, $category) {
                    $resources = $this->displayableResources($request, $category::newModel());

                    return $resources->map(function($resource) use ($request, $category) {
                        return $this->internalLayoutField($category, $resource);
                    }); 
                }),  

                Complex::make(__('Display Columns'), function() use ($request, $category) {
                    return collect([
                        'mp' => __('Mobile Portrait'),
                        'ml' => __('Mobile Landsacpe'), 
                        'tp' => __('Tablet Portrait'),
                        'tl' => __('Tablet Landsacpe'), 
                        'dx' => __('Laptop'),
                        'dl' => __('Monitor'), 
                    ])->map(function($labe, $attribute) use ($category) {
                        return Number::make($labe, $category::optionKey("display->{$attribute}"))
                            ->required()
                            ->rules('required')
                            ->resolveUsing([$this, 'resolveCallback']);
                    }); 
                }),  

                Number::make(__('Number of per page'), $category::optionKey("display->per_page"))
                    ->help(__('Number of resource per page.'))
                    ->required()
                    ->rules('required')
                    ->resolveUsing([$this, 'resolveCallback']),

                BooleanGroup::make(__('Display Setting'), $category::optionKey('display->detail'))
                    ->options($options = $category::displayConfigurations($request))
                    ->resolveUsing(function($value, $resource, $attribute) use ($options) {
                        $values = (array) $this->resolveCallback($value, $resource, $attribute);

                        return array_map('boolval', array_merge($options, $values));
                    }), 

                Flexible::make(__('Contents Display Settings'), $category::uriKey())
                    ->preset(\Armincms\Nova\Flexible\Presets\RelatableDisplayFields::class, [
                        'request'   => $request,
                        'interface' => $category::newModel()::resourcesScope(), 
                    ]),

            ]);
            
        })->tap(function($panels) {
            $panels->first()->withToolbar(); 
        })->all();
    } 

    /**
     * Get select field for internal alyout.
     * 
     * @param  string $category 
     * @param  string $resource 
     * @return \Laravel\Nova\Fields\Field           
     */
    public function internalLayoutField($category, $resource)
    { 
        return  Select::make(__($resource::label()), $category::optionKey('layouts->'.$resource::uriKey()))
                    ->options(collect($resource::newModel()->listableLayouts())->map->label())
                    ->displayUsingLabels() 
                    ->rules('required')
                    ->required()
                    ->resolveUsing([$this, 'resolveCallback']);
    } 

    /**
     * Get the categorizable resources.
     * 
     * @param  Request $request 
     * @return \Illuminate\Support\Collection           
     */
    public function availableResources(Request $request)
    {
        return collect(Nova::availableResources($request))->filter(function($resource) {
            return is_subclass_of($resource::newModel(), \Armincms\Categorizable\Category::class);
        })->values();
    }

    /**
     * Get the categorizable resources available for the layout consumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function displayableResources(Request $request, $resource)
    {
        return SharedResource::availableResources($request, $resource::resourcesScope())
                ->filter(function($resource) {
                    return Common::instanceOf($resource::$model, HasLayout::class);
                });
    }  

    /**
     * Get the field resolve value callback.
     * 
     * @param  $value     
     * @param  $resource  
     * @param  $attribute 
     * @return            
     */
    public function resolveCallback($value, $resource, $attribute)
    {
        return $this->optionValue($attribute);
    }

    /**
     * Get the option via the given key.
     * 
     * @param  $key    
     * @param  $default
     * @return         
     */
    public function optionValue($key, $default = null)
    { 
        $key = str_replace('->', '.', $key); 

        return data_get(collect(static::options())->map(function($value) {
            return is_string($value) ? json_decode($value, true) : $value;
        }), $key, $default);
    } 
}
