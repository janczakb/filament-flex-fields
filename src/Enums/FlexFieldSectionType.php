<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum FlexFieldSectionType: string
{
    case Section = 'section';
    case Fieldset = 'fieldset';
    case Headless = 'headless';
}
