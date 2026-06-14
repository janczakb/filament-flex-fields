<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;

class TranslatablePost extends Model
{
    protected $table = 'translatable_posts';

    protected $fillable = [
        'title',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
        ];
    }
}
