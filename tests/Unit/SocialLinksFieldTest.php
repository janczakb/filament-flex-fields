<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\SocialPlatform;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField;
use Bjanczak\FilamentFlexFields\Support\SocialLinks\SocialLinksNormalizer;
use Bjanczak\FilamentFlexFields\Support\SocialLinks\SocialLinkValidator;
use Bjanczak\FilamentFlexFields\Support\SocialLinks\SocialPlatformDefinition;

it('normalizes list and associative state shapes', function (): void {
    expect(SocialLinksNormalizer::normalize([
        ['platform' => 'instagram', 'url' => 'https://instagram.com/foo'],
        ['platform' => 'x', 'url' => 'https://x.com/foo'],
    ]))->toBe([
        ['platform' => 'instagram', 'url' => 'https://instagram.com/foo'],
        ['platform' => 'x', 'url' => 'https://x.com/foo'],
    ])->and(SocialLinksNormalizer::normalize([
        'linkedin' => 'https://linkedin.com/in/foo',
    ]))->toBe([
        ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/foo'],
    ]);
});

it('keeps rows with empty urls during normalization', function (): void {
    expect(SocialLinksNormalizer::normalize([
        ['platform' => 'instagram', 'url' => ''],
        ['platform' => 'github', 'url' => 'https://github.com/laravel'],
    ]))->toBe([
        ['platform' => 'instagram', 'url' => ''],
        ['platform' => 'github', 'url' => 'https://github.com/laravel'],
    ]);
});

it('deduplicates platforms and strips empty urls on dehydrate', function (): void {
    expect(SocialLinksNormalizer::deduplicatePlatforms([
        ['platform' => 'instagram', 'url' => 'https://instagram.com/a'],
        ['platform' => 'instagram', 'url' => 'https://instagram.com/b'],
    ]))->toBe([
        ['platform' => 'instagram', 'url' => 'https://instagram.com/a'],
    ])->and(SocialLinksNormalizer::dehydrate([
        ['platform' => 'instagram', 'url' => ''],
        ['platform' => 'github', 'url' => 'https://github.com/laravel'],
    ]))->toBe([
        ['platform' => 'github', 'url' => 'https://github.com/laravel'],
    ])->and(SocialLinksNormalizer::dehydrate([]))->toBeNull();
});

it('validates platform-specific hostnames', function (): void {
    $definitions = [
        'instagram' => SocialPlatformDefinition::fromEnum(SocialPlatform::Instagram),
        'website' => SocialPlatformDefinition::fromEnum(SocialPlatform::Website),
        'x' => SocialPlatformDefinition::fromEnum(SocialPlatform::X),
        'discord' => SocialPlatformDefinition::fromEnum(SocialPlatform::Discord),
        'vk' => SocialPlatformDefinition::fromEnum(SocialPlatform::Vk),
    ];

    expect(SocialLinkValidator::validateLink('instagram', 'https://instagram.com/user', [], $definitions))->toBeNull()
        ->and(SocialLinkValidator::validateLink('instagram', 'https://example.com/user', [], $definitions))->not->toBeNull()
        ->and(SocialLinkValidator::validateLink('website', 'https://example.com', [], $definitions))->toBeNull()
        ->and(SocialLinkValidator::validateLink('x', 'https://twitter.com/user', [], $definitions))->toBeNull()
        ->and(SocialLinkValidator::validateLink('discord', 'https://discord.gg/laravel', [], $definitions))->toBeNull()
        ->and(SocialLinkValidator::validateLink('vk', 'https://vk.com/page', [], $definitions))->toBeNull();
});

it('validates custom platform hosts via definitions map', function (): void {
    $definitions = [
        'mastodon' => SocialPlatformDefinition::fromArray([
            'value' => 'mastodon',
            'label' => 'Mastodon',
            'placeholder' => 'https://mastodon.social/@username',
            'hosts' => ['mastodon.social'],
        ]),
    ];

    expect(SocialLinkValidator::validateLink('mastodon', 'https://mastodon.social/@user', [], $definitions))->toBeNull()
        ->and(SocialLinkValidator::validateLink('mastodon', 'https://example.com/@user', [], $definitions))->not->toBeNull();
});

it('configures platforms and max links', function (): void {
    $field = SocialLinksField::make('socials')
        ->platforms([SocialPlatform::Instagram, SocialPlatform::Website])
        ->maxLinks(2)
        ->variant('soft')
        ->size('sm');

    expect($field->getPlatformValues())->toBe(['instagram', 'website'])
        ->and($field->getMaxLinks())->toBe(2)
        ->and($field->getVariant())->toBe('soft')
        ->and($field->getSize())->toBe('sm');
});

it('excludes platforms from defaults when platforms is not set', function (): void {
    $field = SocialLinksField::make('socials')
        ->excludePlatforms([SocialPlatform::Vk, SocialPlatform::Twitch]);

    expect($field->getPlatformValues())
        ->not->toContain('vk')
        ->not->toContain('twitch')
        ->toContain('instagram')
        ->toContain('website');
});

it('merges custom platforms into defaults and whitelist', function (): void {
    $field = SocialLinksField::make('socials')
        ->customPlatforms([
            [
                'value' => 'mastodon',
                'label' => 'Mastodon',
                'placeholder' => 'https://mastodon.social/@username',
                'hosts' => ['mastodon.social'],
                'iconSvg' => '<svg data-custom="mastodon"></svg>',
            ],
        ]);

    expect($field->getPlatformValues())->toContain('mastodon')
        ->and(collect($field->getPlatformDefinitions())->firstWhere('value', 'mastodon')['hosts'])
        ->toBe(['mastodon.social'])
        ->and($field->getBrandIconSvgs()['mastodon'])->toContain('data-custom="mastodon"');

    $whitelisted = SocialLinksField::make('socials')
        ->platforms(['mastodon', SocialPlatform::Instagram])
        ->customPlatforms([
            [
                'value' => 'mastodon',
                'label' => 'Mastodon',
                'hosts' => ['mastodon.social'],
            ],
        ]);

    expect($whitelisted->getPlatformValues())->toBe(['mastodon', 'instagram']);
});

it('configures reorderable and auto format url options', function (): void {
    expect(SocialLinksField::make('socials')->isReorderable())->toBeFalse();

    $field = SocialLinksField::make('socials')
        ->reorderable()
        ->autoFormatUrls(false);

    expect($field->isReorderable())->toBeTrue()
        ->and($field->shouldAutoFormatUrls())->toBeFalse()
        ->and($field->getAlpineConfiguration()['reorderable'])->toBeTrue()
        ->and($field->getAlpineConfiguration()['autoFormatUrls'])->toBeFalse();
});

it('exposes alpine configuration with brand icons and labels', function (): void {
    $field = SocialLinksField::make('socials');

    $config = $field->getAlpineConfiguration();

    expect($config)
        ->toHaveKeys(['platforms', 'brandIcons', 'labels', 'icons', 'reorderable', 'autoFormatUrls'])
        ->and($config['brandIcons'])->toHaveKey('instagram')
        ->and($config['labels'])->toHaveKeys(['add', 'choosePlatform', 'required', 'platformMismatch', 'moveUp', 'moveDown', 'platformNotAllowed'])
        ->and($config['icons'])->toHaveKeys(['remove', 'chevron', 'chevronUp', 'chevronDown'])
        ->and($config['platforms'][0])->toHaveKey('hosts');
});

it('renders brand icon svgs for configured platforms', function (): void {
    $field = SocialLinksField::make('socials')
        ->platforms([SocialPlatform::Instagram, SocialPlatform::Website]);

    $icons = $field->getBrandIconSvgs();

    expect($icons)
        ->toHaveKeys(['instagram', 'website'])
        ->and($icons['instagram'])->toContain('fff-social-links__brand-icon')
        ->and($icons['website'])->toContain('<svg');
});
