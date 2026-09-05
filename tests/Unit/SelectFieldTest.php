<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Playground\SelectPlayground;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

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

it('pins grid layout desktop dropdowns to 400px and re-applies after overlay anchoring', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $js = file_get_contents(__DIR__.'/../../resources/js/components/select-field/headless-combobox-alpine.js');

    expect($css)
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-grid\s*\{[\s\S]*width:\s*400px\s*!important/')
        ->toContain('max-width: min(400px, calc(100vw - 2rem)) !important')
        ->toContain('min-width: 400px !important')
        ->and($js)
        ->toContain('GRID_DROPDOWN_WIDTH_PX = 400')
        ->toContain('resolveMatchTriggerWidth')
        ->toContain('resolveMenuMinWidth')
        ->toContain('afterOverlayPanelOpened')
        ->toMatch('/if \(this\.isGridLayout\) \{\s*return false/')
        ->toMatch('/selectMenu\.updateMenuPosition\.call\(this, \{ reveal \}\)[\s\S]*if \(this\.isGridLayout\) \{[\s\S]*this\.applyGridDropdownWidth\(\)/');
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
        'fff-rounding-md',
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

it('uses chipLabel as triggerLabel in getOptionsForJs', function () {
    $field = SelectField::make('recipients')
        ->richOptions()
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane.cooper@example.com',
                'chipLabel' => 'jane.cooper@example.com',
            ],
        ]);

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toContain('Jane Cooper')
        ->and($options[0]['label'])->toContain('jane.cooper@example.com')
        ->and($options[0]['triggerLabel'])->toBe('jane.cooper@example.com');
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

it('keeps compact trigger layout for rich list selected value', function () {
    $field = SelectField::make('plan')
        ->richOptions()
        ->options([
            'pro' => [
                'label' => 'Pro',
                'description' => 'Advanced plan',
                'badge' => 'Popular',
                'icon' => 'heroicon-o-bolt',
            ],
        ])
        ->default('pro');

    $label = $field->getInitialTriggerLabel();

    expect($field->shouldUseRichListTriggerDisplay())->toBeFalse()
        ->and($label)
        ->toContain('fff-select-option--trigger')
        ->toContain('Pro')
        ->not->toContain('Advanced plan')
        ->not->toContain('fff-select-option--list');
});

it('accepts desc alias and omits optional icon or description in rich options', function () {
    $field = SelectField::make('plan')->richOptions();
    $normalize = (new ReflectionClass($field))->getMethod('normalizeOption');
    $normalize->setAccessible(true);
    $render = (new ReflectionClass($field))->getMethod('renderRichOptionLabel');
    $render->setAccessible(true);

    $fromDescAlias = $normalize->invoke($field, 'pro', [
        'label' => 'Pro',
        'desc' => 'From desc key',
    ]);
    $iconTitleHtml = $render->invoke($field, $normalize->invoke($field, 'pro', [
        'label' => 'Pro',
        'icon' => 'heroicon-o-bolt',
    ]), 'list');
    $titleDescHtml = $render->invoke($field, $normalize->invoke($field, 'pro', [
        'label' => 'Pro',
        'description' => 'No icon here',
    ]), 'list');
    $triggerHtml = $render->invoke($field, $normalize->invoke($field, 'pro', [
        'label' => 'Pro',
        'description' => 'Hidden in trigger',
        'icon' => 'heroicon-o-bolt',
    ]), 'trigger');

    expect($fromDescAlias['description'])->toBe('From desc key')
        ->and($iconTitleHtml)
        ->toContain('fff-select-option__icon')
        ->not->toContain('fff-select-option__description')
        ->and($titleDescHtml)
        ->toContain('No icon here')
        ->not->toContain('fff-select-option__icon')
        ->and($triggerHtml)
        ->toContain('fff-select-option--trigger')
        ->not->toContain('Hidden in trigger');
});

it('scales rich option tokens with field size', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('--fff-select-rich-icon-size')
        ->toContain('--fff-select-rich-desc-size')
        ->toMatch('/\.fff-select-field--sm[\s\S]*--fff-select-rich-icon-size:\s*1\.75rem/')
        ->toMatch('/\.fff-select-field--lg[\s\S]*--fff-select-rich-icon-size:\s*2\.75rem/')
        ->and($css)
        ->toContain('--fff-select-rich-icon-size')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--sm\{[\s\S]*--fff-select-rich-icon-size:1\.75rem/');
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

it('preserves chip labels in headless initial option labels for multiple rich selects', function () {
    $field = SelectField::make('recipients')
        ->options([
            'jane' => [
                'label' => 'Jane Cooper',
                'description' => 'jane.cooper@example.com',
                'chipLabel' => 'jane.cooper@example.com',
            ],
            'john' => [
                'label' => 'John Smith',
                'description' => 'john.smith@example.com',
                'chipLabel' => 'john.smith@example.com',
            ],
        ])
        ->multiple()
        ->richOptions()
        ->default(['jane', 'john']);

    $labels = $field->getHeadlessInitialOptionLabelsForJs();

    expect($labels)->toHaveCount(2)
        ->and($labels[0]['value'])->toBe('jane')
        ->and($labels[0]['triggerLabel'])->toBe('jane.cooper@example.com')
        ->and($labels[0]['triggerLabel'])->not->toContain('fff-select-option--trigger')
        ->and($labels[0]['label'])->toContain('Jane Cooper')
        ->and($labels[1]['triggerLabel'])->toBe('john.smith@example.com');
});

it('uses headless initial option labels in the headless blade payload', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain('getHeadlessInitialOptionLabelsForJs()')
        ->toContain('{!! $badge[\'label\'] !!}');
});

it('renders a static single-select trigger label before alpine hydrates', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain('$showHeadlessStaticTriggerLabel')
        ->toContain('x-text="triggerLabelHtml()"')
        ->toContain('{{ $initialTriggerLabel }}')
        ->toContain('$headlessTriggerHasClearableValue');
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
        ->and($field->isSearchable())->toBeTrue()
        ->and($field->getMinItems())->toBe(1)
        ->and($field->getMaxItems())->toBe(3);
});

it('honours selectablePlaceholder(false) independently of clearable()', function () {
    $field = SelectField::make('status')
        ->options(['draft' => 'Draft'])
        ->selectablePlaceholder(false);

    expect($field->isClearable())->toBeTrue()
        ->and($field->canSelectPlaceholder())->toBeFalse()
        ->and($field->isClearableInUi())->toBeFalse()
        ->and($field->getWrapperClasses())->toHaveKey('fff-select-field--not-clearable');
});

it('passes filament position into the headless alpine payload', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain('position: @js($field->getPosition())')
        ->toContain('clearable: @js($field->isClearableInUi())');
});

it('can keep selected options visible in the multi-select dropdown', function () {
    $field = SelectField::make('states')
        ->multiple()
        ->searchable()
        ->keepSelectedOptionsInDropdown()
        ->options([
            'california' => 'California',
            'texas' => 'Texas',
        ]);

    $single = SelectField::make('status')
        ->keepSelectedOptionsInDropdown()
        ->options(['draft' => 'Draft']);

    expect($field->shouldKeepSelectedOptionsInDropdown())->toBeTrue()
        ->and($single->shouldKeepSelectedOptionsInDropdown())->toBeFalse();
});

it('passes keep-selected dropdown config into the select field blade patch payload', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain('keepSelectedOptionsInDropdown: @js($field->shouldKeepSelectedOptionsInDropdown())');
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
        ->and($field->hasDynamicSearchResults())->toBeFalse()
        ->and($field->hasInitialNoOptionsMessage())->toBeTrue();
});

it('defers dynamic option closure evaluation until options are fetched for headless hydration', function () {
    $invocations = 0;

    $field = SelectField::make('status')
        ->options(function () use (&$invocations): array {
            $invocations++;

            return ['published' => 'Published'];
        })
        ->variant('bordered')
        ->default('published')
        ->searchable();

    expect($field->getHeadlessInitialOptionsForJs())->toBe([])
        ->and($invocations)->toBe(0)
        ->and($field->getInitialTriggerLabel())->toBe('published');

    $field->getOptionsForJs();

    expect($invocations)->toBeGreaterThan(0);
});

it('seeds headless alpine with initial state and trigger label for deferred dynamic options', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain('initialState: @js($headlessInitialState)')
        ->toContain('$headlessInitialOptionLabel = (blank($state) || $isMultiple) ? null : $field->getInitialTriggerLabel()')
        ->not->toContain('($hasDynamicOptions && ! $isPreloaded()) ? null : $getOptionLabel()');
});

it('keeps client-side headless search query out of combobox engine sync', function () {
    $alpine = file_get_contents(__DIR__.'/../../resources/js/components/select-field/headless-combobox-alpine.js');
    $livewire = file_get_contents(__DIR__.'/../../resources/js/components/select-field/headless-combobox-livewire.js');

    expect($alpine)
        ->toContain('resolveHeadlessBoundState')
        ->toContain('initialState,')
        ->toMatch('/if \(this\.hasDynamicSearchResults\) \{\s*this\.comboboxQuery = snapshot\.query/')
        ->and($livewire)
        ->toContain('resolveLivewire()');
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

it('loads select field stylesheet for enhanced select fields to coordinate ssr and alpine trigger', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');
    $headlessBlade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain('! $isNative')
        ->toContain("'component' => 'select-field'")
        ->and($headlessBlade)
        ->toContain('getHeadlessInitialOptionsForJs')
        ->toContain('fff-select-trigger-ssr');
});

it('renders select trigger ssr with x-load-src alpine mount when initial label exists', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');
    $mountBlade = file_get_contents(__DIR__.'/../../resources/views/components/lazy-alpine-mount.blade.php');

    expect($blade)
        ->toContain('$shouldDeferHeadlessAlpine = false')
        ->toContain('$headlessComponentKey = $getKey()')
        ->toContain('componentKey: @js($headlessComponentKey)')
        ->toContain(':mount-on-interaction="$shouldDeferHeadlessAlpine"')
        ->toContain(':wrap-slot="false"')
        ->toContain('fff-select-field__interactive')
        ->toContain('fff-select-dropdown-loading__spinner')
        ->toContain('x-load-src')
        ->not->toContain('selectModuleReady')
        ->not->toContain('fff-select-field-module-loaded');

    $loadStylesheet = file_get_contents(__DIR__.'/../../resources/views/partials/load-stylesheet.blade.php');
    $emitAssets = file_get_contents(__DIR__.'/../../resources/views/partials/emit-assets.blade.php');

    expect($loadStylesheet)->toContain('consumerComponent')
        ->and($emitAssets)->toContain('consumerAttributesFor');

    expect($mountBlade)->toContain('fff-lazy-alpine-gate')
        ->toContain('wrapSlot');
});

it('closes the x-load interactive root before lazy-alpine-mount ends so helper text stays outside input wrapper', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)->toMatch('/<\/template>\s*<\/div>\s*@if \(\$shouldDeferHeadlessAlpine\)/');
});

it('uses headless shell for enhanced select fields without filament select coordinator markup', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');

    expect($blade)
        ->toContain('select-field-headless')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('selectFormComponent({')
        ->not->toContain('data-fff-select-root')
        ->not->toContain('selectFieldPreload')
        ->not->toContain('window.bootSelectFieldPatches')
        ->not->toContain('requestAnimationFrame(applyPatches)');
});

it('wires filament select parity into the headless combobox partial', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');
    $optionBlade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless-option.blade.php');

    expect($blade)
        ->toContain('comboboxFilteredDropdownRows()')
        ->toContain('isReorderable')
        ->toContain('canOptionLabelsWrap')
        ->toContain('reorderSelectedChips')
        ->toContain('x-sortable-handle')
        ->toContain('fi-reorderable')
        ->toContain('select-field-headless-option')
        ->toContain('fff-select-dropdown-empty')
        ->toContain('shouldShowHeadlessSelectEmptyState')
        ->toContain('shouldShowHeadlessSelectSkeleton')
        ->toContain('fff-select-dropdown-loading__spinner')
        ->not->toContain('fff-select-dropdown-loading__row')
        ->toContain('searchDebounce: @js($getSearchDebounce())')
        ->toContain('optionsLimit: @js($getOptionsLimit())')
        ->toContain('searchableOptionFields: @js($field->getSearchableOptionFields())')
        ->toContain('livewireId: @js($this->getId())')
        ->toContain('getSelectMessagesForJs')
        ->toContain('selectNoOptionsIconHtml: @js($headlessSelectNoOptionsIconHtml)')
        ->toContain('selectNoResultsIconHtml: @js($headlessSelectNoResultsIconHtml)')
        ->toContain('selectEmptyStateHints: @js($field->getSelectEmptyStateHintsForJs())')
        ->toContain('userSelectEmptyStateHints: @js($isUserSelectField ? $field->getUserSelectEmptyStateHintsForJs() : [])')
        ->toContain('shouldShowHeadlessDropdownOptions()')
        ->toContain('fff-select-headless-options-root')
        ->toContain('fff-select-headless-dropdown-row')
        ->toContain('fff-select-headless-menu')
        ->toMatch('/x-for="row in comboboxFilteredDropdownRows\(\)"[\s\S]*fff-select-headless-dropdown-row[\s\S]*<template x-if="row\.type === \'section\' \|\| row\.type === \'group-header\'"/')
        ->not->toContain('fi-select-input-option-group')
        ->not->toContain('option in [row.option]')
        ->toContain(":key=\"row.key + ':' + (comboboxQuery ?? '')\"")
        ->toContain('id="{{ $id }}"')
        ->toContain('id="{{ $headlessSearchId }}"')
        ->toContain('id="{{ $headlessListboxId }}"')
        ->toContain('role="listbox"')
        ->toContain('aria-controls="{{ $headlessListboxId }}"')
        ->toContain("->except(['id'])")
        ->and($optionBlade)
        ->toContain('isHeadlessOptionDisabled')
        ->toContain('headlessOptionValue(row.option)')
        ->toContain('row.option');
});

it('marks disabled options in js payloads for disableOptionWhen', function () {
    $field = SelectField::make('status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ])
        ->disableOptionWhen(fn (string $value): bool => $value === 'published');

    $options = $field->getOptionsForJs();

    expect(collect($options)->firstWhere('value', 'draft')['isDisabled'] ?? null)->toBeFalse()
        ->and(collect($options)->firstWhere('value', 'published')['isDisabled'] ?? null)->toBeTrue();
});

it('styles disabled dropdown options with muted text color', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('--fff-select-option-disabled')
        ->toMatch('/\.fi-select-input-option\.fi-disabled[\s\S]*--fff-select-option-disabled/')
        ->and($css)
        ->toContain('--fff-select-option-disabled')
        ->toMatch('/\.fi-select-input-option\.fi-disabled[\s\S]*--fff-select-option-disabled/');
});

it('keeps disableOptionWhen selects on the client-side headless option list', function () {
    $field = SelectField::make('status')
        ->options([
            'draft' => 'Draft',
            'published' => 'Published',
        ])
        ->default('draft')
        ->disableOptionWhen(fn (string $value): bool => $value === 'published')
        ->searchable();

    expect($field->hasClientSideOptionList())->toBeTrue()
        ->and($field->hasDynamicOptions())->toBeFalse()
        ->and($field->hasDynamicSearchResults())->toBeFalse()
        ->and($field->getHeadlessInitialOptionsForJs())->not->toBeEmpty()
        ->and(collect($field->getHeadlessInitialOptionsForJs())->pluck('value')->all())->toContain('draft', 'published');
});

it('resolves translatable select empty state hints for js from package lang files', function () {
    app()->setLocale('pl');

    $field = SelectField::make('status')->options([]);

    expect($field->getSelectEmptyStateHintsForJs())
        ->toMatchArray([
            'pleaseWait' => 'Proszę czekać…',
            'minSearchLength' => 'Wpisz co najmniej 0 znaków, aby wyszukać.',
            'filterList' => 'Zacznij pisać, aby filtrować listę.',
            'tryDifferentSearch' => 'Spróbuj innej frazy wyszukiwania.',
            'noOptionsAvailable' => 'Obecnie nie ma dostępnych opcji.',
            'allOptionsSelected' => 'Wszystkie dostępne opcje są już wybrane.',
        ]);
});

it('resolves translatable select messages for js from filament lang files', function () {
    app()->setLocale('pl');

    $field = SelectField::make('status')->options(fn (): array => ['draft' => 'Draft']);

    expect($field->getSelectMessagesForJs())
        ->toMatchArray([
            'loading' => 'Wczytywanie...',
            'searching' => 'Szukanie...',
            'noOptions' => 'Brak dostępnych opcji.',
            'noMoreOptions' => 'Brak kolejnych opcji do wyboru.',
            'noSearchResults' => 'Żadne wyniki nie pasują do Twojego wyszukiwania.',
            'searchPrompt' => 'Zacznij pisać aby wyszukać...',
        ]);
});

it('keeps headless select runtime in the select field bundle', function () {
    $source = file_get_contents(__DIR__.'/../../resources/dist/components/select-field.js');
    $entry = file_get_contents(__DIR__.'/../../resources/js/components/select-field.js');

    expect($source)
        ->toContain('fffHeadlessSelectField')
        ->not->toContain('bootSelectFieldPatches')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('selectFieldPreload')
        ->and($entry)
        ->not->toContain('select-field-patches')
        ->toContain('fffHeadlessSelectField');
});

it('loads user select stylesheet only for user select fields', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');

    expect($blade)
        ->toContain('$isUserSelectField')
        ->toContain("'component' => 'user-select'");
});

it('styles grid layout trigger chips for dark mode in the select field bundle', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($css)
        ->toContain('--fff-select-trigger-chip-bg')
        ->toMatch('/\.dark\s+\.fff-select-field[\s\S]*--fff-select-trigger-chip-bg:#27272aeb/');
});

it('lets multiple select chips wrap and grow the trigger height', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('--fff-select-multiple-max-h')
        ->toContain('--fff-select-multiple-padding-block')
        ->toContain('--fff-select-chip-padding-inline-start')
        ->toContain('--fff-select-multiple-padding-inline-start')
        ->toContain('.fff-select-field--multiple .fi-select-input-value-badges-ctn')
        ->toContain('.fff-select-trigger-ssr--multiple .fi-select-input-value-badges-ctn')
        ->toMatch('/\.fff-select-field--multiple\s+\.fi-select-input-value-badges-ctn[\s\S]*flex-wrap/')
        ->not->toMatch('/\.fff-select-field--multiple\s+\.fi-select-input-ctn[\s\S]*max-height:\s*var\(--fff-select-min-h\)/')
        ->and($css)
        ->toContain('--fff-select-multiple-max-h')
        ->toContain('--fff-select-multiple-padding-block')
        ->toContain('--fff-select-chip-padding-inline-start')
        ->toContain('--fff-select-multiple-padding-inline-start')
        ->toMatch('/\.fff-select-field--multiple\s+\.fi-select-input-value-badges-ctn[\s\S]*flex-wrap/');
});

it('hides hydrated clear and chevron while the SSR trigger is visible', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('.fi-select-input-ctn:not(.fff-select-trigger-ssr)')
        ->toMatch('/\.fi-select-input:has\(\.fff-select-trigger-ssr:not\(\.is-replaced\)\)\s+\.fi-select-input-ctn:not\(\.fff-select-trigger-ssr\)[\s\S]*opacity:\s*0/')
        ->and($css)
        ->toMatch('/\.fi-select-input:has\(\.fff-select-trigger-ssr:not\(\.is-replaced\)\)\s+\.fi-select-input-ctn:not\(\.fff-select-trigger-ssr\)\{[^}]*opacity:0/');
});

it('aligns grid layout trigger start padding with multi-select chips', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('--fff-select-chip-padding-inline-start')
        ->toContain('.fff-select-field--layout-grid:not(.fff-select-field--item-card) .fi-select-input-btn')
        ->toContain('.fff-select-trigger-ssr--layout-grid .fff-select-trigger-ssr__btn')
        ->toContain(':has(.fff-select-option--trigger)')
        ->and($blade)
        ->toContain("'fff-select-trigger-ssr--layout-grid' => \$isGridLayout")
        ->and($css)
        ->toContain('--fff-select-chip-padding-inline-start')
        ->toMatch('/\.fff-select-field--layout-grid:not\(\.fff-select-field--item-card\)\s+\.fi-select-input-btn[\s\S]*padding-inline-start:var\(--fff-select-chip-padding-inline-start\)/')
        ->toMatch('/:has\(\.fff-select-option--trigger\)[\s\S]*padding-inline-start:var\(--fff-select-chip-padding-inline-start\)/');
});

it('packs rich list dropdown options with flex instead of a stretched grid', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('.fi-dropdown-panel.fff-select-dropdown-panel--layout-list .fff-select-option--list')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option--list\s*\{[^}]*display:\s*flex/')
        ->not->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option--list\s*\{[^}]*grid-template-columns/')
        ->and($css)
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option--list\{[^}]*display:flex/');
});

it('compacts rich list dropdown icons without a background tile', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option__icon\s*\{[\s\S]*background:\s*transparent/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option__icon\s*\{[\s\S]*padding:\s*0/')
        ->toMatch('/--fff-select-rich-list-icon-size:\s*1\.125rem/')
        ->toMatch('/stroke-width:\s*1\.25/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option__icon\s+\.fi-icon[\s\S]*height:\s*100%/')
        ->and($css)
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option__icon\{[\s\S]*background:(?:transparent|0 0|#0000)/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option__icon\{[\s\S]*padding:0/')
        ->toMatch('/--fff-select-rich-list-icon-size:1\.125rem/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--layout-list\s+\.fff-select-option__icon\s+\.fi-icon[\s\S]*?\{[\s\S]*?height:100%/');
});

it('scales multi-select chips with field size tokens', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('--fff-select-chip-font-size')
        ->toContain('--fff-select-chip-remove-size')
        ->toMatch('/\.fff-select-field--sm[\s\S]*--fff-select-chip-font-size:\s*0\.75rem/')
        ->toMatch('/\.fff-select-field--lg[\s\S]*--fff-select-chip-font-size:\s*0\.875rem/')
        ->and($blade)
        ->toContain('fi-badge fi-size-md')
        ->not->toContain("'fi-size-sm' => \$getSize() === 'sm'")
        ->and($css)
        ->toContain('--fff-select-chip-font-size')
        ->toMatch('/\.fff-select-field--sm\{[\s\S]*--fff-select-chip-font-size:\.75rem/')
        ->toMatch('/\.fff-select-field--lg\{[\s\S]*--fff-select-chip-font-size:\.875rem/')
        ->toMatch('/\.fi-select-input-value-badges-ctn\s+\.fi-badge\{[\s\S]*font-size:var\(--fff-select-chip-font-size\)/');
});

it('demos multi-select chips in small medium and large on the select playground', function () {
    $playground = app(SelectPlayground::class);
    $state = $playground->defaultState();

    $flatten = function (array $components) use (&$flatten): array {
        $out = [];

        foreach ($components as $component) {
            $out[] = $component;

            if (method_exists($component, 'getDefaultChildComponents')) {
                $out = [...$out, ...$flatten($component->getDefaultChildComponents())];
            }
        }

        return $out;
    };

    $fields = collect($flatten($playground->components()))
        ->filter(fn ($component) => $component instanceof SelectField)
        ->keyBy(fn ($field) => $field->getName());

    expect($state)
        ->toHaveKey('select__multiple_checklist')
        ->toHaveKey('select__disabled_options')
        ->toHaveKey('select__dynamic_options')
        ->toHaveKey('select__truncate_labels')
        ->toHaveKey('select__reorderable')
        ->toHaveKey('select__boolean')
        ->toHaveKey('select__multiple_sm')
        ->toHaveKey('select__multiple_md')
        ->toHaveKey('select__multiple_lg')
        ->toHaveKey('select__item_card')
        ->toHaveKey('select__inline_search')
        ->toHaveKey('select__inline_field_label')
        ->toHaveKey('select__not_clearable')
        ->toHaveKey('select__domain_affix')
        ->toHaveKey('select__dropdown_align_end')
        ->and($fields->keys()->all())
        ->toContain('select__multiple_checklist')
        ->toContain('select__disabled_options')
        ->toContain('select__dynamic_options')
        ->toContain('select__truncate_labels')
        ->toContain('select__reorderable')
        ->toContain('select__boolean')
        ->toContain('select__multiple_sm')
        ->toContain('select__multiple_md')
        ->toContain('select__multiple_lg')
        ->toContain('select__item_card')
        ->toContain('select__inline_search')
        ->toContain('select__inline_field_label')
        ->toContain('select__not_clearable')
        ->toContain('select__domain_affix')
        ->toContain('select__dropdown_align_end')
        ->and($fields['select__multiple_checklist']->isMultiple())->toBeTrue()
        ->and($fields['select__multiple_checklist']->shouldKeepSelectedOptionsInDropdown())->toBeTrue()
        ->and($fields['select__multiple']->shouldKeepSelectedOptionsInDropdown())->toBeFalse()
        ->and($fields['select__multiple_sm']->getSize())->toBe('sm')
        ->and($fields['select__multiple_sm']->isMultiple())->toBeTrue()
        ->and($fields['select__multiple_md']->getSize())->toBe('md')
        ->and($fields['select__multiple_lg']->getSize())->toBe('lg')
        ->and($fields['select__reorderable']->isReorderable())->toBeTrue()
        ->and($fields['select__truncate_labels']->canOptionLabelsWrap())->toBeFalse()
        ->and($fields['select__dynamic_options']->hasDynamicOptions())->toBeTrue()
        ->and($fields['select__item_card']->getVariant())->toBe('item-card')
        ->and($fields['select__inline_search']->hasInlineSearch())->toBeTrue()
        ->and($fields['select__inline_field_label']->hasInlineFieldLabel())->toBeTrue()
        ->and($fields['select__not_clearable']->isClearable())->toBeFalse()
        ->and($fields['select__dropdown_align_end']->getDropdownAlign())->toBe('end');
});

it('styles grid selected checkmarks with a circular badge in the select field bundle', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($css)
        ->toContain('--fff-select-grid-check-bg')
        ->toContain('--fff-select-selected-check-fg')
        ->toContain('.fff-select-field--layout-grid .fff-select-option-selected-check')
        ->toMatch('/\.fff-select-field--layout-grid\s+\.fff-select-option-selected-check[\s\S]*border-radius:(9999|3\.40282e38)px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-grid-check-bg:#fff/')
        ->toMatch('/\.dark\s+\.fff-select-field[\s\S]*--fff-select-grid-check-bg:#52525bfa/')
        ->toMatch('/\.dark\s+\.fi-dropdown-panel\.fff-select-dropdown-panel[\s\S]*--fff-select-selected-check-fg:#f4f4f5/')
        ->toMatch('/\.dark\s+\.fi-dropdown-panel\.fff-select-dropdown-panel[\s\S]*--fff-select-grid-check-bg:#fff/');
});

it('styles dark dropdown search input with glass backdrop in the select field bundle', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($css)
        ->toMatch('/\.dark\s+\.fi-dropdown-panel\.fff-select-dropdown-panel[\s\S]*--fff-select-menu-bg:#27272aeb/')
        ->toMatch('/\.dark\s+\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-search-ctn\s+\.fi-input[\s\S]*background-color:#40404573!important/')
        ->toMatch('/\.dark\s+\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-search-ctn\s+\.fi-input[\s\S]*-webkit-backdrop-filter:blur\(15px\)saturate\(2\.5\)/')
        ->toMatch('/\.dark\s+\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-search-ctn\s+\.fi-input[\s\S]*backdrop-filter:blur\(15px\)saturate\(2\.5\)/');
});

it('clips and ellipsizes non-wrapping single-select trigger labels in the select field bundle', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');
    $headless = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($source)
        ->toContain(':not(.fff-select-field--rich-list-trigger) .fi-select-input-value-label')
        ->toContain('text-overflow: ellipsis !important')
        ->toContain('-webkit-line-clamp: 2')
        ->toContain('overflow: hidden')
        ->toContain('max-height: var(--fff-select-min-h)')
        ->toContain('--tw-translate-y: -50%')
        ->toContain('--fff-select-option-check-gutter')
        ->toContain('padding-inline-end: var(--fff-select-option-check-gutter, 1.75rem) !important')
        ->toContain('--fff-overlay-sheet-pad-bottom')
        ->toContain('--fff-select-option-min-h: 2.75rem')
        ->toMatch('/fi-select-input-ctn-option-labels-not-wrapped[^{]*\{[^}]*align-items:\s*center\s*!important/')
        ->toMatch('/\.fi-select-input-value-remove-btn[\s\S]*transform:\s*none/')
        ->toMatch('/\.fi-select-input-value-remove-btn[\s\S]*mask-image:\s*none/')
        ->toMatch('/\.fi-select-input-value-remove-btn[\s\S]*background-color:\s*transparent/')
        ->toMatch('/\.fff-select-option-selected-row\s*\{[^}]*position:\s*static/');

    expect($css)
        ->toContain('fi-input-wrp.fff-select-field:not(.fff-select-field--multiple):not(.fff-select-field--item-card){overflow:hidden}')
        ->toContain('text-overflow:ellipsis!important')
        ->toContain('white-space:nowrap!important')
        ->toContain('-webkit-line-clamp:2')
        ->toContain('--tw-translate-y:-50%')
        ->toContain('--fff-select-option-check-gutter')
        ->toContain('padding-inline-end:var(--fff-select-option-check-gutter,1.75rem)!important')
        ->toContain('--fff-overlay-sheet-pad-bottom')
        ->toContain('--fff-select-option-min-h:2.75rem')
        ->toMatch('/\.fi-select-input-value-remove-btn[^{]*\{[^}]*transform:none/')
        ->toMatch('/\.fi-select-input-value-remove-btn[^{]*\{[^}]*mask-image:none/')
        ->toMatch('/\.fi-select-input-value-remove-btn[^{]*\{[^}]*background-color:(?:transparent|#0000)/');

    expect($blade)->not->toContain('<style');

    expect($headless)
        ->toContain("'fi-select-input-ctn-option-labels-not-wrapped' => ! \$canOptionLabelsWrap");
});

it('keeps hydrated select triggers clickable after Alpine handoff', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');

    expect($source)
        ->toContain("[data-fff-select-attached='true'] .fi-select-input-ctn:not(.fff-select-trigger-ssr)")
        ->toContain("[data-fff-select-attached='true'] > .fff-select-trigger-ssr:not(.is-replaced)")
        ->toContain('pointer-events: auto');

    expect($css)
        ->toContain('[data-fff-select-attached=true] .fi-select-input-ctn:not(.fff-select-trigger-ssr){opacity:1;pointer-events:auto}')
        ->toContain('[data-fff-select-attached=true]>.fff-select-trigger-ssr:not(.is-replaced)')
        ->toContain('pointer-events:none;display:none');
});

it('styles the select dropdown shell like a Spectrum list box', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($css)
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-menu-bg:#ffffffe6/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-menu-hover:#ebebec/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-menu-selected:transparent/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-menu-shadow:0 2px 8px 0 #0000000f, 0 -6px 12px 0 #00000008, 0 14px 28px 0 #00000014/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-menu-radius:1\.5rem/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-menu-padding:calc\(\.25rem \* 1\.5\)/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-menu-scrollbar-inset:2px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--overlay-scroll\s+\.fi-select-input-options-ctn[\s\S]*scrollbar-width:none/')
        ->toMatch('/\.fff-select-dropdown-scrollbar\{[\s\S]*inset-inline-end:calc\(\s*var\(--fff-select-overlay-scrollbar-inset/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-radius:1rem/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-min-h:2\.25rem/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-padding-inline:10px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-padding-block:6px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fi-select-input-option>span\{[\s\S]*min-height:var\(--fff-select-option-min-h/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fi-select-input-option\.fi-selected>span\{[\s\S]*background:var\(--fff-select-menu-selected,transparent\)/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:has\(\.fi-select-input-search-ctn\)\{[\s\S]*padding-top:8px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:has\(\.fi-select-input-search-ctn\)\{[\s\S]*padding-bottom:0/')
        ->toContain('.fff-select-dropdown-list-end-spacer')
        ->toMatch('/\.fff-select-dropdown-list-end-spacer\{[\s\S]*height:4px/')
        ->toMatch('/:has\(\.fi-select-input-search-ctn\)\s+\.fff-select-dropdown-list-end-spacer\{[\s\S]*height:4px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-search-ctn\{[\s\S]*padding-block:4px!important/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-search-ctn\{[\s\S]*padding-inline:4px!important/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-dropdown-list>\*\+\*[\s\S]*margin-top:4px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fff-select-option-selected-check__svg\{[\s\S]*stroke-dasharray:22/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fff-select-option-selected-check\[data-visible=true\]\s+\.fff-select-option-selected-check__svg\{[\s\S]*stroke-dashoffset:0/');
});

it('fades overflowing select dropdown edges with a scroll-aware mask', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');
    $alpine = file_get_contents(__DIR__.'/../../resources/js/components/select-field/headless-combobox-alpine.js');
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($source)
        ->toContain('--fff-select-scroll-fade-size: 6px')
        ->toContain("[data-scroll-fade='top']")
        ->toContain("[data-scroll-fade='bottom']")
        ->toContain("[data-scroll-fade='both']")
        ->toContain('--fff-select-overlay-scrollbar-size: 6px')
        ->toContain("[data-active='true']")
        ->toContain('mask-image: linear-gradient(var(--fff-select-scroll-fade-gradient))')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s*\{[\s\S]*overflow:\s*hidden\s*!important/')
        ->and($css)
        ->toContain('--fff-select-scroll-fade-size:6px')
        ->toContain('--fff-select-overlay-scrollbar-size:6px')
        ->toContain('overflow:hidden!important')
        ->toContain('[data-scroll-fade=top]')
        ->toContain('[data-scroll-fade=both]')
        ->and($alpine)
        ->toContain('updateVerticalScrollFade')
        ->toContain('bindDropdownScrollFadeObserver')
        ->toContain('syncOverlayScrollbar')
        ->toContain('onHeadlessOptionsScroll')
        ->and($blade)
        ->toContain('fff-select-dropdown-panel--overlay-scroll')
        ->toContain('data-active="false"')
        ->toContain('fff-select-dropdown-scrollbar__thumb');
});

it('styles grouped option headers as muted labels with even section separators', function () {
    $source = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($source)
        ->toContain('--fff-select-group-header-font-size')
        ->toContain('--fff-select-group-header-padding-inline: 10px')
        ->toContain('--fff-select-group-header-padding-block-start: 6px')
        ->toContain('--fff-select-group-header-padding-block-end: 4px')
        ->toContain('--fff-select-group-header-line-height: calc(1 / 0.75)')
        ->toContain('fff-select-dropdown-scrollbar')
        ->toContain('margin-block: 4px 0')
        ->toContain('margin-inline: var(--fff-select-option-padding-inline, 10px)')
        ->toContain(".fff-select-headless-dropdown-row[data-row-type='separator']")
        ->toContain('text-transform: none')
        ->toContain('.fi-dropdown-panel.fff-select-dropdown-panel')
        ->and($css)
        ->toContain('--fff-select-group-header-padding-inline:10px')
        ->toContain('--fff-select-overlay-scrollbar-inset:3px');

    expect($source)
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s*\{[\s\S]*--fff-select-group-label/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s*\{[\s\S]*--fff-select-group-divider/')
        ->toMatch('/\.fff-select-option-group-separator\s*\{[\s\S]*margin-inline:\s*var\(--fff-select-option-padding-inline/')
        ->toMatch('/\.fff-select-option-group-separator\s*\{[\s\S]*margin-block:\s*4px 0/')
        ->toMatch('/\.fff-select-headless-options-root\s*>\s*\*\s*\+\s*\*[\s\S]*margin-top:\s*4px/');
});

it('styles inline prefix and suffix affixes with internal vertical dividers in the select field bundle', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($css)
        ->toContain('--fff-select-affix-divider')
        ->toMatch('/\.fi-input-wrp\.fff-select-field\s+\.fi-input-wrp-prefix\.fi-input-wrp-prefix-has-content[\s\S]*border-inline-end:1px solid var\(--fff-select-affix-divider/')
        ->toMatch('/\.fi-input-wrp\.fff-select-field\s+\.fi-input-wrp-suffix[\s\S]*border-inline-start:1px solid var\(--fff-select-affix-divider/')
        ->toMatch('/\.dark\s+\.fff-select-field[\s\S]*--fff-select-affix-divider:#ffffff1a/');
});

it('keeps item card ssr visible until displayReady and adds inline field label value gap', function () {
    $css = file_get_contents(__DIR__.'/../../resources/css/components/select-field.css');
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');
    $alpine = file_get_contents(__DIR__.'/../../resources/js/components/select-field/headless-combobox-alpine.js');
    $wrapperBlade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');

    expect($blade)
        ->toContain('fff-select-item-card-ssr')
        ->toContain('fff-select-item-card-trigger__chevron')
        ->toContain('$isItemCardVariant');

    expect($alpine)->toContain('markHeadlessDisplayReady');

    expect($wrapperBlade)->toContain('$showItemCardTriggerSsr');

    expect($css)
        ->toContain('--fff-select-inline-field-label-value-gap')
        ->toContain('.fff-select-field--inline-field-label .fi-select-input-ctn-clearable .fi-select-input-value-ctn')
        ->toContain('margin-inline-end: var(--fff-select-inline-field-label-value-gap)')
        ->toContain('.fff-select-field__shell--headless > .fff-lazy-alpine-gate')
        ->toContain('pointer-events: auto')
        ->toContain('.fi-input-wrp.fff-select-field--item-card .fff-select-item-card-ssr.is-replaced')
        ->toContain('.fi-input-wrp.fff-select-field--item-card .fi-select-input:has(.fff-select-item-card-ssr:not(.is-replaced)) .fff-select-item-card-ssr:not(.is-replaced)')
        ->toContain('.fi-input-wrp.fff-select-field--item-card .fi-select-input:has(.fff-select-item-card-ssr:not(.is-replaced)) .fi-select-input-btn .fi-select-input-value-ctn')
        ->toContain('.fff-select-item-card-trigger__chevron')
        ->toContain('--fff-select-value-label: var(--item-card-group-title, var(--fff-select-text))')
        ->toContain('visibility: hidden');
});

it('wires dependsOn as a closure options resolver', function () {
    $field = SelectField::make('region')
        ->dependsOn('country', fn (?string $country): array => match ($country) {
            'us' => ['ca' => 'California'],
            default => [],
        });

    $optionsProperty = new ReflectionProperty($field, 'options');
    $optionsProperty->setAccessible(true);

    expect($optionsProperty->getValue($field))->toBeInstanceOf(Closure::class);
});

it('exposes Filament select messages optionsLimit maxItems and create option actions to headless', function () {
    $field = SelectField::make('author_id')
        ->searchable()
        ->getSearchResultsUsing(fn (): array => ['1' => 'Jane'])
        ->getOptionLabelUsing(fn ($value): ?string => $value === '1' ? 'Jane' : null)
        ->loadingMessage('Loading authors...')
        ->searchingMessage('Searching authors...')
        ->noSearchResultsMessage('No authors found.')
        ->noOptionsMessage('No authors available.')
        ->searchPrompt('Search authors')
        ->searchDebounce(500)
        ->optionsLimit(20)
        ->createOptionForm([
            TextInput::make('name')->required(),
        ])
        ->createOptionUsing(fn (array $data): string => 'created');

    $messages = $field->getSelectMessagesForJs();

    expect($messages)
        ->toMatchArray([
            'loading' => 'Loading authors...',
            'searching' => 'Searching authors...',
            'noOptions' => 'No authors available.',
            'noMoreOptions' => 'No more options to choose.',
            'noSearchResults' => 'No authors found.',
            'searchPrompt' => 'Search authors',
        ])
        ->and($field->getOptionsLimit())->toBe(20)
        ->and($field->getSearchDebounce())->toBe(500)
        ->and($field->hasCreateOptionActionFormSchema())->toBeTrue();
});

it('wires maxItems into the headless alpine payload', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain('maxItems: @js($getMaxItems())')
        ->toContain('maxItemsMessage: @js($getMaxItemsMessage())')
        ->toContain('fff-select-max-items-message')
        ->toContain('optionsLimit: @js($getOptionsLimit())');
});

it('marks disabled options from disabledOptions helper', function () {
    $field = SelectField::make('animal')
        ->options([
            'dog' => 'Dog',
            'cat' => 'Cat',
        ])
        ->disabledOptions(['cat']);

    $options = $field->getOptionsForJs();

    expect(collect($options)->firstWhere('value', 'cat')['isDisabled'] ?? null)->toBeTrue()
        ->and(collect($options)->firstWhere('value', 'dog')['isDisabled'] ?? null)->toBeFalse();
});

it('exposes paginated async search configuration for js', function () {
    $field = SelectField::make('character')
        ->searchable()
        ->paginatedSearchResults()
        ->searchResultsPageSize(2)
        ->getSearchResultsPageUsing(fn (): array => [
            'items' => [],
            'cursor' => null,
            'hasMore' => false,
        ]);

    expect($field->hasDynamicSearchResults())->toBeTrue()
        ->and($field->hasPaginatedSearchResults())->toBeTrue()
        ->and($field->getSearchResultsPageSize())->toBe(2);
});

it('defaults option group separators to enabled', function () {
    $field = SelectField::make('country')->options([]);

    expect($field->hasOptionGroupSeparators())->toBeTrue();

    $field->optionGroupSeparators(false);

    expect($field->hasOptionGroupSeparators())->toBeFalse();
});

it('renders grouped separators and load-more hooks in headless blade', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/select-field-headless.blade.php');

    expect($blade)
        ->toContain("row.type === 'separator'")
        ->toContain('fff-select-option-group-separator')
        ->toContain('headlessLoadMoreSentinel')
        ->toContain('shouldShowHeadlessTriggerLoading()')
        ->toContain('hasPaginatedSearchResults')
        ->toContain('optionGroupSeparators');
});

it('renders custom option views through optionView api', function () {
    $field = SelectField::make('user')
        ->options([
            'fred' => [
                'label' => 'Fred',
                'description' => 'fred@example.com',
            ],
        ])
        ->optionView('filament-flex-fields::forms.components.partials.select-option-view-user');

    expect($field->hasOptionView())->toBeTrue()
        ->and($field->usesRichOptionHtml())->toBeTrue()
        ->and($field->isHtmlAllowed())->toBeTrue();

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])
        ->toContain('fff-select-option-view-user')
        ->toContain('Fred')
        ->toContain('fff-select-option__description')
        ->and($options[0]['triggerLabel'])
        ->toContain('fff-select-option--trigger')
        ->toContain('Fred')
        ->not->toContain('fff-select-option__description');
});

it('supports separate optionTriggerView for compact trigger html', function () {
    $field = SelectField::make('user')
        ->options([
            'fred' => [
                'label' => 'Fred',
                'description' => 'fred@example.com',
            ],
        ])
        ->optionView(fn (array $option): string => '<span class="fff-custom-list">'.$option['label'].' · '.$option['description'].'</span>')
        ->optionTriggerView(fn (array $option): string => '<span class="fff-custom-trigger">'.$option['label'].'</span>');

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toContain('fff-custom-list')
        ->and($options[0]['label'])->toContain('Fred')
        ->and($options[0]['triggerLabel'])->toContain('fff-custom-trigger')
        ->and($options[0]['triggerLabel'])->not->toContain('fff-custom-list');
});

it('uses full list layout in trigger when richListTriggerDisplay is enabled', function () {
    $field = SelectField::make('user')
        ->options([
            'fred' => [
                'label' => 'Fred',
                'description' => 'fred@example.com',
            ],
        ])
        ->optionView('filament-flex-fields::forms.components.partials.select-option-view-user')
        ->richListTriggerDisplay()
        ->default('fred');

    $trigger = $field->getInitialTriggerLabel();

    expect($field->shouldUseRichListTriggerDisplay())->toBeTrue()
        ->and($field->getWrapperClasses())->toHaveKey('fff-select-field--rich-list-trigger')
        ->and($trigger)->toContain('fff-select-option--list')
        ->and($trigger)->toContain('fff-select-option__description');
});

it('merges optionViewData into custom option views', function () {
    $field = SelectField::make('user')
        ->options(['fred' => ['label' => 'Fred', 'description' => 'fred@example.com']])
        ->optionViewData(['accent' => 'violet'])
        ->optionView(fn (array $option, string $layout, string $accent): string => '<span class="fff-accent-'.$accent.'">'.$option['label'].'</span>');

    $options = $field->getOptionsForJs();

    expect($options[0]['label'])->toContain('fff-accent-violet');
});

it('transforms ten thousand plain options within a reasonable time budget', function () {
    $options = [];

    for ($index = 1; $index <= 10_000; $index++) {
        $options['opt_'.$index] = 'Option '.str_pad((string) $index, 5, '0', STR_PAD_LEFT);
    }

    $field = SelectField::make('scale')->options($options);

    $start = hrtime(true);
    $transformed = $field->getOptionsForJs();
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

    expect($transformed)->toHaveCount(10_000)
        ->and($elapsedMs)->toBeLessThan(500);
});
