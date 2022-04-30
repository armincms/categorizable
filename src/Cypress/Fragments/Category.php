<?php

namespace Armincms\Categorizable\Cypress\Fragments;
 
use Armincms\Contract\Concerns\InteractsWithModel; 
use Zareismail\Cypress\Fragment; 
use Zareismail\Cypress\Contracts\Resolvable; 

class Category extends Fragment implements Resolvable
{   
    use InteractsWithModel; 

    /**
     * Get the resource Model class.
     * 
     * @return
     */
    public function model(): string
    {
        return \Armincms\Categorizable\Models\Category::class;
    } 

    /**
     * Apply custom query to the given query.
     *
     * @param  \Zareismail\Cypress\Http\Requests\CypressRequest $request
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyQuery($request, $query)
    {
        return $query->unless(\Auth::guard('admin')->check(), function($query) {
            return $query->whereHas('translations', function($query) {
                $query->published()->localize();
            });
        });
    } 
}
