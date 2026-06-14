<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class TagsFieldPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'tags_field__basic' => ['laravel', 'filament'],
            'tags_field__suggestions' => ['tailwindcss'],
            'tags_field__comma' => 'php,pest',
            'tags_field__suffix' => ['12', '24'],
            'tags_field__reorderable' => ['alpha', 'beta', 'gamma'],
            'tags_field__max' => ['one', 'two'],
            'tags_field__danger' => ['blocked'],
            'tags_field__disabled' => ['readonly'],
            'tags_field__sm' => ['small'],
            'tags_field__lg' => ['large'],
            'tags_field__secondary' => ['secondary'],
            'tags_field__soft' => ['soft'],
        ];
    }

    public function section(): Section
    {
        return Section::make('Tags field')
            ->description('HeroUI-style tags input — clean text field with removable pills below, autocomplete suggestions, and full Filament TagsInput API.')
            ->extraAttributes(['class' => 'fff-playground-section'])
            ->schema([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        TagsField::make('tags_field__basic')
                            ->label('Basic tags')
                            ->placeholder('Type and press Enter')
                            ->helperText('Tags render below the input with an inline remove button.'),
                        TagsField::make('tags_field__suggestions')
                            ->label('Autocomplete suggestions')
                            ->suggestions([
                                'tailwindcss',
                                'alpinejs',
                                'laravel',
                                'livewire',
                                'filament',
                            ])
                            ->helperText('Pick from suggestions or type your own.'),
                        TagsField::make('tags_field__comma')
                            ->label('Comma-separated storage')
                            ->separator(',')
                            ->placeholder('Add skills')
                            ->helperText('Dehydrates as a comma-separated string instead of JSON.'),
                        TagsField::make('tags_field__suffix')
                            ->label('Tag suffix')
                            ->tagSuffix('%')
                            ->placeholder('Add percentage')
                            ->helperText('Display-only prefix/suffix without changing stored values.'),
                    ]),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        TagsField::make('tags_field__reorderable')
                            ->label('Reorderable')
                            ->reorderable()
                            ->splitKeys(['Tab', ' '])
                            ->helperText('Drag tags to reorder. Tab or space also creates a tag.'),
                        TagsField::make('tags_field__max')
                            ->label('Max tags + counter')
                            ->maxTags(3)
                            ->showTagCount()
                            ->duplicateInsensitive()
                            ->helperText('Up to 3 tags. Duplicate check ignores letter case.'),
                        TagsField::make('tags_field__danger')
                            ->label('Danger color')
                            ->color('danger')
                            ->nestedRecursiveRules(['min:2', 'max:32'])
                            ->placeholder('Add blocked keywords'),
                        TagsField::make('tags_field__disabled')
                            ->label('Disabled')
                            ->disabled()
                            ->suggestions(['alpha', 'beta']),
                    ]),
                Grid::make(['default' => 1, 'lg' => 3])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        TagsField::make('tags_field__sm')
                            ->label('Size: sm')
                            ->size('sm')
                            ->placeholder('Small control'),
                        TagsField::make('tags_field__lg')
                            ->label('Size: lg')
                            ->size('lg')
                            ->placeholder('Large control'),
                        TagsField::make('tags_field__secondary')
                            ->label('Variant: secondary')
                            ->variant('secondary')
                            ->placeholder('Secondary shell'),
                    ]),
                Grid::make(['default' => 1, 'lg' => 2])
                    ->extraAttributes(['class' => 'fff-playground-variants'])
                    ->schema([
                        TagsField::make('tags_field__soft')
                            ->label('Variant: soft')
                            ->variant('soft')
                            ->color('info')
                            ->placeholder('Soft pills'),
                        TagsField::make('tags_field__flat')
                            ->label('Variant: flat')
                            ->variant('flat')
                            ->placeholder('Flat shell, no tag shadow'),
                    ]),
            ]);
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            $this->section(),
        ];
    }
}
