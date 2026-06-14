<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TrafficSplit;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class TrafficSplitPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'traffic_split__two' => [50, 50],
            'traffic_split__three' => [33, 33, 34],
            'traffic_split__four' => [25, 25, 25, 25],
            'traffic_split__five' => [20, 20, 20, 20, 20],
            'traffic_split__sm' => [50, 50],
            'traffic_split__md' => [33, 33, 34],
            'traffic_split__lg' => [33, 33, 34],
            'traffic_split__default' => [33, 33, 34],
            'traffic_split__secondary' => [33, 33, 34],
            'traffic_split__labels' => [40, 30, 30],
            'traffic_split__locked_middle' => [25, 25, 25, 25],
            'traffic_split__disabled' => [50, 50],
            'traffic_split__repeater_urls' => [
                'https://example.com/a',
                'https://example.com/b',
            ],
            'traffic_split__repeater_linked' => [50, 50],
        ];
    }

    public function section(): Section
    {
        return Section::make('Traffic Split')
            ->description('Drag the handles between segments to adjust percentage distribution (2–5 segments, always sums to 100%).')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Section::make('Segment count')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->extraAttributes(['class' => 'fff-playground-variants'])
                            ->schema([
                                $this->field('traffic_split__two', 'Two segments', 2),
                                $this->field('traffic_split__three', 'Three segments', 3),
                                $this->field('traffic_split__four', 'Four segments', 4),
                                $this->field('traffic_split__five', 'Five segments', 5),
                            ]),
                    ]),
                Section::make('Sizes')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 3])
                            ->extraAttributes(['class' => 'fff-playground-variants'])
                            ->schema([
                                $this->field('traffic_split__sm', 'Small', 2)->size('sm'),
                                $this->field('traffic_split__md', 'Medium', 3),
                                $this->field('traffic_split__lg', 'Large', 3)->size('lg'),
                            ]),
                    ]),
                Section::make('Variants')
                    ->compact()
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                $this->field('traffic_split__default', 'Default', 3)
                                    ->variant('default'),
                                $this->field('traffic_split__secondary', 'Secondary', 3)
                                    ->variant('secondary'),
                            ]),
                    ]),
                Section::make('Custom labels')
                    ->compact()
                    ->schema([
                        $this->field('traffic_split__labels', 'Campaign split', 3)
                            ->labels(['A', 'B', 'C'])
                            ->helperText('Optional labels replace the default index numbers.'),
                    ]),
                Section::make('Locked segments')
                    ->compact()
                    ->schema([
                        $this->field('traffic_split__locked_middle', 'Locked segment', 4)
                            ->lockedSegments([1])
                            ->labels(['A', 'B', 'C', 'D'])
                            ->helperText('Segment B stays fixed. Handles touching B are disabled; C and D can still be rebalanced.'),
                    ]),
                Section::make('Disabled')
                    ->compact()
                    ->schema([
                        $this->field('traffic_split__disabled', 'Traffic split', 2)
                            ->disabled(),
                    ]),
                Section::make('Linked to Repeater')
                    ->description('Add or remove URLs — the traffic split segments follow automatically (like Dub A/B testing).')
                    ->compact()
                    ->schema($this->linkedRepeaterDemo()),
            ]);
    }

    protected function field(string $name, string $label, int $segmentCount): TrafficSplit
    {
        return TrafficSplit::make($name)
            ->label($label)
            ->segmentCount($segmentCount);
    }

    /**
     * @return list<Component>
     */
    protected function linkedRepeaterDemo(): array
    {
        $linkedTrafficSplit = TrafficSplit::make('traffic_split__repeater_linked')
            ->label('Traffic Split')
            ->linkedToRepeater('traffic_split__repeater_urls')
            ->helperText('Segments match repeater rows. Max 5 URLs. Split hidden until 2+ URLs.')
            ->columnSpanFull();

        return [
            Repeater::make('traffic_split__repeater_urls')
                ->label('Testing URLs')
                ->maxItems(TrafficSplit::MAX_LINKED_REPEATER_ITEMS)
                ->partiallyRenderAfterActionsCalled(true)
                ->partiallyRenderComponentsAfterStateUpdated(['traffic_split__repeater_linked'])
                ->afterStateUpdated($linkedTrafficSplit->repeaterSyncCallback())
                ->simple(
                    FlexTextInput::make('url')
                        ->url()
                        ->required()
                        ->placeholder('https://example.com/variant-a'),
                )
                ->defaultItems(2)
                ->addActionLabel('Add URL')
                ->reorderable(false)
                ->extraAttributes(['class' => 'fff-repeater-stacked-urls'])
                ->columnSpanFull(),
            $linkedTrafficSplit,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [$this->section()];
    }
}
