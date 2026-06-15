<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum MapboxSearchType: string
{
    case Country = 'country';

    case Region = 'region';

    case Postcode = 'postcode';

    case District = 'district';

    case Place = 'place';

    case Locality = 'locality';

    case Neighborhood = 'neighborhood';

    case Address = 'address';

    case Poi = 'poi';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $type): string => $type->value,
            self::cases(),
        );
    }
}
