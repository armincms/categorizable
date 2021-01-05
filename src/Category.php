<?php

namespace Armincms\Categorizable;

use Illuminate\Database\Eloquent\{Model, SoftDeletes}; 
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Armincms\Concerns\{HasConfig, HasMediaTrait, Authorization};  
use Armincms\Targomaan\Concerns\InteractsWithTargomaan;
use Armincms\Targomaan\Contracts\Translatable; 
use Armincms\Contracts\Authorizable;  

class Category extends Model implements Translatable, HasMedia, Authorizable 
{
    use InteractsWithTargomaan, SoftDeletes, HasMediaTrait, Authorization, HasConfig; 
    
    const TRANSLATION_TABLE = 'categories_translations';

    const LOCALE_KEY = 'language';

    protected $medias = [
        'banner' => [  
            'disk'  => 'armin.image',
            'conversions' => [
                'common'
            ]
        ], 

        'logo' => [  
            'disk'  => 'armin.image',
            'conversions' => [
                'common'
            ]
        ], 

        'app_banner' => [  
            'disk'  => 'armin.image',
            'conversions' => [
                'common'
            ]
        ], 

        'app_logo' => [  
            'disk'  => 'armin.image',
            'conversions' => [
                'common'
            ]
        ], 
    ]; 

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot(); 
    }  

    /**
     * Query the related category.
     * 
     * @return \Illuminate\Database\Eloqeunt\Relations\BelongsTo
     */
    public function parent()
    { 
        return $this->belongsTo(self::class, 'category_id');
    }  

    /**
     * Query the related categories.
     * 
     * @return \Illuminate\Database\Eloqeunt\Relations\HasOneOrMany
     */ 
    public function categories()
    {
        return $this->hasMany(self::class, 'category_id');
    } 

    /**
     * Query the related categories and sub categories.
     * 
     * @return \Illuminate\Database\Eloqeunt\Relations\HasOneOrMany
     */
    public function subCategories()
    { 
        return $this->categories()->tap(function($query) {
            $query->with('subCategories');
        });
    }

    /**
     * Flatten all sub categories.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function flattenSubCategories()
    { 
        return $this->subCategories->flatMap(function($category) {
            return $category->flattenSubCategories()->push($category);
        });
    }

    // public function setConfigAttribute($config)
    // { 
    //     $this->attributes['config'] = collect($this->config)->whereNotIn(
    //         'layout', collect($config)->pluck('layout')->all()
    //     )->merge($config)->toJson(); 
    // }

    /**
     * Driver name of the targomaan.
     * 
     * @return [type] [description]
     */
    public function translator(): string
    {
        return 'layeric';
    }
}
