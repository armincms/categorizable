<?php 
namespace Armincms\Categorizable\Components;
 
use Illuminate\Http\Request; 
use Core\Document\Document;
use Core\HttpSite\Component;
use Core\HttpSite\Contracts\Resourceable;
use Core\HttpSite\Concerns\IntractsWithLayout;
use Core\HttpSite\Concerns\IntractsWithResource; 
use Armincms\Categorizable\Helper;

abstract class Category extends Component implements Resourceable
{       
	use IntractsWithResource, IntractsWithLayout; 

	/**
	 * Route of Component.
	 * 
	 * @var null
	 */
	protected $route = 'categories/{slug}'; 

	public function toHtml(Request $request, Document $docuemnt) : string
	{        
		$category = $this->newQuery($request)->whereHas('translations', function($query) use ($request) {
			$query->whereUrl($request->relativeUrl());
		})->firstOrFail(); 

		$this->resource($category);   
		$docuemnt->title($category->name);  
		$docuemnt->description($category->abstract);   
		$layout = $category->getConfig('layout', $this->config('layout', 'clean-category')); 

		return (string) $this->firstLayout($docuemnt, $layout)
							 ->display($category->serializeForDetail($request), array_merge($this->config(), $category->config)); 
	}     

	/**
	 * Get the resource query builder.
	 * 
	 * @param  Request $request 
	 * @return \Illuminate\Database\Elqoeunt\Builder           
	 */
	public function newQuery(Request $request)
	{
		return $this->newModel($request)->newQuery();
	}

	/**
	 * Get the resource query builder.
	 * 
	 * @param  Request $request 
	 * @return \Illuminate\Database\Elqoeunt\Model           
	 */
	abstract public function newModel(Request $request);  

	/**
	 * Returns the categorizable resources.
	 * 
	 * @return array
	 */
	public function categorizables()
	{  
		return $this->resourceInformation()->map(function($relation, $resourceName) {  
			return $this->resource()->{$relation}()->paginate($this->paginationLength());
		})->filter->isNotEmpty(); 
	}  

    /**
     * Get meta data information about all resources for client side consumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $interface
     * @return \Illuminate\Support\Collection
     */
	public function resourceInformation()
	{
		return $this->newModel(app('request'))->resourceInformation()->filter(function($resource) {
			return ! $this->hasFilter() || $this->filteredBy($resource['key']);
		})->pluck('relation', 'key');
	} 

    /**
     * Determine if the request filtered by the given resource.
     * 
     * @param  string $resource
     * @return boolean 
     */
	public function filteredBy(string $resource)
	{
		return request()->query('categorizable') === $resource; 
	} 

    /**
     * Determine if the request filtered by a resource.
     * 
     * @param  string $resource
     * @return boolean 
     */
	public function hasFilter()
	{
		return request()->has('categorizable');
	}

	/**
	 * Returns pagination length.
	 * 
	 * @return int
	 */
	public function paginationLength()
	{ 
		return $this->resourceInformation()->count() < 2 || $this->hasFilter() 
					? $this->resource->getConfig('display.per_page') 
					: 3;
	} 

	/**
	 * Get config value with the given key.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getConfig(string $key, $default = null)
	{
		return $this->resource->getConfig($key, $default);
	}
}
