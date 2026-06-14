<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class SlugFieldPost extends Model
{
    protected $table = 'slug_field_posts';

    protected $guarded = [];
}
