<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class FlexRichEditorPlayground
{
    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'filament_rich_editor__native' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Native Filament RichEditor — select this sentence to compare bubble menu, toolbar, and panels with FlexRichEditor below.',
                            ],
                        ],
                    ],
                ],
            ],
            'flex_rich_editor__basic' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Select this sentence to try the bubble menu. FlexRichEditor reuses Filament TipTap runtime with a premium shell.',
                            ],
                        ],
                    ],
                ],
            ],
            'flex_rich_editor__compact' => null,
            'flex_rich_editor__secondary' => null,
            'flex_rich_editor__soft' => null,
            'flex_rich_editor__flat' => null,
            'flex_rich_editor__attachments' => null,
            'flex_rich_editor__full_toolbar' => null,
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        $flexReference = FlexRichEditor::make('_playground_reference');

        $articleBodyToolbar = $flexReference->getFlexDefaultToolbarButtons();

        foreach ($articleBodyToolbar as $index => $group) {
            if (! is_array($group) || ! in_array('link', $group, true) || in_array('attachFiles', $group, true)) {
                continue;
            }

            $linkIndex = array_search('link', $group, true);
            $group = array_values($group);
            array_splice($group, (int) $linkIndex + 1, 0, ['attachFiles']);
            $articleBodyToolbar[$index] = $group;

            break;
        }

        return [
            Section::make(__('filament-flex-fields::default.rich_editor.playground.native.section'))
                ->description(__('filament-flex-fields::default.rich_editor.playground.native.description'))
                ->icon(GravityIcon::make('text-align-left'))
                ->schema([
                    RichEditor::make('filament_rich_editor__native')
                        ->label(__('filament-flex-fields::default.rich_editor.playground.native.label'))
                        ->json()
                        ->tools(FlexRichEditor::getNativeExtraTools())
                        ->toolbarButtons($flexReference->getNativeComparisonToolbarButtons())
                        ->floatingToolbars($flexReference->getFlexDefaultFloatingToolbars())
                        ->fileAttachmentsDirectory('rich-editor/attachments')
                        ->fileAttachmentsDisk('public')
                        ->columnSpanFull(),
                ]),
            Section::make('FlexRichEditor')
                ->description('JSON-first rich editor — image variants apply on upload/save (check disk + FlexRichContentRenderer HTML for srcset; editor preview uses temporary Livewire URL). Click an image for centered Edit/Delete overlay (editor only).')
                ->icon(GravityIcon::FileText)
                ->schema([
                    FlexRichEditor::make('flex_rich_editor__basic')
                        ->label('Article body')
                        ->toolbarButtons($articleBodyToolbar)
                        ->scopedAttachmentDirectory('rich-editor')
                        ->fileAttachmentsDisk('public')
                        ->imagesOnly()
                        ->maxAttachmentSizeKb(5120)
                        ->imageVariants([
                            'thumb' => ['max_long_edge' => 320, 'webp' => true],
                            'medium' => ['max_long_edge' => 1200, 'webp' => true],
                            'large' => ['max_long_edge' => 2000, 'master' => true, 'webp' => true],
                        ])
                        ->responsiveImages()
                        ->lazyImages()
                        ->altTextRequired()
                        ->pruneOrphanedAttachmentsOnSave()
                        ->wordCount()
                        ->readingTime()
                        ->maxCharacters(10000)
                        ->maxWords(1500)
                        ->limitBehavior('soft')
                        ->fullscreen()
                        ->distractionFree()
                        ->youtube()
                        ->youtubeNocookie()
                        ->autosave(30, fn (): string => 'playground-article-body-'.(auth()->id() ?? 'guest'))
                        ->pasteCleanup('aggressive')
                        ->columnSpanFull(),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            FlexRichEditor::make('flex_rich_editor__compact')
                                ->label('Compact (sm)')
                                ->size('sm')
                                ->wordCount()
                                ->jsonBadge(false),
                            FlexRichEditor::make('flex_rich_editor__secondary')
                                ->label('Secondary')
                                ->variant('secondary')
                                ->size('md')
                                ->wordCount(),
                        ]),
                    Grid::make(['default' => 1, 'lg' => 2])
                        ->schema([
                            FlexRichEditor::make('flex_rich_editor__soft')
                                ->label('Soft')
                                ->variant('soft')
                                ->size('md'),
                            FlexRichEditor::make('flex_rich_editor__flat')
                                ->label('Flat')
                                ->variant('flat')
                                ->size('lg')
                                ->wordCount()
                                ->jsonBadge(false),
                        ]),
                ]),
            Section::make('File attachments')
                ->description('Upload images inline via attachFiles — stored on the public disk under rich-editor/attachments.')
                ->icon(GravityIcon::make('picture'))
                ->schema([
                    FlexRichEditor::make('flex_rich_editor__attachments')
                        ->label('Editor with image uploads')
                        ->toolbarButtons([
                            ['bold', 'italic', 'link', 'attachFiles'],
                        ])
                        ->fileAttachmentsDirectory('rich-editor/attachments')
                        ->fileAttachmentsDisk('public')
                        ->wordCount()
                        ->columnSpanFull(),
                ]),
            Section::make('Full toolbar')
                ->description('Grouped headings, alignment, lists, and attachFiles.')
                ->icon(GravityIcon::LayoutCells)
                ->schema([
                    FlexRichEditor::make('flex_rich_editor__full_toolbar')
                        ->label('Full feature toolbar')
                        ->toolbarButtons(fn (FlexRichEditor $component): array => $component->getFlexFullToolbarButtons())
                        ->fileAttachmentsDirectory('rich-editor/attachments')
                        ->fileAttachmentsDisk('public')
                        ->wordCount()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
