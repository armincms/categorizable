<?php

namespace Armincms\Categorizable\Nova;
  
use Illuminate\Http\Request; 
use Illuminate\Support\Str;

abstract class Categorizable  
{          
    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label()
    {
        return Str::plural(Str::title(Str::snake(class_basename(get_called_class()), ' ')));
    }


    /**
     * Get the Config key for the resource.
     *
     * @return string
     */
    public static function configKey()
    {
        return Str::plural(Str::kebab(class_basename(get_called_class())));
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    abstract public static function fields(Request $request): array; 
}
