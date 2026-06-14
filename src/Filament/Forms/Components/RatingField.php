<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\CalculatesRatingFill;
use Bjanczak\FilamentFlexFields\Concerns\DisplaysRating;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use Filament\Support\Concerns\HasExtraAlpineAttributes;

class RatingField extends Field
{
    use CalculatesRatingFill;
    use CanBeReadOnly;
    use DisplaysRating;
    use HasControlSize;
    use HasExtraAlpineAttributes;

    protected string $view = 'filament-flex-fields::forms.components.rating-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(null);

        $this->rules([
            'nullable',
            'numeric',
            fn (RatingField $component): string => 'min:0',
            fn (RatingField $component): string => 'max:'.$component->getMax(),
            fn (RatingField $component): ?string => $component->isReadOnly() ? null : 'integer',
        ]);
    }
}
