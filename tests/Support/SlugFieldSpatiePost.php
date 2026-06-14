<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class SlugFieldSpatiePost extends Model
{
    use HasSlug;

    protected $table = 'slug_field_posts';

    protected $guarded = [];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['title', 'subtitle'])
            ->saveSlugsTo('slug')
            ->usingSeparator('-')
            ->usingLanguage('en')
            ->slugsShouldBeNoLongerThan(80);
    }
}
