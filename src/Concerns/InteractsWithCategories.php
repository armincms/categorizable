<?php

namespace Armincms\Categorizable\Concerns;


trait InteractsWithCategories
{
	/**
	 * Query the related categories.
	 * 
	 * @return \Illuminate\Database\Eloqenut\Relations\BelongsToMany
	 */
	abstract public function categories();
}