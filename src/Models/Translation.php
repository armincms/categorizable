<?php

namespace Armincms\Categorizable\Models;

use Armincms\Contract\Concerns\Authorizable;
use Armincms\Contract\Concerns\InteractsWithMeta;
use Armincms\Contract\Concerns\InteractsWithUri;
use Armincms\Contract\Concerns\Localizable;
use Armincms\Contract\Concerns\Sluggable;
use Armincms\Contract\Contracts\HasMeta;
use Armincms\Markable\Archivable;
use Armincms\Targomaan\Translation as Model;

class Translation extends Model implements HasMeta
{
    use Archivable;
    use Authorizable;
    use InteractsWithMeta;
    use InteractsWithUri;
    use Localizable;
    use Sluggable;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return 'categories_translations';
    }
}
