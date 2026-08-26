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
    $normalize = (new \ReflectionClass($field))->getMethod('normalizeOption');
    $normalize->setAccessible(true);
    $render = (new \ReflectionClass($field))->getMethod('renderRichOptionLabel');
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
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');

    expect($blade)
        ->toContain("'keepSelectedOptionsInDropdown' => \$field->shouldKeepSelectedOptionsInDropdown()");
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

it('loads select field stylesheet for enhanced select fields to coordinate ssr and alpine trigger', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');

    expect($blade)
        ->toContain('! $isNative')
        ->toContain("'component' => 'select-field'")
        ->toContain('fff-select-trigger-ssr');
});

it('renders select trigger ssr with eager lazy alpine mount when initial label exists', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');

    expect($blade)
        ->toContain(':eager="$showInitialTriggerSsr"')
        ->toContain(':mount-immediately="$isDisabled || $showInitialTriggerSsr"')
        ->toContain('modulepreload')
        ->toContain("getAlpineComponentSrc('select-field'");
});

it('uses coordinator shell for select field alpine patching without window globals', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');

    expect($blade)
        ->toContain('fffSelectFieldCoordinator')
        ->toContain('selectFormComponent({')
        ->toContain('data-fff-select-root')
        ->not->toContain('selectFieldPreload')
        ->not->toContain('window.bootSelectFieldPatches')
        ->not->toContain('requestAnimationFrame(applyPatches)');
});

it('keeps select field patch bootstrapping inside the coordinator module without window globals', function () {
    $source = file_get_contents(__DIR__.'/../../resources/dist/components/select-field.js');

    expect($source)
        ->toContain('bootSelectFieldPatches')
        ->toContain('data-fff-select-root')
        ->toContain('fff-select-coordinator-attach-failed')
        ->toContain('fffSelectAttached')
        ->not->toContain('window.bootSelectFieldPatches')
        ->not->toContain('selectFieldPreload');
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
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');
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
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/select-field.blade.php');
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
    $playground = app(\Bjanczak\FilamentFlexFields\Support\Playground\SelectPlayground::class);
    $state = $playground->defaultState();
    $section = collect($playground->components())
        ->first(fn ($component) => $component instanceof \Filament\Schemas\Components\Section);

    expect($section)->not->toBeNull();

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

    $fields = collect($flatten($section->getDefaultChildComponents()))
        ->filter(fn ($component) => $component instanceof \Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField)
        ->keyBy(fn ($field) => $field->getName());

    expect($state)
        ->toHaveKey('select__multiple_checklist')
        ->toHaveKey('select__multiple_sm')
        ->toHaveKey('select__multiple_md')
        ->toHaveKey('select__multiple_lg')
        ->and($fields->keys()->all())
        ->toContain('select__multiple_checklist')
        ->toContain('select__multiple_sm')
        ->toContain('select__multiple_md')
        ->toContain('select__multiple_lg')
        ->and($fields['select__multiple_checklist']->isMultiple())->toBeTrue()
        ->and($fields['select__multiple_checklist']->shouldKeepSelectedOptionsInDropdown())->toBeTrue()
        ->and($fields['select__multiple']->shouldKeepSelectedOptionsInDropdown())->toBeFalse()
        ->and($fields['select__multiple_sm']->getSize())->toBe('sm')
        ->and($fields['select__multiple_sm']->isMultiple())->toBeTrue()
        ->and($fields['select__multiple_md']->getSize())->toBe('md')
        ->and($fields['select__multiple_lg']->getSize())->toBe('lg');
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
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel--scrollable\s+\.fi-dropdown-list\{[\s\S]*margin-inline-end:calc\(var\(--fff-select-menu-scrollbar-inset/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-radius:1rem/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-min-h:2\.25rem/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-padding-inline:10px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\{[\s\S]*--fff-select-option-padding-block:6px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fi-select-input-option>span\{[\s\S]*min-height:var\(--fff-select-option-min-h/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fi-select-input-option\.fi-selected>span\{[\s\S]*background:var\(--fff-select-menu-selected,transparent\)/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:has\(\.fi-select-input-search-ctn\)\{[\s\S]*padding-top:8px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-search-ctn\{[\s\S]*padding-block:4px!important/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-search-ctn\{[\s\S]*padding-inline:4px!important/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-dropdown-list>\*\+\*[\s\S]*margin-top:4px/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fff-select-option-selected-check__svg\{[\s\S]*stroke-dasharray:22/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel:not\(\.fff-select-dropdown-panel--layout-grid\)\s+\.fff-select-option-selected-check\[data-visible=true\]\s+\.fff-select-option-selected-check__svg\{[\s\S]*stroke-dashoffset:0/');
});

it('styles grouped option headers distinctly from selectable options in the select field bundle', function () {
    $css = file_get_contents(__DIR__.'/../../resources/dist/css/select-field.css');

    expect($css)
        ->toContain('--fff-select-group-label')
        ->toContain('.fi-dropdown-panel.fff-select-dropdown-panel .fi-select-input-option-group .fi-dropdown-header')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-option-group\s+\.fi-dropdown-header[\s\S]*text-transform:uppercase/')
        ->toMatch('/\.fi-dropdown-panel\.fff-select-dropdown-panel\s+\.fi-select-input-option-group:not\(:first-child\)\s+\.fi-dropdown-header[\s\S]*border-top:1px solid var\(--fff-select-group-divider\)/');
});
