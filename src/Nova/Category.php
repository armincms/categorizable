<?php

namespace Armincms\Categorizable\Nova;
  
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Nova\Panel;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{Heading, Text, Number, Select, Textarea, BooleanGroup, BelongsTo};
use OptimistDigital\MultiselectField\Multiselect;
use Whitecube\NovaFlexibleContent\Flexible;  
use Inspheric\Fields\Url;
use Armincms\Contracts\HasLayout;   
use Armincms\Nova\{Resource, Role};  
use Armincms\Helpers\{SharedResource, Common};  
use Armincms\Taggable\Nova\Fields\Tags;  
use Armincms\Fields\Targomaan;
use Armincms\Nova\Fields\Images; 
use Armincms\Categorizable\Helper;
use Zareismail\Fields\Complex;

abstract class Category extends Resource
{     
    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';  

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Taxonomies';

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    { 
        return [   
            Url::make(__('Category Name'), 'name')
                ->exceptOnForms()
                ->alwaysClickable() 
                ->resolveUsing(function()  {
                    return $this->url();
                })
                ->titleUsing(function($value, $resource) {
                    return $this->name;
                }) 
                ->labelUsing(function($value, $resource) {
                    return $this->name;
                }), 

            $this->when(! $request->isMethod('get'), function() {
                return Text::make(__('Url'), 'name')->fillUsing(function($request, $model) {
                    $model->saved(function($model) {
                        $model->translations()->get()->each(function($model) {
                            $model->update([
                                'url' => urlencode($model->buildUrl(static::newModel()->component()->route())),
                            ]);
                        });
                    });
                });
            }), 

            BelongsTo::make(__('Parent Category'), 'parent', static::class)
                ->withoutTrashed()
                ->nullable()
                ->withMeta([
                    'placeholder' => __('No parent')
                ]),

            Select::make(__('Publish Status'), 'marked_as')->options([
                    'draft' => __('Draft'),
                    'pending' => __('Pending'),
                    'published' => __('Published'),
                ])
                ->required()
                ->rules('required')
                ->withMeta(array_filter([
                    'value' => $request->isCreateOrAttachRequest() ? 'draft' : null
                ])),

            Targomaan::make([
                
                Text::make(__('Category Name'), 'name')
                    ->required()
                    ->rules('required')
                    ->onlyOnForms(),

                Text::make(__('Url Slug'), 'slug') 
                    ->nullable()
                    ->hideFromIndex()
                    ->help(__('Caution: cleaning the input causes rebuild it. This string used in url address.')), 
            ]), 
 
            Tags::make(__('Tags'), 'tags')->hideFromIndex(),

            Complex::make(__('Images'), [$this, 'imageFields']),  

            Targomaan::make([
                Textarea::make(__('Describe Category'), 'abstract'),
            ]), 

            new Panel(__('Advanced'), [  

                Select::make(__('Display Layout'), 'config->layout')
                    ->options($layouts = collect(static::newModel()->singleLayouts())->map->label())
                    ->displayUsingLabels()
                    ->hideFromIndex()
                    ->nullable(), 

                Complex::make(__('Internal Layouts'), function() use ($request) {
                    return $this->displayableResources($request)->map(function($resource) use ($request) {
                        return  Select::make(__($resource::label()), 'config->layouts->'.$resource::uriKey())
                                    ->options($layouts = collect($resource::newModel()->listableLayouts())->map->label())
                                    ->displayUsingLabels()
                                    ->hideFromIndex() 
                                    ->nullable();
                    }); 
                }),  

                Complex::make(__('Display Columns'), function() {
                    return collect([
                        'mp' => __('Mobile Portrait'),
                        'ml' => __('Mobile Landsacpe'), 
                        'tp' => __('Tablet Portrait'),
                        'tl' => __('Tablet Landsacpe'), 
                        'dx' => __('Laptop'),
                        'dl' => __('Monitor'), 
                    ])->map(function($labe, $attribute) {
                        return  Number::make($labe, "config->display->{$attribute}")
                                    ->rules('max:12') 
                                    ->nullable()
                                    ->max(12)
                                    ->min(1);
                    }); 
                }),  

                Number::make(__('Number of per page'), 'config->display->per_page')
                    ->help(__('Number of resource per page.')),

                Multiselect::make(__('Available For'), 'config->roles')
                    ->options(function() {
                        return Role::newModel()->get()->pluck('name', 'id');
                    })
                    ->help(__('Restrict to users that have the selected roles.'))
                    ->placeholder(__('Select a user role.')),   

                BooleanGroup::make(__('Content Type'), 'config->resources') 
                    ->options($resources = SharedResource::resourceInformation($request, static::resourcesScope())->pluck('label', 'key'))
                    ->withMeta(array_filter([
                        'value' => $request->isCreateOrAttachRequest() ? $resources->map(function() {
                            return true;
                        })->all() : null
                    ]))
                    ->required()
                    ->rules([
                        'required', 
                        function($attribute, $value, $fail) {
                            collect(json_decode($value, true))->filter()->isNotEmpty() ||
                            $fail(__('Each category should accept one type of content.'));
                        }
                    ]),

                \OwenMelbz\RadioField\RadioButton::make(__('Display Setting'), 'display_setting')
                    ->options([__('Default'), __('Custom')])
                    ->fillUsing(function(){})
                    ->resolveUsing(function() {
                        return intval(data_get($this->config, 'display.detail'));
                    })
                    ->toggle([
                        ['config->display->detail']
                    ]),

                BooleanGroup::make(__('Display Setting'), 'config->display->detail')
                    ->options($options = static::displayConfigurations($request))
                    ->fillUsing(function($request, $resource, $attribute) {
                        if (intval($request->get('display_setting'))) {
                            $resource->{$attribute} = json_decode($request->get($attribute), true);
                        }
                    })
                    ->nullable(),


                Flexible::make(__('Contents Display Settings'))
                    ->preset(\Armincms\Nova\Flexible\Presets\RelatableDisplayFields::class, [
                        'request'   => $request,
                        'interface' => static::resourcesScope(), 
                    ]),

            ]), 
        ];
    }    

    /**
     * Get the categorizable resources available for the layout consumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Support\Collection
     */
    public function displayableResources(Request $request)
    {
        return SharedResource::availableResources($request, static::resourcesScope())
                ->filter(function($resource) {
                    return Common::instanceOf($resource::$model, HasLayout::class);
                });
    } 

    /**
     * Return`s array of fields to hnalde iamges.
     * 
     * @return array
     */
    public function imageFields()
    {
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
    }

    /**
     * Returnc category display configurations.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request 
     * @return array
     */
    public static function displayConfigurations(Request $request)
    {
        return [
            'name' => __('Display the category name'),

            'abstract' => __('Display the category describe'),

            'banner' => __('Display the category banner'),

            'logo' => __('Display the category logo if possible'),

            'subcategories' => __('Include subcategories content'),

            'empty_subcategories' => __('Include empty subcategories')
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

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey()
    {
        return Str::lower(str_replace('\\', '-', static::class));
    }

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function option($key, $default = null)
    {
        return Configuration::option(static::optionKey($key), $default);
    }

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function optionKey($key)
    {
        return forward_static_call([static::$model, 'optionKey'], $key);
    }
}
