<?php

namespace Armincms\Categorizable\Cypress\Widgets;

use Armincms\Categorizable\Nova\Category;
use Armincms\Contract\Gutenberg\Templates\Pagination;
use Armincms\Contract\Gutenberg\Widgets\BootstrapsTemplate;
use Armincms\Contract\Gutenberg\Widgets\ResolvesDisplay;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use PhoenixLib\NovaNestedTreeAttachMany\NestedTreeAttachManyField as CategorySelect;
use Zareismail\Cypress\Http\Requests\CypressRequest;
use Zareismail\Cypress\Widget;
use Zareismail\Gutenberg\Gutenberg;
use Zareismail\Gutenberg\GutenbergWidget;

abstract class IndexCategory extends GutenbergWidget
{
    use BootstrapsTemplate;
    use ResolvesDisplay;

    /**
     * The logical group associated with the widget.
     *
     * @var string
     */
    public static $group = 'Category';

    /**
     * Bootstrap the resource for the given request.
     *
     * @param  \Zareismail\Cypress\Http\Requests\CypressRequest  $request
     * @param  \Zareismail\Cypress\Layout  $layout
     * @return void
     */
    public function boot(CypressRequest $request, $layout)
    {
        parent::boot($request, $layout);

        $pagination = $this->bootstrapTemplate(
            $request,
            $layout,
            $this->metaValue('pagination')
        );

        $this->displayResourceUsing(function ($attributes) use ($pagination) {
            return $pagination->gutenbergTemplate($attributes)->render();
        }, 'pagination');
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public static function fields($request)
    {
        return [
            Select::make(__('Display Pagination By'), 'config->pagination')
                ->options(static::findTemplates(Pagination::class))
                ->displayUsingLabels()
                ->required()
                ->rules('required'),

            CategorySelect::make(__('Limit display to'), 'config->categories', Category::class)
                ->useAsField()
                ->nullable(),

            Number::make(__('Display per page'), 'config->per_page')
                ->required()
                ->min(1)
                ->rules('required', 'min:1')
                ->default(15),
        ];
    }

    /**
     * List tempaltes for given handler
     *
     * @param  string  $handler
     * @return array
     */
    public static function findTemplates($handler)
    {
        return Gutenberg::cachedTemplates()->forHandler($handler)->pluck('name', 'id');
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $paginator = Category::newModel()->tap(function ($query) {
            $query->with('media', 'auth');
            $query->when((array) $this->metaValue('categories'), function ($query) {
                $query->whereKey($this->categories()->map->getKey());
            });
        })->paginate($this->metaValue('per_page'));

        return collect(parent::jsonSerialize())
            ->merge($paginator->appends(request()->query())->toArray())
            ->except('data')
            ->merge([
                'items' => $paginator->getCollection()->map->serializeForWidget(
                    $this->getRequest()
                ),
            ])
            ->toArray();
    }

    /**
     * [categories description]
     *
     * @return [type] [description]
     */
    public function categories()
    {
        $categories = (array) $this->metaValue('categories', []);

        return Category::newModel()->whereKey($categories)->get()->flatten()->unique();
    }

    /**
     * Query related templates.
     *
     * @param  [type] $request [description]
     * @param  [type] $query   [description]
     * @return [type]          [description]
     */
    public static function relatableTemplates($request, $query)
    {
        return $query->handledBy(
            \Armincms\Categorizable\Gutenberg\Templates\IndexCategory::class
        );
    }
}
