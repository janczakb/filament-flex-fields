<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class SlugFieldSelfHealingPost extends SlugFieldPost
{
    use HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->selfHealing();
    }
}
