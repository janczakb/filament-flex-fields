<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\Attributes\Sluggable;

#[Sluggable(from: 'title', to: 'slug', separator: '_', maxLength: 40)]
class SlugFieldSpatieAttributePost extends Model
{
    protected $table = 'slug_field_posts';

    protected $guarded = [];
}
