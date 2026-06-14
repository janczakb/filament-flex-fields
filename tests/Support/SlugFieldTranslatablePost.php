<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class SlugFieldTranslatablePost extends Model
{
    protected $table = 'slug_field_translatable_posts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'title' => 'array',
        ];
    }
}
