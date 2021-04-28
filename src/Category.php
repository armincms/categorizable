<?php

namespace Armincms\Categorizable;

use Illuminate\Http\Request; 
use Illuminate\Database\Eloquent\{Model, SoftDeletes}; 
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Armincms\Concerns\{HasConfig, HasMediaTrait, Authorization, InteractsWithLayouts};  
use Armincms\Targomaan\Concerns\InteractsWithTargomaan;
use Armincms\Targomaan\Contracts\Translatable; 
use Armincms\Contracts\{Authorizable, HasLayout};
use Core\HttpSite\Concerns\IntractsWithSite;
use Armincms\Helpers\SharedResource;
use Armincms\Taggable\Concerns\InteractsWithTags;  
use Armincms\Taggable\Contracts\Taggable;

abstract class Category extends Model implements Translatable, HasMedia, Authorizable, HasLayout, Taggable
{
    use InteractsWithTargomaan, SoftDeletes, HasMediaTrait, Authorization, HasConfig; 
    use InteractsWithLayouts, InteractsWithTags, IntractsWithSite, HasPublish; 
    
    const TRANSLATION_TABLE = 'categories_translations';

    const TRANSLATION_MODEL = Translation::class;

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
    public static function boot()
    {
        parent::boot(); 

        static::addGlobalScope(function($query) {
            return $query->resourceIn(static::resources()->all());
        });
    }  

    /**
     * Get the interface of scoped resources.
     * 
     * @return string
     */
    abstract public static function resourcesScope() : string;

    /**
     * Query the related category.
     * 
     * @return \Illuminate\Database\Eloqeunt\Relations\BelongsTo
     */
    public function parent()
    { 
        return $this->belongsTo(static::class, 'category_id');
    }  

    /**
     * Query the related categories.
     * 
     * @return \Illuminate\Database\Eloqeunt\Relations\HasOneOrMany
     */ 
    public function categories()
    {
        return $this->hasMany(static::class, 'category_id');
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

    /**
     * Query the resource type.
     * 
     * @param  \Illumiante\Database\Elqoeunt\Query $query    
     * @param  array  $resources
     * @return \Illumiante\Database\Elqoeunt\Query           
     */
    public function scopeResourceIn($query, array $resources)
    {
        return $query->tap(function($query) use ($resources) {
            collect($resources)->each(function($resource, $key) use ($query) { 
                $query->when(
                    boolval($key), 
                    function($query) use ($resource) {
                        $query->orWhere->resource($resource);
                    }, 
                    function($query) use ($resource) {
                        $query->resource($resource);
                    });
            });
        });
    }

    /**
     * Query for the given resource.
     * 
     * @param  \Illumiante\Database\Elqoeunt\Query $query    
     * @param  string  $resource
     * @return \Illumiante\Database\Elqoeunt\Query           
     */
    public function scopeResource($query, string $resource)
    { 
        return $query->where($query->qualifyColumn('config->resources->'.$resource::uriKey()), true);
    }

    /**
     * Driver name of the targomaan.
     * 
     * @return string
     */
    public function translator(): string
    {
        return 'layeric';
    }

    /**
     * Get the tag url.
     * 
     * @return string
     */
    public function url(): string
    {
        return $this->site()->url(urldecode($this->getTranslation('url')));
    }

    /**
     * Get the resources available for the given interface.
     *
     * @param  \Illuminate\Http\Request  $request 
     * @return \Illuminate\Support\Collection
     */
    public static function resources()
    {
        return SharedResource::availableResources(app('request'), static::resourcesScope());
    } 

    /**
     * Get meta data information about all resources for client side consumption.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $interface
     * @return \Illuminate\Support\Collection
     */
    public static function resourceInformation()
    {
        return SharedResource::resourceInformation(app('request'), static::resourcesScope());
    }

    /**
     * Prepare the resource for JSON serialization.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function serializeForDetail(Request $request)
    {
        return [
            'name' => $this->name,
            'logo' => $this->getLogo(),
            'banner'    => $this->getBanner(), 
            'abstract'  => $this->abstract,
        ];
    }

    /**
     * Retruns the Banner images.
     * 
     * @return array
     */
    public function getBanner()
    {
        return $this->getConversions($this->getFirstMedia('banner'), ['common-main', 'common-thumbnail']);
    }

    /**
     * Retruns the Logo images.
     * 
     * @return array
     */
    public function getLogo()
    {
        return $this->getConversions($this->getFirstMedia('logo'), ['thumbnail']);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if($resource = static::resourceInformation()->where('relation', $method)->first()) {
            return $this->morphedByMany($resource['model'], 'categorizable', 'categorizable');
        }

        return parent::__call($method, $parameters);
    }
}
