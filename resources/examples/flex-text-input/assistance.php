<?php

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;

FlexTextInput::make('flex_text_input__suggestions')
    ->label('Local suggestions · free text')
    ->suggestions([
        0 => [
            'value' => 'Laravel',
            'label' => 'Laravel',
            'description' => 'PHP framework',
        ],
        1 => [
            'value' => 'Livewire',
            'label' => 'Livewire',
            'description' => 'Reactive UI',
        ],
        2 => [
            'value' => 'Filament',
            'label' => 'Filament',
            'description' => 'Admin panel',
        ],
        3 => [
            'value' => 'Symfony',
            'label' => 'Symfony · disabled example',
            'description' => 'Disabled options cannot be selected.',
            'disabled' => true,
        ],
    ])
    ->suggestionMinLength(0);

FlexTextInput::make('flex_text_input__suggestionLimit_0')
    ->label('suggestionLimit(8)')
    ->suggestions([
        0 => 'Laravel',
        1 => 'Livewire',
        2 => 'Filament',
    ])
    ->suggestionLimit(8);

FlexTextInput::make('flex_text_input__suggestionMinLength_0')
    ->label('suggestionMinLength(1)')
    ->suggestions([
        0 => 'Laravel',
        1 => 'Livewire',
        2 => 'Filament',
    ])
    ->suggestionMinLength(1);

FlexTextInput::make('flex_text_input__suggestionPlacement_0')
    ->label('suggestionPlacement(auto)')
    ->suggestions([
        0 => 'Laravel',
        1 => 'Livewire',
        2 => 'Filament',
    ])
    ->suggestionPlacement('auto');

FlexTextInput::make('flex_text_input__suggestionPlacement_1')
    ->label('suggestionPlacement(top)')
    ->suggestions([
        0 => 'Laravel',
        1 => 'Livewire',
        2 => 'Filament',
    ])
    ->suggestionPlacement('top');

FlexTextInput::make('flex_text_input__suggestionPlacement_2')
    ->label('suggestionPlacement(bottom)')
    ->suggestions([
        0 => 'Laravel',
        1 => 'Livewire',
        2 => 'Filament',
    ])
    ->suggestionPlacement('bottom');

FlexTextInput::make('flex_text_input__suggestionDebounce_0')
    ->label('suggestionDebounce(200)')
    ->suggestions([
        0 => 'Laravel',
        1 => 'Livewire',
        2 => 'Filament',
    ])
    ->suggestionDebounce(200);

FlexTextInput::make('flex_text_input__draftKey_0')
    ->label('draftKey(demo-text-input)')
    ->draftKey('demo-text-input');

FlexTextInput::make('flex_text_input__draftStorage_0')
    ->label('draftStorage(session)')
    ->draftKey('demo-draftStorage_0')
    ->draftStorage('session');

FlexTextInput::make('flex_text_input__draftStorage_1')
    ->label('draftStorage(local)')
    ->draftKey('demo-draftStorage_1')
    ->draftStorage('local');

FlexTextInput::make('flex_text_input__draftLifetime_0')
    ->label('draftLifetime(86400)')
    ->draftKey('demo-draftLifetime_0')
    ->draftLifetime(86400);

// Independent async helpers keep native editing intact while their request runs.
FlexTextInput::make('flex_text_input__async_suggestions')
    ->label('Async suggestions · bounded request')
    ->suggestionsUsing(fn (string $search): array => array_values(array_filter(
        ['Amsterdam', 'Athens', 'Berlin', 'Lisbon', 'London', 'Paris', 'Warsaw'],
        fn (string $city): bool => str_contains(strtolower($city), strtolower($search)),
    )))
    ->suggestionMinLength(1)
    ->skipRenderAfterStateUpdated();

FlexTextInput::make('flex_text_input__verification')
    ->label('Async verification · latest result wins')
    ->verifyUsing(function (string $value): array {
        $valid = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;

        return [
            'state' => $valid ? 'success' : 'error',
            'message' => $valid ? 'Valid email format' : 'Enter a valid email address',
        ];
    })
    ->verificationDebounce(300)
    ->verificationMinLength(3)
    ->skipRenderAfterStateUpdated();

// The same local records with descriptions aligned to the end of the row.
FlexTextInput::make('framework')
    ->suggestions([
        ['value' => 'Laravel', 'label' => 'Laravel', 'description' => 'PHP framework'],
        ['value' => 'Livewire', 'label' => 'Livewire', 'description' => 'Reactive UI'],
        ['value' => 'Filament', 'label' => 'Filament', 'description' => 'Admin panel'],
    ])
    ->suggestionDescriptionPosition('trailing')
    ->suggestionMinLength(0);

// Server-side search keeps structured metadata and commits only the value.
FlexTextInput::make('city')
    ->suggestionsUsing(fn (string $search): array => array_values(array_filter([
        ['value' => 'Amsterdam', 'label' => 'Amsterdam', 'description' => 'Netherlands'],
        ['value' => 'Warsaw', 'label' => 'Warsaw', 'description' => 'Poland'],
    ], fn (array $city): bool => str_contains(strtolower($city['label']), strtolower($search)))))
    ->suggestionDescriptionPosition('trailing')
    ->suggestionMinLength(1);
