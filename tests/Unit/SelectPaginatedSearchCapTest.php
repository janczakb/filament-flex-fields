<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;

it('caps paginated search to the first page when getSearchResultsPageUsing is missing', function (): void {
    $calls = 0;

    $field = SelectField::make('characters')
        ->searchable()
        ->paginatedSearchResults()
        ->searchResultsPageSize(3)
        ->getSearchResultsUsing(function (string $search) use (&$calls): array {
            $calls++;

            return [
                ['label' => 'One', 'value' => '1'],
                ['label' => 'Two', 'value' => '2'],
                ['label' => 'Three', 'value' => '3'],
                ['label' => 'Four', 'value' => '4'],
                ['label' => 'Five', 'value' => '5'],
            ];
        });

    $first = $field->getSearchResultsPageForJs('a');

    expect($calls)->toBe(1)
        ->and($first['items'])->toHaveCount(3)
        ->and($first['hasMore'])->toBeFalse()
        ->and($first['cursor'])->toBeNull();

    $second = $field->getSearchResultsPageForJs('a', '3');

    expect($second['items'])->toBe([])
        ->and($second['hasMore'])->toBeFalse()
        ->and($calls)->toBe(1);
});
