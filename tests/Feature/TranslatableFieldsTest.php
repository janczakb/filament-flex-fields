<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Bjanczak\FilamentFlexFields\Tests\Support\TranslatablePost;
use Filament\Forms\Components\Field;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TestableTranslatableForm::$formSchema = [];

    Schema::dropIfExists('translatable_posts');

    Schema::create('translatable_posts', function (Blueprint $table): void {
        $table->id();
        $table->json('title');
        $table->json('body')->nullable();
        $table->timestamps();
    });
});

it('stores single translatable field values per locale', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Title')
            ->locales(['ar' => 'Arabic', 'en' => 'English'])
            ->schema([
                FlexTextInput::make('title')->hiddenLabel(),
            ]),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.title.ar', 'مرحبا')
        ->set('data.title.en', 'Hello')
        ->assertSet('data.title.ar', 'مرحبا')
        ->assertSet('data.title.en', 'Hello');
});

it('stores multiple translatable fields per locale', function (): void {
    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Content')
            ->locales(['pl' => 'PL', 'en' => 'EN'])
            ->schema([
                FlexTextInput::make('title')->hiddenLabel(),
                FlexTextareaField::make('body')->hiddenLabel(),
            ]),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.title.pl', 'Tytuł')
        ->set('data.body.pl', 'Treść')
        ->assertSet('data.title.pl', 'Tytuł')
        ->assertSet('data.body.pl', 'Treść');
});

it('hydrates json attributes into locale fields on edit', function (): void {
    $post = TranslatablePost::create([
        'title' => ['ar' => 'عنوان', 'en' => 'Title'],
        'body' => ['ar' => 'نص', 'en' => 'Body'],
    ]);

    TestableTranslatableForm::$formSchema = [
        TranslatableFields::make('Content')
            ->locales(['ar' => 'Arabic', 'en' => 'English'])
            ->schema([
                FlexTextInput::make('title')->hiddenLabel(),
                FlexTextareaField::make('body')->hiddenLabel(),
            ]),
    ];

    Livewire::test(TestableTranslatableForm::class, ['record' => $post])
        ->assertSet('data.title.ar', 'عنوان')
        ->assertSet('data.title.en', 'Title')
        ->assertSet('data.body.ar', 'نص')
        ->assertSet('data.body.en', 'Body');
});

it('supports single field macro', function (): void {
    TestableTranslatableForm::$formSchema = [
        FlexTextInput::make('title')
            ->label('Title')
            ->translatableFields(['pl' => 'PL', 'en' => 'EN']),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.title.pl', 'Polski')
        ->assertSet('data.title.pl', 'Polski');
});

it('supports macro with inline modify callbacks', function (): void {
    TestableTranslatableForm::$formSchema = [
        FlexTextInput::make('title')
            ->label('Title')
            ->translatableFields(
                locales: ['pl', 'en'],
                modifyFieldsUsing: function (Field $field, string $locale): void {
                    $field->placeholder("Title ({$locale})");
                },
            ),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.title.pl', 'Polski')
        ->assertSet('data.title.pl', 'Polski');
});

it('supports translatableTabs macro alias', function (): void {
    TestableTranslatableForm::$formSchema = [
        FlexTextInput::make('title')
            ->label('Title')
            ->translatableTabs(['de' => 'DE', 'en' => 'EN']),
    ];

    Livewire::test(TestableTranslatableForm::class)
        ->set('data.title.de', 'Hallo')
        ->assertSet('data.title.de', 'Hallo');
});
