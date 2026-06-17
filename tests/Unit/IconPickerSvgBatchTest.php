<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;

it('limits svg preview batches to the configured maximum', function (): void {
    $field = IconPickerField::make('icon')
        ->sets(['heroicons']);

    $icons = array_fill(0, 80, 'heroicon-o-star');

    $rendered = $field->getIconPickerSvgPreviews($icons);

    expect(count($rendered))->toBe(IconPickerField::MAX_SVG_PREVIEW_BATCH);
});
