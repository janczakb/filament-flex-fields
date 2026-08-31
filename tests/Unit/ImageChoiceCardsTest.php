<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ImageChoiceCards;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\Playground\ImageChoiceCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ImageChoiceCardsPlaygroundSilhouettes;
use Filament\Schemas\Components\StateCasts\OptionsArrayStateCast;

it('exposes image choice cards configuration via fluent api', function () {
    $field = ImageChoiceCards::make('body_type')
        ->options([
            'athletic' => [
                'label' => 'Athletic',
                'image' => 'https://example.com/athletic.jpg',
                'alt' => 'Athletic build',
            ],
        ])
        ->multiple(false)
        ->size('lg')
        ->rounding('lg')
        ->gridColumns(['default' => 2, 'sm' => 4])
        ->imageAspectRatio('3/4')
        ->imageFit('cover')
        ->indicator('check')
        ->disabledOptions(['bulky'])
        ->ripple();

    $options = $field->getNormalizedOptions();

    expect($field->isMultiple())->toBeFalse()
        ->and($field->getSize())->toBe('lg')
        ->and($field->getRounding())->toBe('lg')
        ->and($field->getGridColumnConfig()['sm'])->toBe(4)
        ->and($field->getImageAspectRatio())->toBe('3/4')
        ->and($field->getImageFit())->toBe('cover')
        ->and($field->getIndicator())->toBe('check')
        ->and($field->isRippleEnabled())->toBeTrue()
        ->and($options['athletic']['label'])->toBe('Athletic')
        ->and($options['athletic']['image'])->toBe('https://example.com/athletic.jpg')
        ->and($options['athletic']['alt'])->toBe('Athletic build');
});

it('defaults indicator to check for radio and checkbox for multi', function () {
    $radio = ImageChoiceCards::make('body')->multiple(false);
    $multi = ImageChoiceCards::make('focus')->multiple();

    expect($radio->getIndicator())->toBe('check')
        ->and($multi->getIndicator())->toBe('checkbox');
});

it('uses alt from label when alt is omitted', function () {
    $field = ImageChoiceCards::make('body')
        ->options([
            'slim' => ['label' => 'Slim', 'image' => '/slim.jpg'],
        ]);

    expect($field->getNormalizedOptions()['slim']['alt'])->toBe('Slim');
});

it('casts multi state with OptionsArrayStateCast', function () {
    $field = ImageChoiceCards::make('focus')->multiple();

    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof OptionsArrayStateCast))->toBeTrue();
});

it('does not add OptionsArrayStateCast for single select', function () {
    $field = ImageChoiceCards::make('body')->multiple(false);

    $casts = collect($field->getDefaultStateCasts());

    expect($casts->contains(fn ($cast): bool => $cast instanceof OptionsArrayStateCast))->toBeFalse();
});

it('prunes a single selection that became disabled', function () {
    $field = ImageChoiceCards::make('body')
        ->options([
            'slim' => ['label' => 'Slim'],
            'bulky' => ['label' => 'Bulky'],
        ])
        ->disabledOptions(['bulky']);

    expect($field->pruneState('bulky'))->toBeNull()
        ->and($field->pruneState('slim'))->toBe('slim');
});

it('filters multi selections that became disabled', function () {
    $field = ImageChoiceCards::make('focus')
        ->multiple()
        ->options([
            'yoga' => ['label' => 'Yoga'],
            'cardio' => ['label' => 'Cardio'],
            'strength' => ['label' => 'Strength'],
        ])
        ->disabledOptions(['cardio']);

    expect($field->pruneState(['yoga', 'cardio', 'strength']))->toBe(['yoga', 'strength']);
});

it('validates multi min max and exact selection counts', function () {
    $minField = ImageChoiceCards::make('focus')
        ->multiple()
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->minSelections(2);

    $minRule = collect($minField->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);
    $minMessage = null;
    $minRule('focus', ['a'], function (string $failMessage) use (&$minMessage): void {
        $minMessage = $failMessage;
    });

    expect($minMessage)->toBe(__('filament-flex-fields::default.validation.image_choice_cards.min', ['count' => 2]));

    $maxField = ImageChoiceCards::make('focus')
        ->multiple()
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->maxSelections(1);

    $maxRule = collect($maxField->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);
    $maxMessage = null;
    $maxRule('focus', ['a', 'b'], function (string $failMessage) use (&$maxMessage): void {
        $maxMessage = $failMessage;
    });

    expect($maxMessage)->toBe(__('filament-flex-fields::default.validation.image_choice_cards.max', ['count' => 1]));

    $exactField = ImageChoiceCards::make('focus')
        ->multiple()
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->exactSelections(2);

    $exactRule = collect($exactField->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);
    $exactMessage = null;
    $exactRule('focus', ['a'], function (string $failMessage) use (&$exactMessage): void {
        $exactMessage = $failMessage;
    });

    expect($exactMessage)->toBe(__('filament-flex-fields::default.validation.image_choice_cards.exact', ['count' => 2]));
});

it('marks disabled options from fluent api and option arrays', function () {
    $field = ImageChoiceCards::make('body')
        ->options([
            'slim' => ['label' => 'Slim', 'disabled' => true],
            'athletic' => ['label' => 'Athletic'],
        ])
        ->disabledOptions(['bulky']);

    expect($field->isOptionDisabled('slim'))->toBeTrue()
        ->and($field->isOptionDisabled('athletic'))->toBeFalse()
        ->and($field->isOptionDisabled('bulky'))->toBeTrue();
});

it('exposes size css variables', function () {
    $small = ImageChoiceCards::make('body')->size('sm')->getImageChoiceCardSizeStyles();
    $large = ImageChoiceCards::make('body')->size('lg')->getImageChoiceCardSizeStyles();

    expect($small['--fff-image-choice-cards-label-size'])->toBe('0.75rem')
        ->and($large['--fff-image-choice-cards-label-size'])->toBe('1rem');
});

it('registers image-choice-cards as a lazy stylesheet', function () {
    expect(FlexFieldAssets::LAZY_COMPONENT_STYLESHEETS)->toContain('image-choice-cards')
        ->and(FlexFieldAssets::hasLazyStylesheet('image-choice-cards'))->toBeTrue();
});

it('accepts gridColumns fluent api', function () {
    $field = ImageChoiceCards::make('body')->gridColumns(3);

    expect($field->getGridColumnConfig()['default'])->toBe(3);
});

it('supports default and overlay layout variants', function () {
    expect(ImageChoiceCards::make('body')->getVariant())->toBe('default')
        ->and(ImageChoiceCards::make('body')->variant('overlay')->getVariant())->toBe('overlay')
        ->and(ImageChoiceCards::make('body')->variant('invalid')->getVariant())->toBe('default');
});

it('provides inline playground silhouette data uris', function () {
    $uris = ImageChoiceCardsPlaygroundSilhouettes::dataUris();

    expect($uris)->toHaveKeys(['slim', 'average', 'athletic', 'shredded'])
        ->and($uris['slim'])->toStartWith('data:image/png;base64,');
});

it('uses pexels playground photos outside body-type silhouette demos', function () {
    $playground = new ImageChoiceCardsPlayground;

    $reflection = new ReflectionClass($playground);

    $squareBodyOptions = $reflection->getMethod('squareBodyOptions')->invoke($playground);
    $destinationOptions = $reflection->getMethod('destinationOptions')->invoke($playground);
    $focusOptions = $reflection->getMethod('focusOptions')->invoke($playground);
    $galleryPresetOptions = $reflection->getMethod('galleryPresetOptions')->invoke($playground);
    $yachtClassOptions = $reflection->getMethod('yachtClassOptions')->invoke($playground);
    $experienceOptions = $reflection->getMethod('experienceOptions')->invoke($playground);

    expect($squareBodyOptions['slim']['image'])->toStartWith('data:image/png;base64,');

    foreach ([$destinationOptions, $focusOptions, $galleryPresetOptions, $yachtClassOptions, $experienceOptions] as $options) {
        foreach ($options as $option) {
            if (blank($option['image'])) {
                continue;
            }

            expect($option['image'])
                ->toContain('images.pexels.com/photos/')
                ->not->toContain('picsum.photos')
                ->not->toStartWith('data:image/');
        }
    }
});

it('server renders selected state before alpine hydrates', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/image-choice-cards.blade.php');
    $css = file_get_contents(__DIR__.'/../../resources/css/core/image-choice-cards.css');
    $js = file_get_contents(__DIR__.'/../../resources/js/components/image-choice-cards.js');

    expect($blade)
        ->toContain('$currentState = $getState()')
        ->toContain('$isInitiallySelected')
        ->toContain("'is-selected' => \$isInitiallySelected")
        ->toContain('@checked($isInitiallySelected)');

    expect($css)
        ->toContain('.fff-image-choice-cards:not(.is-hydrated)')
        ->toContain('.fff-image-choice-cards--overlay')
        ->toContain('backdrop-filter: blur(20px) saturate(1.5)')
        ->toContain('--fff-image-choice-cards-overlay-fade');

    expect($js)
        ->toContain("this.\$root.classList.add('is-hydrated')");
});
