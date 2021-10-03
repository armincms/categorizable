<?php

namespace Armincms\Categorizable\Nova;

use Armincms\Categorizable\Gutenberg\Templates\SingleCategory;
use Armincms\Categorizable\Models\Translation;
use Armincms\Contract\Nova\Authorizable;
use Armincms\Contract\Nova\Fields;
use Armincms\Fields\Targomaan;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Hidden;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource as NovaResource; 
use Zareismail\Fields\Complex; 

class Category extends NovaResource
{ 
    use Authorizable;
    use Fields;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Armincms\Categorizable\Models\Category::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
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
            ID::make(__('Category ID'), 'id')->sortable(),

            BelongsTo::make(__('Parent Category'), 'parent', static::class)
                ->nullable()
                ->withoutTrashed(), 

            Targomaan::make([ 
                Select::make(__('Category Status'), 'marked_as')
                    ->options($this->statuses($request))
                    ->required()
                    ->rules('required')
                    ->default('draft'),

                Text::make(__('Category Name'), 'name')
                    ->required()
                    ->rules('required'),

                Text::make(__('Category Slug'), 'slug')
                    ->nullable(), 

                Textarea::make(__('Category Summary'), 'summary')
                    ->nullable(),  
            ]), 

            Panel::make(__('Advanced Category Configurations'), [ 
                Complex::make(__('Category Images'), function() {
                    return [
                        $this->resourceImage(__('Category Image')),

                        $this->resourceImage(__('Category Logo'), 'logo'),

                        $this->resourceImage(__('Category Application Image'), 'application-image'),

                        $this->resourceImage(__('Category Application Logo'), 'application-logo'),
                    ];
                })->hideFromIndex(),

                Targomaan::make([
                    $this->resourceMeta(__('Category Meta')),
                ]),
            ]),
        ];
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fieldsForIndex(Request $request)
    {
        $model = new Translation;

        return [
            ID::make(__('Category ID'), 'id')->sortable(), 

            Text::make(__('Category Name'), 'name'), 

            $this->resourceUrls(),

            Badge::make(__('Category Status'), 'marked_as')
                ->map([
                    $model->getPublishValue() => 'success',
                    $model->getDraftValue()   => 'info',
                    $model->getArchiveValue() => 'warning',
                    $model->getPendingValue() => 'danger',
                ])
                ->labels([
                    $model->getPublishValue() => __($model->getPublishValue()),
                    $model->getDraftValue()   => __($model->getDraftValue()),
                    $model->getArchiveValue() => __($model->getArchiveValue()),
                    $model->getPendingValue() => __($model->getPendingValue()),
                ]),
        ];
    }

    /**
     * Get the category statuses.
     * 
     * @param  Request $request 
     * @return array           
     */
    public function statuses(Request $request)
    {
        $model = new Translation;

        return $this->filter([
            $model->getDraftValue() => __('Store category as draft'),

            $this->mergeWhen($request->user()->can('publish', $model), function() use ($model) {
                return [
                    $model->getPublishValue() => __('Publish the category'),
                ];
            }, function() {
                return [
                    $model->getPendingValue() => __('Request category publishing'),
                ];
            }),

            $this->mergeWhen($request->user()->can('archive', $model), function() use ($model) {
                return [
                    $model->getArchiveValue() => __('Archive the category'),
                ];
            }), 
        ]);
    }

    /**
     * Build an "index" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query; 
    }

    /**
     * Build a Scout search query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Laravel\Scout\Builder  $query
     * @return \Laravel\Scout\Builder
     */
    public static function scoutQuery(NovaRequest $request, $query)
    {
        return $query;
    }

    /**
     * Build a "detail" query for the given resource.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function detailQuery(NovaRequest $request, $query)
    {
        return parent::detailQuery($request, $query);
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableQuery(NovaRequest $request, $query)
    {
        return parent::relatableQuery($request, $query);
    }

    /**
     * Build a "relatable" query for the given resource.
     *
     * This query determines which instances of the model may be attached to other resources.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function relatableCategories(NovaRequest $request, $query)
    {
        return parent::relatableQuery($request, $query)->whereKeyNot($request->resourceId);
    } 
}
