<?php

namespace Armincms\Categorizable; 
 
use Illuminate\Database\Eloquent\Relations\Pivot;
use Cviebrock\EloquentSluggable\Sluggable;
use Core\HttpSite\Concerns\HasPermalink; 
use Core\HttpSite\Component; 

class Translation extends Pivot  
{ 
	use Sluggable, HasPermalink;   

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false; 

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = []; 

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static $autoLink = false;
    
    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable():array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];  
    }   
}
