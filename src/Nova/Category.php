<?php

namespace Armincms\Categorizable\Nova;
  
use Illuminate\Http\Request;
use Laravel\Nova\Panel;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{Text, Select, Textarea, BooleanGroup, BelongsTo};
use OptimistDigital\MultiselectField\Multiselect;
use OwenMelbz\RadioField\RadioButton; 
use Eminiarts\Tabs\Tabs;
use Armincms\Nova\{Resource, Role};  
use Armincms\Fields\Targomaan;
use Armincms\Nova\Fields\Images;
use Armincms\Categorizable\Helper; 
use Zareismail\Fields\Complex;

abstract class Category extends Resource
{    
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Armincms\Categorizable\Category::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';  

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    { 
        return [  
            BelongsTo::make(__('Parent Category'), 'parent', static::class)
                ->withoutTrashed()
                ->nullable(),

            Select::make(__('Publish Status'), 'marked_as')->options([
                    'draft' => __('Draft'),
                    'pending' => __('Pending'),
                    'published' => __('Published'),
                ])
                ->required()
                ->rules('required'),

            Select::make(__('Display Layout'), 'config->layout')
                ->options(layouts('category.single')->map->label())
                ->hideFromIndex(),

            Targomaan::make([
                Text::make(__('Category Name'), 'name'),

                Text::make(__("Url Slug"), 'slug') 
                    ->nullable()
                    ->hideFromIndex()
                    ->help(__("Caution: cleaning the input causes rebuild it. This string used in url address.")), 
            ]), 

            Multiselect::make(__('Available For'), 'config->roles')
                ->options(Role::newModel()->get()->pluck('name', 'id'))
                ->help(__('Restrict to users that have the selected roles.'))
                ->placeholder(__('Select a user role.')), 

            Complex::make(__('Images'), function() {
                return [  
                    Images::make(__('Banner'), 'banner')
                        ->conversionOnPreview('common-thumbnail') 
                        ->conversionOnDetailView('common-thumbnail') 
                        ->conversionOnIndexView('common-thumbnail')
                        ->fullSize(),

                    Images::make(__('Logo'), 'logo')
                        ->conversionOnPreview('common-thumbnail') 
                        ->conversionOnDetailView('common-thumbnail') 
                        ->conversionOnIndexView('common-thumbnail')
                        ->fullSize(),

                    Images::make(__('Application Banner'), 'app_banner')
                        ->conversionOnPreview('common-thumbnail') 
                        ->conversionOnDetailView('common-thumbnail') 
                        ->conversionOnIndexView('common-thumbnail')
                        ->fullSize(),

                    Images::make(__('Application Logo'), 'app_logo')
                        ->conversionOnPreview('common-thumbnail') 
                        ->conversionOnDetailView('common-thumbnail') 
                        ->conversionOnIndexView('common-thumbnail')
                        ->fullSize(), 
                ];
            }),  

            BooleanGroup::make(__('Content Type'), 'config->resources') 
                ->options($resources = Helper::resourceInformation($request)->pluck('label', 'key'))
                ->withMeta(array_merge([
                    'value' => $request->isCreateOrAttachRequest() ? $resources : []
                ])),

            BooleanGroup::make(__('Display Setting'), 'config->display')
                ->options([
                    'name' => __('Display the category name.'),

                    'abstract' => __('Display the category describe.'),

                    'banner' => __('Display the category banner.'),

                    'logo' => __('Display the category logo if possible.'),

                    'subcategories' => __('Include subcategories content.'),

                    'empty_subcategories' => __('Include empty subcategories')
                ]),

            Targomaan::make([ 
                Textarea::make(__('Describe Category'), 'abstract'),
            ]),  

            new Panel(__('Configuration'), [

                

            ]),

            $this->panel(__("Category display settings"), $this->tab(function($tab) use ($request) { 
                foreach ($this->categorizables() as $categorizable) {
                    if($fields = $this->categorizableFields($request, $categorizable)) {
                        $tab->group($categorizable::label(), $fields);
                    }  
                }  
            })->toArray()), 
        ];
    }  

    /**
     * Build an associatable query for the field.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  bool  $withTrashed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableCategories(NovaRequest $request, $query)
    { 
        $categories = with($request->findModelQuery()->with('subCategories')->first(), function($category) { 
            return is_null($category) 
                        ? [] 
                        : $category->flattenSubCategories()->push($category)->map->getKey()->unique()->all();
        }); 

        return $query->whereKeyNot($categories);
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
            'default' => __('Default'), 
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
