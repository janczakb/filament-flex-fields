<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRichEditor;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableFlexRichEditorForm;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableFlexRichEditorForm::$formSchema = [];
});

it('renders flex rich editor shell and alpine configuration', function (): void {
    TestableFlexRichEditorForm::$formSchema = [
        FlexRichEditor::make('body')
            ->wordCount()
            ->maxCharacters(5000)
            ->youtube(),
    ];

    $html = Livewire::test(TestableFlexRichEditorForm::class)->html(false);

    expect($html)
        ->toContain('fff-rich-editor')
        ->toContain('flexRichEditorFormComponent({')
        ->toContain('fi-fo-rich-editor')
        ->toContain('fff-rich-editor__footer-stats')
        ->not->toContain('x-effect="editorUpdatedAt; scheduleRichEditorChromeSync()"')
        ->toContain('aria-orientation="horizontal"')
        ->toContain('role="textbox"')
        ->toContain('aria-live="polite"');
});

it('omits chrome sync effect for a minimal editor without footer metrics', function (): void {
    TestableFlexRichEditorForm::$formSchema = [
        FlexRichEditor::make('body'),
    ];

    $html = Livewire::test(TestableFlexRichEditorForm::class)->html(false);

    expect($html)
        ->toContain('flexRichEditorFormComponent({')
        ->not->toContain('x-effect="editorUpdatedAt; scheduleRichEditorChromeSync()"');
});

it('enables chrome sync when autosave is configured', function (): void {
    TestableFlexRichEditorForm::$formSchema = [
        FlexRichEditor::make('body')->autosave(30),
    ];

    $html = Livewire::test(TestableFlexRichEditorForm::class)->html(false);

    expect($html)
        ->toContain('fff-rich-editor__footer-autosave')
        ->not->toContain('x-effect="editorUpdatedAt; scheduleRichEditorChromeSync()"');
});

it('renders optional image overlay chrome when attachments are enabled', function (): void {
    TestableFlexRichEditorForm::$formSchema = [
        FlexRichEditor::make('body')
            ->fileAttachments(true)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('rich-editor'),
    ];

    $html = Livewire::test(TestableFlexRichEditorForm::class)->html(false);

    expect($html)
        ->toContain('fff-rich-editor__image-overlay')
        ->toContain('editSelectedImage()');
});
