<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

it('strips mention trigger before async search', function (): void {
    $field = SelectField::make('assignee')
        ->entityMentions(trigger: '@')
        ->getSearchResultsUsing(fn (string $search): array => [
            $search => strtoupper($search),
        ])
        ->searchable();

    expect($field->getSearchResultsForJs('@jan'))->toBe([
        ['label' => 'JAN', 'value' => 'jan', 'isDisabled' => false],
    ]);
});
