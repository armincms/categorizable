<?php

namespace Armincms\Categorizable\Models;

use Kalnoy\Nestedset\Collection as NestedsetCollection;

class Collection extends NestedsetCollection 
{ 
    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int  $depth
     * @return static
     */
    public function flatten($depth = INF)
    {
    	return parent::flatMap(function($category) {
    		return $category->descendants->merge($this->items);
    	});
    }
}