<?php

namespace Znck\Eloquent\Relations {
    use Illuminate\Database\Eloquent\Model;

    if (! class_exists(BelongsToThrough::class)) {
        class BelongsToThrough
        {
            public function getRelated(): Model
            {
                throw new \RuntimeException('Znck BelongsToThrough stub');
            }
        }
    }
}
