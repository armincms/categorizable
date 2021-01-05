<?php

namespace Armincms\Categorizable\Contracts;


interface Categorizable
{
	/**
	 * Query the related categories.
	 * 
	 * @return \Illuminate\Database\Eloqenut\Relations\BelongsToMany
	 */
	public function categories();
}