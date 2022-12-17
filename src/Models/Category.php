<?php

namespace Armincms\Categorizable\Models;

use Armincms\Contract\Concerns\Authorizable;
use Armincms\Contract\Concerns\InteractsWithFragments;
use Armincms\Contract\Concerns\InteractsWithMedia;
use Armincms\Contract\Concerns\InteractsWithWidgets;
use Armincms\Contract\Contracts\Authenticatable;
use Armincms\Contract\Contracts\HasMedia;
use Armincms\Targomaan\Concerns\InteractsWithTargomaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model implements Authenticatable, HasMedia
{
    use Authorizable;
    use InteractsWithFragments;
    use InteractsWithMedia;
    use InteractsWithWidgets;
    use InteractsWithTargomaan;
    use NodeTrait;
    use SoftDeletes;

    /**
     * The translation model.
     *
     * @var string
     */
    public const TRANSLATION_MODEL = Translation::class;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return app()->make(\Armincms\Categorizable\CategoryFactory::class);
    }

    /**
     * Query the related GutenbergTemplate.
     *
     * @return \Illuminate\Database\Elqoeunt\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo(\Zareismail\Gutenberg\Models\GutenbergTemplate::class);
    }

    /**
     * Get the corresponding cypress fragment.
     *
     * @return
     */
    public function cypressFragment(): string
    {
        return \Armincms\Categorizable\Cypress\Fragments\Category::class;
    }

    /**
     * Get the available media collections.
     *
     * @return array
     */
    public function getMediaCollections(): array
    {
        return [
            'image' => [
                'conversions' => ['category-image'],
                'multiple' => false,
                'disk' => 'image',
                'limit' => 20, // count of images
                'accepts' => ['image/jpeg', 'image/jpg', 'image/png'],
            ],

            'logo' => [
                'conversions' => ['category-logo'],
                'multiple' => false,
                'disk' => 'image',
                'limit' => 20, // count of images
                'accepts' => ['image/jpeg', 'image/jpg', 'image/png'],
            ],

            'application-image' => [
                'conversions' => ['application-image'],
                'multiple' => false,
                'disk' => 'image',
                'limit' => 20, // count of images
                'accepts' => ['image/jpeg', 'image/jpg', 'image/png'],
            ],

            'application-logo' => [
                'conversions' => ['application-logo'],
                'multiple' => false,
                'disk' => 'image',
                'limit' => 20, // count of images
                'accepts' => ['image/jpeg', 'image/jpg', 'image/png'],
            ],
        ];
    }

    /**
     * Serialize the model to pass into the client view for single item.
     *
     * @param Zareismail\Cypress\Request\CypressRequest
     * @return array
     */
    public function serializeForDetailWidget($request)
    {
        return array_merge($this->serializeForIndexWidget($request), [
            'creation_date' => $this->created_at->format('Y F d'),
            'last_update' => $this->updated_at->format('Y F d'),
            'author' => optional($this->auth)->fullname(),
            'url' => $this->getUrl($request),
        ]);
    }

    /**
     * Serialize the model to pass into the client view for collection of items.
     *
     * @param Zareismail\Cypress\Request\CypressRequest
     * @return array
     */
    public function serializeForIndexWidget($request)
    {
        return array_merge($this->getFirstMediasWithConversions()->toArray(), [
            'name' => $this->name,
            'id' => $this->id,
            'url' => $this->getUrl($request) ?? collect($this->urls())->map->url->first(),
        ]);
    }

    /**
     * Get the targomaan driver.
     *
     * @return string
     */
    public function translator(): string
    {
        return 'layeric';
    }

    /**
     * Get the uri value.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->getTranslation('uri');
    }

    /**
     * Find a model by its uri.
     *
     * @param  string  $uri
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function findByUri($uri, $columns = ['*'])
    {
        return $this->withUri($uri)->first($columns);
    }

    /**
     * Query where has the given uri string.
     *
     * @param  string  $uri
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithUri($query, $uri)
    {
        return $query->whereHas('translations', function ($query) use ($uri) {
            return $query->withUri($uri);
        });
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }
}
