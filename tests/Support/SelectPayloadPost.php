<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Persistence stand-in for select playground state (JSON column = "DB write").
 */
class SelectPayloadPost extends Model
{
    protected $table = 'select_payload_posts';

    protected $fillable = [
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
