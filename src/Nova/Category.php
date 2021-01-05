<?php

namespace Armincms\Categorizable\Nova;
  
use Illuminate\Http\Request;
use Laravel\Nova\Panel;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Fields\{Heading, Text, Select, Textarea, BooleanGroup, BelongsTo};
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
                ->rules('required'),

            Select::make(__('Display Layout'), 'config->layout')
                ->options(layouts('category.single')->map->label())
                ->displayUsingLabels()
                ->hideFromIndex()
                ->required()
                ->rules('required'),

            Targomaan::make([
                Text::make(__('Category Name'), 'name'),

                Text::make(__("Url Slug"), 'slug') 
                    ->nullable()
                    ->hideFromIndex()
                    ->help(__("Caution: cleaning the input causes rebuild it. This string used in url address.")), 
            ]), 

            Multiselect::make(__('Available For'), 'config->roles')
                ->options(function() {
                    return Role::newModel()->get()->pluck('name', 'id');
                })
                ->help(__('Restrict to users that have the selected roles.'))
                ->placeholder(__('Select a user role.')), 

            Complex::make(__('Images'), [$this, 'imageFields']),  

            BooleanGroup::make(__('Content Type'), 'config->resources') 
                ->options($resources = Helper::resourceInformation($request)->pluck('label', 'key'))
                ->withMeta(array_merge([
                    'value' => $request->isCreateOrAttachRequest() ? $resources : []
                ])),

            BooleanGroup::make(__('Display Setting'), 'config->display')
                ->options($this->displayConfigurations($request)),

            Targomaan::make([
                Textarea::make(__('Describe Category'), 'abstract'),
            ]), 

            new Panel(__('Contents Display Settings'), $this->filter([
                new Fields\RelatableDisplayFields($request, __("Category`s content display settings")),
            ])),
        ];
    }   

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
    public function displayConfigurations(Request $request)
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

    public static function screens()
    {
        return collect([
            'default' => __('Default'), 
            'desktop' => __('Desktop'), 
            'mobile'  => __('Mobile'), 
            'tablet'  => __('Tablet')
        ]);
    } 

    public function shouldIgnoreScreen(Request $request, $categorizable, $screen)
    {
        return $request->editing &&
               $request->exists($categorizable::uriKey()."_{$screen}") &&
               (int) $request->get($categorizable::uriKey()."_{$screen}") == 0;
    } 

    public function prepareCategorizableFields($categorizable, $fields)
    { 
        return $this->configField([
                    $this->jsonField($categorizable::uriKey(), $fields)
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
}
