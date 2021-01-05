<?php

namespace Armincms\Categorizable\Concerns;


trait HasCategory
{
	/**
	 * Query the related categories.
	 * 
	 * @return \Illuminate\Database\Eloqenut\Relations\BelongsToMany
	 */
	public function categories()
	{
		return $this->morphToMany(\Armincms\Categorizable\Category::class, 'categorizable', 'categorizable');
	}
}