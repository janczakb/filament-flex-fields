<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Forms\Components\Select;

it('extends filament select and exposes custom styling api', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->size(ControlSize::Lg)
        ->variant('flat')
        ->color('success')
        ->chipColor('danger')
        ->richOptions();

    expect($field)->toBeInstanceOf(Select::class)
        ->and($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('flat')
        ->and($field->getColor())->toBe('success')
        ->and($field->getChipColor())->toBe('danger')
        ->and($field->usesRichOptionHtml())->toBeTrue();
});

it('transforms rich options for js with html labels', function () {
    $field = SelectField::make('plan')
        ->richOptions()
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced plan',
                'badge' => 'Popular',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options)->toHaveCount(1)
        ->and($options[0]['value'])->toBe('pro')
        ->and($options[0]['label'])->toContain('fff-select-option')
        ->and($options[0]['label'])->toContain('Pro');
});

it('keeps grouped options when transforming rich options', function () {
    $field = SelectField::make('status')
        ->options([
            'In process' => [
                'draft' => 'Draft',
                'reviewing' => 'Reviewing',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toBe('In process')
        ->and($options[0]['options'][0]['value'])->toBe('draft');
});

it('detects rich option shapes automatically', function () {
    $field = SelectField::make('plan')
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced plan',
            ],
        ]);

    expect($field->usesRichOptionHtml())->toBeTrue();
});

it('exposes focus outline api', function () {
    expect(SelectField::make('status')->shouldShowFocusOutline())->toBeFalse()
        ->and(SelectField::make('status')->focusOutline()->getWrapperClasses())->toHaveKey('has-focus-outline');
});

it('defaults select trigger icons to gravity ui circle chevron and circle xmark', function () {
    $field = SelectField::make('status');

    expect($field->getChevronIcon())->toBe(GravityIcon::CircleChevronDown)
        ->and($field->getClearIcon())->toBe(GravityIcon::CircleXmark)
        ->and($field->getDefaultChevronIcon())->toBe(GravityIcon::CircleChevronDown)
        ->and($field->getDefaultClearIcon())->toBe(GravityIcon::CircleXmark);
});

it('allows overriding select trigger icons with heroicon or any icon set', function () {
    $field = SelectField::make('status')
        ->chevronIcon('heroicon-o-chevron-down')
        ->clearIcon('heroicon-o-x-circle');

    expect($field->getChevronIcon())->toBe('heroicon-o-chevron-down')
        ->and($field->getClearIcon())->toBe('heroicon-o-x-circle');
});

it('accepts gravity ui icons in rich select options', function () {
    $field = SelectField::make('plan')
        ->richOptions()
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced plan',
                'icon' => GravityIcon::Thunderbolt,
            ],
        ]);

    expect($field->getOptions()['pro']['icon'])->toBe(GravityIcon::Thunderbolt)
        ->and($field->getOptionsForJs()[0]['label'])->toContain('fff-select-option');
});

it('supports soft variant for gray trigger without shadow', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->variant('soft');

    expect($field->getVariant())->toBe('soft')
        ->and($field->getWrapperClasses())->toContain('fff-select-field--soft');
});

it('rejects unsupported select variants', function () {
    SelectField::make('status')->variant('ghost')->getVariant();
})->throws(InvalidArgumentException::class);

it('rejects unsupported select option layouts', function () {
    SelectField::make('theme')->optionLayout('cards')->getOptionLayout();
})->throws(InvalidArgumentException::class);

it('renders grid layout html for dropdown options', function () {
    $field = SelectField::make('theme')
        ->optionLayout('grid')
        ->options([
            'sky' => [
                'label' => 'Sky',
                'badge_color' => 'primary',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toContain('fff-select-option--grid')
        ->and($options[0]['label'])->toContain('fff-select-option__fallback')
        ->and($options[0]['triggerLabel'])->toContain('fff-select-option--trigger')
        ->and($options[0]['triggerLabel'])->toContain('fff-select-option__trigger-label');
});

it('renders trigger layout for grid selected value', function () {
    $field = SelectField::make('theme')
        ->optionLayout('grid')
        ->options([
            'sky' => [
                'label' => 'Sky',
                'badge_color' => 'primary',
            ],
        ]);

    $triggerLabels = $field->getTriggerOptionLabelsForJs();

    expect($triggerLabels['sky'])->toContain('fff-select-option--trigger')
        ->and($triggerLabels['sky'])->toContain('fff-select-option__trigger-label');
});

it('supports image urls in rich options', function () {
    $field = SelectField::make('theme')
        ->richOptions()
        ->options([
            'ocean' => [
                'label' => 'Ocean',
                'image' => 'https://example.com/ocean.png',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toContain('https://example.com/ocean.png')
        ->and($options[0]['label'])->toContain('fff-select-option__image');
});

it('includes wrapper classes for size and variant', function () {
    $field = SelectField::make('status')
        ->label('Status')
        ->size('sm')
        ->variant('underlined')
        ->color('primary');

    expect($field->getWrapperClasses())->toMatchArray([
        'fff-select-field',
        'fff-select-field--sm',
        'fff-select-field--underlined',
        'fff-select-field--layout-list',
        'fff-select-field--chips-neutral',
        'fi-color-primary' => 'primary',
    ]);
});

it('keeps search in the dropdown by default for searchable single selects', function () {
    $field = SelectField::make('country')
        ->searchable()
        ->options(['pl' => 'Poland']);

    expect($field->hasInlineSearch())->toBeFalse()
        ->and($field->getWrapperClasses())->not->toHaveKey('fff-select-field--inline-search');
});

it('can opt into inline search in the trigger for searchable single selects', function () {
    $field = SelectField::make('country')
        ->searchable()
        ->inlineSearch()
        ->options(['pl' => 'Poland']);

    expect($field->hasInlineSearch())->toBeTrue()
        ->and($field->getWrapperClasses())->toHaveKey('fff-select-field--inline-search');
});

it('can enable inline field label in trigger', function () {
    $field = SelectField::make('status')
        ->label('Status')
        ->inlineFieldLabel();

    expect($field->hasInlineFieldLabel())->toBeTrue()
        ->and($field->getWrapperClasses())->toHaveKey('fff-select-field--inline-field-label');
});

it('can disable inline field label in trigger', function () {
    $field = SelectField::make('status')
        ->label('Status')
        ->inlineFieldLabel(false);

    expect($field->hasInlineFieldLabel())->toBeFalse()
        ->and($field->getWrapperClasses())->not->toHaveKey('fff-select-field--inline-field-label');
});

it('omits triggerLabel when identical to dropdown label', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft']);

    $options = $field->getOptionsForJs();

    expect($options[0])->not->toHaveKey('triggerLabel');
});

it('includes triggerLabel for rich list options', function () {
    $field = SelectField::make('plan')
        ->richOptions()
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced plan',
                'badge' => 'Popular',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toContain('fff-select-option--list')
        ->and($options[0]['triggerLabel'])->toContain('fff-select-option--trigger');
});

it('can disable clear button for bordered selects', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->clearable(false);

    expect($field->isClearable())->toBeFalse()
        ->and($field->canSelectPlaceholder())->toBeFalse()
        ->and($field->getWrapperClasses())->toHaveKey('fff-select-field--not-clearable');
});

it('enables clear button by default for bordered selects', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft']);

    expect($field->isClearable())->toBeTrue()
        ->and($field->getWrapperClasses())->not->toHaveKey('fff-select-field--not-clearable');
});

it('adds clearable has value wrapper class when a value is selected', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->default('draft');

    expect($field->getWrapperClasses())->toHaveKey('fff-select-field--clearable-has-value');
});

it('does not add clearable has value wrapper class when disabled', function () {
    $field = SelectField::make('status')
        ->options(['published' => 'Published'])
        ->default('published')
        ->disabled();

    expect($field->getWrapperClasses())->not->toHaveKey('fff-select-field--clearable-has-value');
});

it('does not add clearable has value wrapper class when state is blank', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft']);

    expect($field->getWrapperClasses())->not->toHaveKey('fff-select-field--clearable-has-value');
});

it('returns null for item card initial trigger label on non item card variants', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->variant('bordered')
        ->default('draft');

    expect($field->getItemCardInitialTriggerLabel())->toBeNull();
});

it('returns null for initial trigger label on item card variants', function () {
    $field = SelectField::make('channel')
        ->options(['email' => 'Email'])
        ->variant('item-card')
        ->default('email');

    expect($field->getInitialTriggerLabel())->toBeNull();
});

it('returns placeholder for initial trigger label when state is blank', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->variant('bordered')
        ->placeholder('Select status');

    expect($field->getInitialTriggerLabel())->toBe('Select status');
});

it('returns option label for initial trigger label when state is filled', function () {
    $field = SelectField::make('status')
        ->options(['published' => 'Published'])
        ->variant('bordered')
        ->default('published');

    expect($field->getInitialTriggerLabel())->toBe('Published');
});

it('keeps rich list layout for initial trigger label instead of compact trigger', function () {
    $field = SelectField::make('plan')
        ->richOptions()
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced plan',
                'badge' => 'Popular',
            ],
        ])
        ->default('pro');

    $label = $field->getInitialTriggerLabel();

    expect($label)
        ->toContain('fff-select-option--list')
        ->toContain('Advanced plan')
        ->not->toContain('fff-select-option--trigger');
});

it('returns empty initial trigger badges for single select', function () {
    $field = SelectField::make('status')
        ->options(['published' => 'Published'])
        ->default('published');

    expect($field->getInitialTriggerBadges())->toBe([]);
});

it('returns initial trigger badges for multiple select defaults', function () {
    $field = SelectField::make('tech')
        ->options(['tailwind' => 'Tailwind CSS', 'laravel' => 'Laravel'])
        ->multiple()
        ->default(['tailwind', 'laravel']);

    expect($field->getInitialTriggerBadges())->toBe([
        ['value' => 'tailwind', 'label' => 'Tailwind CSS'],
        ['value' => 'laravel', 'label' => 'Laravel'],
    ]);
});

it('returns placeholder for item card initial trigger label when state is blank', function () {
    $field = SelectField::make('channel')
        ->options(['email' => 'Email'])
        ->variant('item-card')
        ->placeholder('Select channel');

    expect($field->getItemCardInitialTriggerLabel())->toBe('Select channel');
});

it('returns option label for item card initial trigger label when state is filled', function () {
    $field = SelectField::make('channel')
        ->options([
            'email' => 'Email',
            'push_whatsapp' => 'Push Notification, WhatsApp',
        ])
        ->variant('item-card')
        ->default('push_whatsapp');

    expect($field->getItemCardInitialTriggerLabel())->toBe('Push Notification, WhatsApp');
});

it('disables clear button by default for item card selects', function () {
    $field = SelectField::make('channel')
        ->options(['email' => 'Email'])
        ->variant('item-card');

    expect($field->isClearable())->toBeFalse()
        ->and($field->getWrapperClasses())->toHaveKey('fff-select-field--not-clearable')
        ->and($field->getDropdownAlign())->toBe('end');
});

it('can override item card dropdown alignment', function () {
    $field = SelectField::make('channel')
        ->options(['email' => 'Email'])
        ->variant('item-card')
        ->dropdownAlign('start');

    expect($field->getDropdownAlign())->toBe('start');
});

it('inherits searchable and multiple configuration from filament select', function () {
    $field = SelectField::make('tags')
        ->multiple()
        ->searchable()
        ->minItems(1)
        ->maxItems(3);

    expect($field->isMultiple())->toBeTrue()
        ->and($field->isSearchable())->toBeTrue();
});

it('treats static playground selects as client-side option lists', function () {
    $field = SelectField::make('status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ])
        ->searchable();

    expect($field->hasClientSideOptionList())->toBeTrue()
        ->and($field->hasDynamicOptions())->toBeFalse()
        ->and($field->hasDynamicSearchResults())->toBeFalse()
        ->and($field->hasInitialNoOptionsMessage())->toBeFalse();
});

it('keeps dynamic option fetching for closure based option lists', function () {
    $field = SelectField::make('status')
        ->options(fn (): array => ['draft' => 'Draft'])
        ->searchable();

    expect($field->hasClientSideOptionList())->toBeFalse()
        ->and($field->hasDynamicOptions())->toBeTrue()
        ->and($field->hasInitialNoOptionsMessage())->toBeTrue();
});

it('keeps dynamic option fetching for preloaded selects', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->searchable()
        ->preload();

    expect($field->hasClientSideOptionList())->toBeFalse()
        ->and($field->hasDynamicOptions())->toBeTrue();
});

it('defines stable select field search cache keys', function () {
    $field = SelectField::make('status');
    $reflection = new ReflectionClass($field);
    $method = $reflection->getMethod('searchCacheKey');
    $method->setAccessible(true);

    expect($method->invoke($field, 'draft'))->toBe($method->invoke($field, 'draft'))
        ->and($method->invoke($field, 'draft'))->not->toBe($method->invoke($field, 'review'));
});

it('stores select field search results in request cache', function () {
    $field = SelectField::make('status');
    $reflection = new ReflectionClass($field);
    $cache = $reflection->getProperty('searchResultsCache');
    $cache->setAccessible(true);
    $cacheKey = $reflection->getMethod('searchCacheKey');
    $cacheKey->setAccessible(true);

    $key = $cacheKey->invoke($field, 'draft');
    $cache->setValue($field, [$key => ['draft' => 'Draft']]);

    expect($cache->getValue($field))->toHaveKey($key);
});
