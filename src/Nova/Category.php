<?php

namespace Armincms\Categorizable\Nova;
 
use Armincms\Nova\Resource ;  
use Illuminate\Http\Request;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Whitecube\NovaFlexibleContent\Flexible;
use Eminiarts\Tabs\Tabs;
use OwenMelbz\RadioField\RadioButton;

abstract class Category extends Resource
{    
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = 'Armincms\\Categorizable\\Category';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name'; 

    /**
     * The columns that should be searched in the translation table.
     *
     * @var array
     */
    public static $searchTranslations = [
        'name'
    ]; 


    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [ 
            $this->resourceField(__("Category"), 'name'),  

            $this->panel(__("Category display settings"), $this->tab(function($tab) use ($request) { 
                foreach ($this->categorizables() as $categorizable) {
                    if($fields = $this->categorizableFields($request, $categorizable)) {
                        $tab->group($categorizable::label(), $fields);
                    }  
                }  
            })->toArray()), 
        ];
    }

    public function categorizableFields(Request $request, $categorizable)
    { 
        return collect(static::screens())->map(function($screenName, $screen) use ($request, $categorizable) { 

            if(! $this->shouldIgnoreScreen($request, $categorizable, $screen)) { 
                $fields =  $this->prepareCategorizableFields(
                    $categorizable, $this->prepareScreenFields($screen, $categorizable::fields($request))
                );

                $toggle = $this->screenToggler($screenName, "{$categorizable::configKey()}.{$screen}", [
                    0 => collect($fields)->map->attribute->filter()
                ]);
                     

                return collect($fields)
                        ->flatten()
                        ->prepend($toggle)
                        ->prepend($this->heading($screenName)->onlyOnDetail())
                        ->each(function($field) use ($categorizable, $screen)  { 
                            $field->canSee(function($request) use ($categorizable, $screen) { 
                                if($request->editing == false) { 
                                    return data_get(
                                        $request->findModelQuery()->first(), "config.{$categorizable::configKey()}.{$screen}"
                                    );
                                }  

                                return true; 
                            });   
                        }); 
            } 

            return $this->prepareCategorizableFields($categorizable, [
                Text::make($screenName, $screen)->fillUsing(function() {
                    return [];
                }),
            ]);
        })->filter()->flatten()->toArray();
    }

    public static function screens()
    {
        return [
            'desktop' => __('Desktop'), 
            'mobile'  => __('Mobile'), 
            'tablet'  => __('Tablet')
        ];
    } 

    public function shouldIgnoreScreen(Request $request, $categorizable, $screen)
    {
        return $request->editing &&
               $request->exists($categorizable::configKey()."_{$screen}") &&
               (int) $request->get($categorizable::configKey()."_{$screen}") == 0;
    } 

    public function prepareCategorizableFields($categorizable, $fields)
    { 
        return $this->configField([
                    $this->jsonField($categorizable::configKey(), $fields)
                ]) 
                ->saveHistory()
                ->hideFromIndex()
                ->toArray();
    }

    public function prepareScreenFields($screen, $fields)
    {
        return [
            $this->jsonField($screen, $fields)
        ];
    }

    public function screenToggler($name, $attribute, $toggles = [])
    {
        return RadioButton::make($name, $attribute)
                    ->options([__("Default"), __("Custom")])
                    ->toggle($toggles)
                    ->default(0)
                    ->marginBetween()
                    ->onlyOnForms()
                    ->fillUsing(function() { })
                    ->resolveUsing(function($value, $categorizable, $attribute) {  
                        return data_get($categorizable->config, $attribute) ? 1 : 0;
                    });
    }

    abstract public function categorizables() : array;
}
