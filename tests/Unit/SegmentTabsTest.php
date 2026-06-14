<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\SegmentTabs\SegmentTab;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;

it('exposes segment tabs configuration via fluent api', function () {
    $tabs = SegmentTabs::make('Settings')
        ->variant('ghost')
        ->color('primary')
        ->separators(false)
        ->fullWidth()
        ->iconOnly()
        ->expandSelectedLabel()
        ->activeTab(2)
        ->persistTab()
        ->tabs([
            SegmentTab::make('General')
                ->icon(GravityIcon::Person)
                ->tooltip('General settings')
                ->schema([
                    FlexTextInput::make('name'),
                ]),
            SegmentTab::make('Advanced')
                ->schema([
                    FlexTextInput::make('api_key'),
                ]),
        ]);

    expect($tabs->getLabel())->toBe('Settings')
        ->and($tabs->getVariant())->toBe('ghost')
        ->and($tabs->getColor())->toBe('primary')
        ->and($tabs->hasSeparators())->toBeFalse()
        ->and($tabs->isFullWidth())->toBeTrue()
        ->and($tabs->isIconOnly())->toBeTrue()
        ->and($tabs->shouldExpandSelectedLabel())->toBeTrue()
        ->and($tabs->getActiveTab())->toBe(2)
        ->and($tabs->isTabPersisted())->toBeTrue();

    $general = SegmentTab::make('General')
        ->icon(GravityIcon::Person)
        ->tooltip('General settings')
        ->schema([
            FlexTextInput::make('name'),
        ]);

    expect($general->getLabel())->toBe('General')
        ->and($general->getIcon())->toBe(GravityIcon::Person)
        ->and($general->getTooltip())->toBe('General settings')
        ->and($general->canConcealComponents())->toBeTrue();
});

it('defaults ghost variant color to primary for segment tabs', function () {
    $tabs = SegmentTabs::make('Billing')
        ->variant('ghost')
        ->tabs([
            SegmentTab::make('Monthly')->schema([]),
            SegmentTab::make('Yearly')->schema([]),
        ]);

    expect($tabs->getColor())->toBe('primary');
});

it('renders only the active tab panel before alpine hydration', function () {
    $panel = file_get_contents(__DIR__.'/../../resources/views/schemas/components/segment-tabs/segment-tab.blade.php');
    $css = file_get_contents(__DIR__.'/../../resources/css/components/segment-control.css');

    expect($panel)
        ->toContain('x-bind:class="{')
        ->and($panel)->toContain("'is-active': tab === @js(\$key)")
        ->and($panel)->not->toContain('x-show="tab ===');

    expect($css)
        ->toContain('.fff-segment-tabs__panel {')
        ->and($css)->toContain('display: none;')
        ->and($css)->toContain('.fff-segment-tabs__panel.is-active {')
        ->and($css)->not->toContain('.fff-segment-tabs__panel:not(.is-active)');
});
