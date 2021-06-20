<?php 
namespace Armincms\Categorizable\Components;
 
use Illuminate\Http\Request; 
use Core\Document\Document;
use Core\HttpSite\Component; 
use Core\HttpSite\Concerns\IntractsWithLayout;  

abstract class Index extends Component
{       
	use IntractsWithLayout; 

	/**
	 * Route of Component.
	 * 
	 * @var null
	 */
	protected $route = 'categories'; 

	public function toHtml(Request $request, Document $docuemnt) : string
	{       
		$docuemnt->title(__('Product Categories'));  
		$docuemnt->description(__('Product Categories'));    
		$layout = $this->config('layout', 'clean-category-review');

		return (string) $this->firstLayout($docuemnt, $layout)->display([
			'categories' => $this->newQuery($request)->paginate(1),
		]); 
	}     

	/**
	 * Get the resource query builder.
	 * 
	 * @param  Request $request 
	 * @return \Illuminate\Database\Elqoeunt\Builder           
	 */
	public function newQuery(Request $request)
	{
		return $this->newModel($request)->newQuery()->published();
	}

	/**
	 * Get the resource query builder.
	 * 
	 * @param  Request $request 
	 * @return \Illuminate\Database\Elqoeunt\Model           
	 */
	abstract public function newModel(Request $request);   
}
