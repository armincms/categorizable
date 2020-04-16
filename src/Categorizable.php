<?php

namespace Armincms\Categorizable;
 

trait Categorizable  
{ 
	public function categories()
	{
		return $this->morphToMany(Category::class, 'categorizable', 'categorizable');
	}
}
