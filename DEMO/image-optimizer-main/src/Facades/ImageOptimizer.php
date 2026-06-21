<?php

namespace DaniHidayatX\ImageOptimizer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \DaniHidayatX\ImageOptimizer\ImageOptimizer
 */
class ImageOptimizer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'image-optimizer';
    }
}
