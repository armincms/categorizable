<?php

namespace Armincms\Categorizable\Gutenberg\Templates;

use Zareismail\Gutenberg\Template;
use Zareismail\Gutenberg\Variable;

class SingleCategory extends Template
{
    /**
     * The logical group associated with the template.
     *
     * @var string
     */
    public static $group = 'Category';

    /**
     * Register the given variables.
     *
     * @return array
     */
    public static function variables(): array
    {
        return [
            Variable::make('id', __('Category Id')),

            Variable::make('name', __('Category Name')),

            Variable::make('url', __('Category URL')),

            Variable::make('hits', __('Category hits')),

            Variable::make('creation_date', __('Category creation date')),

            Variable::make('last_update', __('Category update date')),

            Variable::make('author', __('Category author')),

            Variable::make('summary', __('Category summary')),

            Variable::make('contents', __('Rendered category contents')),

            Variable::make('links', __('Rendered category pagination links')),

            Variable::make('image.templateName', __(
                'Image with the required template (example: image-category.common-main)'
            )),
        ];
    }
}
