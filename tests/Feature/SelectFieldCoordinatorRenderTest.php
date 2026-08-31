<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Filament\Schemas\Components\Section;
use Livewire\Livewire;

it('renders headless combobox shell for static select fields by default', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('status')
            ->options([
                'draft' => 'Draft',
                'published' => 'Published',
            ]),
        SelectField::make('technologies')
            ->options([
                'tailwind' => 'Tailwind CSS',
                'laravel' => 'Laravel',
            ])
            ->multiple()
            ->searchable(),
        SelectField::make('theme')
            ->options([
                'sky' => 'Sky',
                'mint' => 'Mint',
            ])
            ->optionLayout('grid')
            ->richOptions(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fffHeadlessSelectField')
        ->toContain('fff-select-field__shell--headless')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.status["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.technologies["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.theme["\']?/')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('data-fff-select-root')
        ->not->toContain('selectFormComponent({');
});

it('renders user select through the headless combobox runtime', function (): void {
    TestableTranslatableForm::$formSchema = [
        UserSelect::make('assignee')
            ->options([
                'jane' => [
                    'label' => 'Jane Cooper',
                    'description' => 'jane@example.com',
                    'verified' => true,
                ],
            ])
            ->searchable(),
        UserSelect::make('members')
            ->options([
                'alex' => [
                    'label' => 'Alex Rivera',
                    'description' => 'alex@example.com',
                    'verified' => false,
                ],
            ])
            ->multiple()
            ->searchable(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fff-select-field__shell--headless')
        ->toContain('fffHeadlessSelectField')
        ->toContain('isUserSelectField')
        ->toContain('fff-user-select__selected-tags')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.assignee["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.members["\']?/')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('data-fff-select-root');
});

it('renders rich option selects through the headless runtime', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('theme')
            ->options([
                'sky' => 'Sky',
                'mint' => 'Mint',
            ])
            ->optionLayout('grid')
            ->richOptions()
            ->searchable(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fffHeadlessSelectField')
        ->toContain('fff-select-field__shell--headless')
        ->not->toContain('fffSelectFieldCoordinator')
        ->not->toContain('data-fff-select-root')
        ->not->toContain('selectFormComponent({');
});

it('renders async search selects through the headless runtime', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('author')
            ->searchable()
            ->getSearchResultsUsing(fn (): array => ['1' => 'Jane']),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('fffHeadlessSelectField')
        ->toContain('hasDynamicSearchResults: true')
        ->toContain('componentKey')
        ->not->toContain('selectFormComponent({');
});

it('renders closure option selects as dynamic options without async search', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('status')
            ->options(fn (): array => [
                'draft' => 'Draft',
                'published' => 'Published',
            ])
            ->default('published')
            ->searchable(),
    ];

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect($html)
        ->toContain('hasDynamicOptions: true')
        ->toContain('hasDynamicSearchResults: false')
        ->toContain('options: []')
        ->toContain('componentKey');
});

it('fetches closure based options through callSchemaComponentMethod', function (): void {
    TestableTranslatableForm::$formSchema = [
        SelectField::make('status')
            ->options(fn (): array => [
                'draft' => 'Draft',
                'published' => 'Published',
            ])
            ->default('published')
            ->searchable(),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->fillForm(['status' => 'published']);

    $field = $livewire->instance()
        ->getSchema('form')
        ->getComponentByStatePath('status');

    expect($field)->toBeInstanceOf(SelectField::class);

    $componentKey = $field->getKey();

    expect($componentKey)->toBeString()->toContain('.');

    $options = $livewire->instance()->callSchemaComponentMethod($componentKey, 'getOptionsForJs');

    expect($options)->toBeArray()->not->toBeEmpty()
        ->and(collect($options)->pluck('value')->all())->toContain('draft', 'published');
});

it('fetches playground dynamic options through callSchemaComponentMethod', function (): void {
    $statusOptions = [
        'draft' => 'Draft',
        'reviewing' => 'Reviewing',
        'published' => 'Published',
    ];

    TestableTranslatableForm::$formSchema = [
        Section::make('SelectField')->schema([
            SelectField::make('select__dynamic_options')
                ->options(fn (): array => $statusOptions)
                ->default('published')
                ->searchable(),
        ]),
    ];

    $livewire = Livewire::test(TestableTranslatableForm::class)
        ->fillForm(['select__dynamic_options' => 'published']);

    $field = $livewire->instance()
        ->getSchema('form')
        ->getComponentByStatePath('select__dynamic_options');

    expect($field)->toBeInstanceOf(SelectField::class)
        ->and($field->hasDynamicOptions())->toBeTrue()
        ->and($field->hasDynamicSearchResults())->toBeFalse();

    $componentKey = $field->getKey();

    expect($componentKey)->toBeString()->toContain('.');

    $options = $livewire->instance()->callSchemaComponentMethod($componentKey, 'getOptionsForJs');

    expect($options)->toBeArray()->not->toBeEmpty()
        ->and(collect($options)->pluck('value')->all())->toContain('published');

    $html = $livewire->html(false);

    expect($html)
        ->toContain('hasDynamicOptions: true')
        ->toContain('hasDynamicSearchResults: false')
        ->toContain('select__dynamic_options')
        ->not->toContain('componentKey: null');
});
