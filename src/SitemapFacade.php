<?php

namespace Uzsoftic\SitemapGenerator;

use Illuminate\Support\Facades\Facade;

class SitemapGeneratorFacade extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sitemap-generator';
    }

}
