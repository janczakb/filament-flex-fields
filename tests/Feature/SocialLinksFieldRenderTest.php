<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableSocialLinksForm;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    TestableSocialLinksForm::$formSchema = [];
});

it('renders social links field shell and alpine configuration', function (): void {
    TestableSocialLinksForm::$formSchema = [
        SocialLinksField::make('socials')
            ->required(),
    ];

    $html = Livewire::test(TestableSocialLinksForm::class)->html(false);

    expect($html)
        ->toContain('fff-social-links')
        ->toContain('socialLinksFieldFormComponent({')
        ->toContain('onPlatformMenuKeydown')
        ->toContain('aria-activedescendant')
        ->toContain('fff-social-links__reorder-btn')
        ->toContain('role="listbox"')
        ->toContain('role="option"');
});

it('fails server validation when required social links are empty', function (): void {
    TestableSocialLinksForm::$formSchema = [
        SocialLinksField::make('socials')->required(),
    ];

    $component = Livewire::test(TestableSocialLinksForm::class)
        ->set('data.socials', []);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('fails server validation when a row has an empty url', function (): void {
    TestableSocialLinksForm::$formSchema = [
        SocialLinksField::make('socials')->required(),
    ];

    $component = Livewire::test(TestableSocialLinksForm::class)
        ->set('data.socials', [
            ['platform' => 'instagram', 'url' => ''],
        ]);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('fails server validation for invalid host mismatch', function (): void {
    TestableSocialLinksForm::$formSchema = [
        SocialLinksField::make('socials'),
    ];

    $component = Livewire::test(TestableSocialLinksForm::class)
        ->set('data.socials', [
            ['platform' => 'instagram', 'url' => 'https://example.com/profile'],
        ]);

    expect(fn () => $component->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);
});

it('passes server validation for valid social links', function (): void {
    TestableSocialLinksForm::$formSchema = [
        SocialLinksField::make('socials')->required(),
    ];

    Livewire::test(TestableSocialLinksForm::class)
        ->set('data.socials', [
            ['platform' => 'instagram', 'url' => 'https://instagram.com/laravelphp'],
            ['platform' => 'website', 'url' => 'https://example.com'],
        ])
        ->call('save')
        ->assertHasNoErrors();
});

it('validates custom platform hosts on the server', function (): void {
    TestableSocialLinksForm::$formSchema = [
        SocialLinksField::make('socials')
            ->platforms(['mastodon'])
            ->customPlatforms([
                [
                    'value' => 'mastodon',
                    'label' => 'Mastodon',
                    'placeholder' => 'https://mastodon.social/@username',
                    'hosts' => ['mastodon.social', 'mastodon.online'],
                ],
            ]),
    ];

    $invalid = Livewire::test(TestableSocialLinksForm::class)
        ->set('data.socials', [
            ['platform' => 'mastodon', 'url' => 'https://example.com/@user'],
        ]);

    expect(fn () => $invalid->instance()->getSchema('form')->validate())
        ->toThrow(ValidationException::class);

    Livewire::test(TestableSocialLinksForm::class)
        ->set('data.socials', [
            ['platform' => 'mastodon', 'url' => 'https://mastodon.social/@user'],
        ])
        ->call('save')
        ->assertHasNoErrors();
});
