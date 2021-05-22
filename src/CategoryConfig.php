<?php 

namespace Armincms\Categorizable;

use Armincms\Bios\Option; 
use Armincms\Targomaan\Concerns\InteractsWithTargomaan; 
use Armincms\Targomaan\Contracts\Translatable; 
use Armincms\Categorizable\Nova\CategoryConfig as Config; 
 
class CategoryConfig extends Option implements Translatable
{ 
    use InteractsWithTargomaan; 

    protected static $cachedOptions = [];

    protected $casts = [
    	'CATEGORY_RELATABLE_CONFIGURATIONS' => 'array'
    ];

    public function getConfig($key)
    { 
    	return (array) data_get($this->options(), str_replace('relatable.', '', $key)); 
    } 

    public function fillJsonAttribute($key, $value)
    {
    	if ($key == 'config->relatable') {   
    		return parent::setAttribute('CATEGORY_RELATABLE_CONFIGURATIONS', array_merge(
    			(array) $this->CATEGORY_RELATABLE_CONFIGURATIONS, (array) $value
    		));  
    	} 

    	return parent::fillJsonAttribute($key, $value);
    }

    public function options()
    {
        return Config::option('CATEGORY_RELATABLE_CONFIGURATIONS');
    }
}
