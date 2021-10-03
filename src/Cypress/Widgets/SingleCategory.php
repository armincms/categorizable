<?php

namespace Armincms\Categorizable\Cypress\Widgets;

use Armincms\Contract\Gutenberg\Templates\HasRelationships; 
use Armincms\Contract\Gutenberg\Templates\Pagination; 
use Laravel\Nova\Fields\BooleanGroup;
use Laravel\Nova\Fields\Select;
use Zareismail\Cypress\Widget;  
use Zareismail\Cypress\Http\Requests\CypressRequest;
use Zareismail\Gutenberg\Gutenberg;
use Zareismail\Gutenberg\HasTemplate;

abstract class SingleCategory extends Widget
{       
    use HasRelationships;
    use HasTemplate;

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
        $this->bootstrapTemplate($request, $layout);
        $this->bootstrapContentTemplate($request, $layout);

        $this->withMeta([
            'resource' => $request->resolveFragment()->metaValue('resource'),
            'contents' => $this->belongsToMany('categories'),
        ]); 
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
        return $this->getRequest()->resolveFragment()->metaValue('resource');
    }

    /**
     * Bootstrap the resource for the given request.
     * 
     * @param  \Zareismail\Cypress\Http\Requests\CypressRequest $request 
     * @param  \Zareismail\Cypress\Resource $layout 
     * @return void                  
     */
    public function bootstrapContentTemplate(CypressRequest $request, $layout)
    { 
        $callback =  function($template) use ($request, $layout) {
            $template ->plugins->filter->isActive()->flatMap->gutenbergPlugins()->each->boot($request, $layout);
        };

        $this->withMeta([
            '_content_template' => tap($this->findTemplate($this->metaValue('template.content')), $callback), 
        ]);
    } 
    
    /**
     * Get the template id.
     * 
     * @return integer
     */
    public function getTemplateId(): int
    {
        return $this->metaValue('template.category');
    } 

    /**
     * Serialize the widget fro template.
     * 
     * @return array
     */
    public function serializeForTemplate(): array
    {
        $request  = $this->getRequest(); 
        $resource = $request->resolveFragment()->metaValue('resource');
        $template = $this->metaValue('_content_template')->gutenbergTemplate([]);
        $paginator = $this->metaValue('contents');
        $contents = $paginator->getCollection()->map->serializeForWidget($request);   

        return array_merge($resource->serializeForWidget($request), [
            'links' => $paginator->links('pagination::default'),
            'contents' => $contents->reduce(function($html, $data) use ($template) {   
                return $html . $template->setAttributes($data)->render();
            }), 
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
        return [
            Select::make(__('Category Template'), 'config->template->category')
                ->options(static::availableTemplates(static::templateName()))
                ->displayUsingLabels()
                ->required()
                ->rules('required'),

            Select::make(__('Category Content Template'), 'config->template->content')
                ->options(static::availableTemplates(static::contentTemplateName()))
                ->displayUsingLabels()
                ->required()
                ->rules('required'),

            // Select::make(__('Pagination Template'), 'config->template->pagination')
            //     ->options(static::availableTemplates(Pagination::class))
            //     ->displayUsingLabels()
            //     ->required()
            //     ->rules('required'),  
        ];
    }
  
    /**
     * Get the template name.
     * 
     * @return string
     */
    public static function templateName(): string
    {
        return \Armincms\Categorizable\Gutenberg\Templates\SingleCategory::class;
    }
  
    /**
     * Get the category related content template name.
     * 
     * @return string
     */
    abstract public static function contentTemplateName(): string; 
}
