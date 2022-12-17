<?php

namespace Armincms\Categorizable;

trait HasCategories
{
    public function categories()
    {
        return $this->morphToMany(Models\Category::class, 'categorizable', 'categorizable');
    }
}
