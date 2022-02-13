<?php

namespace Armincms\Categorizable\Cypress\Widgets;

use Armincms\Contract\Gutenberg\Templates\Pagination; 
use Armincms\Contract\Gutenberg\Widgets\HasRelationships; 
use Armincms\Contract\Gutenberg\Widgets\BootstrapsTemplate;  
use Armincms\Contract\Gutenberg\Widgets\ResolvesDisplay;   
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Zareismail\Cypress\Widget;  
use Zareismail\Cypress\Http\Requests\CypressRequest;
use Zareismail\Gutenberg\Gutenberg; 
use Zareismail\Gutenberg\GutenbergWidget; 

abstract class SingleCategory extends GutenbergWidget
{        
    use BootstrapsTemplate; 
    use HasRelationships; 
    use ResolvesDisplay;

    /**
     * The logical group associated with the template.
     *
     * @var string
     */
    public static $group = 'Other';

    /**
     * Indicates if the widget should be shown on the component page.
     *
     * @var \Closure|bool
     */
    public $showOnComponent = false;

    /**
     * Bootstrap the resource for the given request.
     * 
     * @param  \Zareismail\Cypress\Http\Requests\CypressRequest $request 
     * @param  \Zareismail\Cypress\Layout $layout 
     * @return void                  
     */
    public function boot(CypressRequest $request, $layout)
    {   
        parent::boot($request, $layout); 

        collect(static::resources())->each(function($resource) use ($request, $layout) {
            if ($templateKey = $this->metaValue($resource::uriKey())) {
                $template = $this->bootstrapTemplate($request, $layout, $templateKey);

                $this->displayResourceUsing(function($attributes) use ($template) {
                    return $template->gutenbergTemplate($attributes)->render();
                }, $resource);
            } 
        }); 

        $pagination = $this->bootstrapTemplate($request, $layout, $this->metaValue('pagination'));

        $this->displayResourceUsing(function($attributes) use ($pagination) {
            return $pagination->gutenbergTemplate($attributes)->render();
        }, 'pagination');

        $this->withMeta([
            'resource' => $request->resolveFragment()->metaValue('resource'),
        ]); 
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public static function fields($request)
    {
        return collect(static::resources())->map(function($resource) {
            return Select::make(__('Display '.$resource::label().' By'), 'config->'.$resource::uriKey())
                ->options(Gutenberg::cachedTemplates()->forHandler(static::handler($resource))->keyBy->getKey()->map->name)
                ->nullable() 
                ->displayUsingLabels()
                ->withMeta([
                    'placeholder' => __('Dont Display '.$resource::label())
                ]);
        })->merge([ 
            Select::make(__('Display Pagination By'), 'config->pagination')
                ->options(Gutenberg::cachedTemplates()->forHandler(Pagination::class)->keyBy->getKey()->map->name)
                ->displayUsingLabels()
                ->required()
                ->rules('required'),  

            Number::make(__('Display per page'), 'config->per_page')
                ->required()
                ->min(1)
                ->rules('required', 'min:1')
                ->default(15),
        ])->toArray(); 
    }
    
    /**
     * Get the reated keys for the given relatinoship.
     * 
     * 
     * @param  string $relationship 
     * @return integer|array              
     */
    protected function getRelatedKeys(string $relationship)
    { 
        return with($this->getParent($relationship), function($resource) {
            return $resource->descendants->map->getKey()->push($resource->getKey()); 
        });
    } 

    /**
     * Get the parent model.
     * 
     * @param  string  $relationship 
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getParent(string $relationship)
    { 
        return $this->metaValue('resource');
    } 

    /**
     * Serialize the widget fro template.
     * 
     * @return array
     */
    public function serializeForDisplay(): array
    {
        $resource = $this->metaValue('resource');
        $paginator = $this->belongsToMany('categories');

        return array_merge($resource->serializeForWidget($this->getRequest()), [
            'items' => $paginator->getCollection()->map(function($item) { 
                $resource = static::findResourceForModel($item);

                return $this->displayResource( 
                    $item->serializeForWidget($this->getRequest(), false),
                    $resource
                );
            })->implode(''),

            'pagination' => $this->displayResource($paginator->toArray(), 'pagination'),
        ]);
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
            \Armincms\Categorizable\Gutenberg\Templates\SingleCategory::class,
        );
    } 

    /**
     * Get resource for the given model.
     * 
     * @param  \Illuminate\Database\Eloqeunt\Model $model 
     * @return string      
     */
    public static function findResourceForModel($model)
    {
        return collect(static::resources())->first(function($resource) use ($model) {
            return $resource::$model === get_class($model);
        });
    }
  
    /**
     * Get the tag related content template name.
     * 
     * @return string
     */
    abstract public static function resources(): array; 
  
    /**
     * Get the template handlers for given resourceName.
     * 
     * @return string
     */
    abstract public static function handler(string $resourceName): array;
}
