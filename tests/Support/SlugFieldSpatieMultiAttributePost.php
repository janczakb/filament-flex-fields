<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: ['title', 'subtitle'], to: 'slug', separator: '-')]
class SlugFieldSpatieMultiAttributePost extends Model
{
    protected $table = 'slug_field_posts';

    protected $guarded = [];
}
