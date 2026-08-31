<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\ChoiceCardsFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexChecklistFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexRadiolistFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\MatrixChoiceFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SegmentControlFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SelectFieldConfigurator;

it('normalizes studio list option rows for flex checklist', function (): void {
    $field = (new FlexChecklistFieldConfigurator)->configureFlexChecklistField(
        FlexChecklist::make('files'),
        [
            'options' => [
                ['value' => 'docs', 'label' => 'Documents', 'description' => 'Shared folder', 'icon' => 'folder'],
                ['value' => 'budget', 'label' => 'Budget'],
            ],
        ],
    );

    $options = $field->getNormalizedOptions();

    expect($options)->toHaveKeys(['docs', 'budget'])
        ->and($options['docs']['label'])->toBe('Documents')
        ->and($options['docs']['description'])->toBe('Shared folder')
        ->and($options['docs']['icon'])->toBe('folder')
        ->and($options['budget']['label'])->toBe('Budget');
});

it('normalizes studio list option rows for flex radiolist', function (): void {
    $field = (new FlexRadiolistFieldConfigurator)->configureFlexRadiolistField(
        FlexRadiolist::make('delivery'),
        [
            'options' => [
                ['value' => 'express', 'label' => 'Express', 'description' => '2-5 days'],
            ],
            'variant' => 'default',
        ],
    );

    expect($field->getNormalizedOptions()['express']['label'])->toBe('Express')
        ->and($field->getNormalizedOptions()['express']['description'])->toBe('2-5 days');
});

it('normalizes studio list option rows for segment control and choice cards', function (): void {
    $segment = (new SegmentControlFieldConfigurator)->configureSegmentControlField(
        SegmentControl::make('view'),
        [
            'options' => [
                ['value' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout'],
            ],
            'separators' => false,
            'full_width' => true,
        ],
    );

    $cards = (new ChoiceCardsFieldConfigurator)->configureChoiceCardsField(
        ChoiceCards::make('plan'),
        [
            'options' => [
                ['value' => 'pro', 'label' => 'Pro', 'description' => 'Teams', 'price' => '$20'],
            ],
            'grid_columns' => '3',
        ],
    );

    expect($segment->getNormalizedOptions()['dashboard']['label'])->toBe('Dashboard')
        ->and($segment->getNormalizedOptions()['dashboard']['icon'])->toBe('layout')
        ->and($segment->isFullWidth())->toBeTrue()
        ->and($segment->hasSeparators())->toBeFalse()
        ->and($cards->getNormalizedOptions()['pro']['label'])->toBe('Pro')
        ->and($cards->getNormalizedOptions()['pro']['price'])->toBe('$20')
        ->and($cards->getGridColumnConfig()['default'])->toBe(3);
});

it('preserves string label maps for choice cards form builder config', function (): void {
    $cards = (new ChoiceCardsFieldConfigurator)->configureChoiceCardsField(
        ChoiceCards::make('plan'),
        [
            'options' => [
                'starter' => 'Starter',
                'pro' => 'Pro',
            ],
        ],
    );

    expect($cards->getNormalizedOptions())->toHaveKeys(['starter', 'pro'])
        ->and($cards->getNormalizedOptions()['starter']['label'])->toBe('Starter');
});

it('normalizes rich card option rows via RichOptionSchemaV2 profile', function (): void {
    $cards = (new ChoiceCardsFieldConfigurator)->configureChoiceCardsField(
        ChoiceCards::make('plan'),
        [
            'options' => [
                ['value' => 'starter', 'label' => 'Starter'],
                ['value' => 'pro', 'label' => 'Pro', 'description' => 'Teams', 'icon' => 'users', 'price' => '$20'],
            ],
        ],
    );

    expect($cards->getNormalizedOptions()['starter']['label'])->toBe('Starter')
        ->and($cards->getNormalizedOptions()['pro']['description'])->toBe('Teams')
        ->and($cards->getNormalizedOptions()['pro']['icon'])->toBe('users')
        ->and($cards->getNormalizedOptions()['pro']['price'])->toBe('$20');
});

it('normalizes studio matrix row and column lists', function (): void {
    $field = (new MatrixChoiceFieldConfigurator)->configureMatrixChoiceField(
        MatrixChoiceField::make('scores'),
        [
            'rows' => ['Quality', 'Speed'],
            'columns' => ['1', '2', '3'],
            'mode' => 'checkbox',
        ],
    );

    expect($field->getNormalizedRows())->toHaveKeys(['Quality', 'Speed'])
        ->and($field->getNormalizedColumns())->toHaveKeys(['1', '2', '3']);
});

it('normalizes studio list option rows for select so values match fill validation keys', function (): void {
    $field = (new SelectFieldConfigurator)->configureSelectField(
        SelectField::make('plan'),
        [
            'options' => [
                ['value' => 'starter', 'label' => 'Starter'],
                ['value' => 'pro', 'label' => 'Pro'],
            ],
        ],
    );

    $options = $field->getOptions();

    expect($options)->toBe([
        'starter' => 'Starter',
        'pro' => 'Pro',
    ]);
});
