<?php

namespace Armincms\Categorizable;

use Armincms\Concerns\Authorization;
use Armincms\Concerns\IntractsWithMedia;
use Armincms\Contracts\Authorizable; 
use Armincms\Localization\Concerns\HasTranslation;
use Armincms\Localization\Contracts\Translatable;  
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMedia;

class Category extends Model implements Translatable, HasMedia, Authorizable 
{
    use HasTranslation, SoftDeletes, IntractsWithMedia, Authorization; 

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    	"config" => "json", 
    ];


    protected $medias = [
        'gallery' => [
            'multiple' => true,
            'disk' => 'armin.image',
            'schemas' => [
                'cover', 'logo', '*',
            ],
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
     * Get the translation database.
     * 
     * @return string
     */
    public function getTranslationTable()
    {
        return 'categories_translations';
    } 

    public function setConfigAttribute($config)
    { 
        $this->attributes['config'] = collect($this->config)->whereNotIn(
            'layout', collect($config)->pluck('layout')->all()
        )->merge($config)->toJson(); 
    }
}
