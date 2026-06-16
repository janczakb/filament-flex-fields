<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Livewire\Livewire;

it('renders select field coordinator shell for enhanced selects', function (): void {
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
        ->toContain('fff-select-field__shell')
        ->toContain('fffSelectFieldCoordinator')
        ->toContain('data-fff-select-root')
        ->toContain('selectFormComponent({')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.status["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.technologies["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.theme["\']?/')
        ->not->toContain('window.bootSelectFieldPatches')
        ->not->toContain('selectFieldPreload');
});

it('renders user select through the same coordinator shell', function (): void {
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
        ->toContain('fff-select-field__shell')
        ->toContain('fffSelectFieldCoordinator')
        ->toContain('data-fff-select-root')
        ->toContain('shouldPatchUserSelectClient')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.assignee["\']?/')
        ->toMatch('/statePath["\']?\s*:\s*["\']data\.members["\']?/');
});
