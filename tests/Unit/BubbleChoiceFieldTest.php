<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\BubbleChoiceField;
use Bjanczak\FilamentFlexFields\Support\Playground\BubbleChoicePlayground;

it('exposes bubble choice styling and layout api', function () {
    $field = BubbleChoiceField::make('habits')
        ->options([
            'water' => 'Water',
            'journal' => [
                'label' => 'Journal',
                'image' => '/images/journal.jpg',
                'color' => '#3b82f6',
                'selectedColor' => '#c8f560',
            ],
        ])
        ->size('lg')
        ->variant('solid')
        ->selectedShape('scallop')
        ->bubbleColor('#1d4ed8')
        ->selectedBubbleColor('#bbf7d0')
        ->arenaColor('#0f172a')
        ->layoutOptions([
            'size' => 160,
            'minSize' => 25,
            'gutter' => 8,
            'numCols' => 10,
            'fringeWidth' => 160,
            'yRadius' => 130,
            'xRadius' => 248,
            'cornerRadius' => 50,
            'compact' => true,
            'gravitation' => 5,
        ])
        ->arenaHeight('24rem')
        ->minItems(1)
        ->maxItems(3);

    $layout = $field->getLayoutOptionsForJs();

    expect($field->getSize())->toBe('lg')
        ->and($field->getVariant())->toBe('solid')
        ->and($field->getSelectedShape())->toBe('scallop')
        ->and($field->getBubbleColor())->toBe('#1d4ed8')
        ->and($field->getSelectedBubbleColor())->toBe('#bbf7d0')
        ->and($field->getArenaColor())->toBe('#0f172a')
        ->and($layout['size'])->toBe(160.0)
        ->and($layout['minSize'])->toBe(25.0)
        ->and($layout['gutter'])->toBe(8.0)
        ->and($layout['numCols'])->toBe(10)
        ->and($layout['fringeWidth'])->toBe(160.0)
        ->and($layout['yRadius'])->toBe(130.0)
        ->and($layout['xRadius'])->toBe(248.0)
        ->and($layout['cornerRadius'])->toBe(50.0)
        ->and($layout['compact'])->toBeTrue()
        ->and($layout['gravitation'])->toBe(5.0)
        ->and($layout['provideProps'])->toBeTrue()
        ->and($field->getArenaHeight())->toBe('24rem')
        ->and($field->getMinItems())->toBe(1)
        ->and($field->getMaxItems())->toBe(3);
});

it('normalizes rich bubble options for js', function () {
    $field = BubbleChoiceField::make('habits')
        ->options([
            'water' => 'Water',
            'journal' => [
                'label' => 'Journal',
                'description' => 'Notes',
                'image' => '/images/journal.jpg',
                'imageMode' => 'icon',
                'color' => '#3b82f6',
                'selectedColor' => '#c8f560',
            ],
        ])
        ->disabledOptions(['blocked']);

    $options = $field->getOptionsForJs();

    expect($options)->toHaveCount(2)
        ->and($options[0])->toMatchArray([
            'value' => 'water',
            'label' => 'Water',
            'description' => null,
            'image' => null,
            'imageMode' => 'background',
            'color' => null,
            'selectedColor' => null,
            'disabled' => false,
        ])
        ->and($options[1])->toMatchArray([
            'description' => 'Notes',
            'image' => '/images/journal.jpg',
            'imageMode' => 'icon',
            'color' => '#3b82f6',
            'selectedColor' => '#c8f560',
        ]);
});

it('normalizes state and strips unknown or disabled values', function () {
    $field = BubbleChoiceField::make('habits')
        ->options([
            'water' => 'Water',
            'yoga' => 'Yoga',
            'blocked' => [
                'label' => 'Blocked',
                'disabled' => true,
            ],
        ]);

    expect($field->normalizeState(['water', 'water', 'missing', 'blocked']))
        ->toBe(['water']);
});

it('validates min max and required selection counts', function () {
    $minField = BubbleChoiceField::make('habits')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->minItems(2);

    $minRule = collect($minField->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);
    $minMessage = null;
    $minRule('habits', ['a'], function (string $failMessage) use (&$minMessage): void {
        $minMessage = $failMessage;
    });

    expect($minMessage)->toBe(__('filament-flex-fields::default.validation.bubble_choice.min', ['count' => 2]));

    $maxField = BubbleChoiceField::make('habits')
        ->options(['a' => 'A', 'b' => 'B', 'c' => 'C'])
        ->maxItems(1);

    $maxRule = collect($maxField->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);
    $maxMessage = null;
    $maxRule('habits', ['a', 'b'], function (string $failMessage) use (&$maxMessage): void {
        $maxMessage = $failMessage;
    });

    expect($maxMessage)->toBe(__('filament-flex-fields::default.validation.bubble_choice.max', ['count' => 1]));

    $requiredField = BubbleChoiceField::make('habits')
        ->options(['a' => 'A', 'b' => 'B'])
        ->required();

    $requiredRule = collect($requiredField->getValidationRules())->first(fn (mixed $rule): bool => $rule instanceof Closure);
    $requiredMessage = null;
    $requiredRule('habits', [], function (string $failMessage) use (&$requiredMessage): void {
        $requiredMessage = $failMessage;
    });

    expect($requiredMessage)->toBe(__('filament-flex-fields::default.validation.bubble_choice.min', ['count' => 1]));
});

it('exposes wrapper classes for variant and selected shape', function () {
    $field = BubbleChoiceField::make('habits')
        ->size('sm')
        ->variant('outline')
        ->selectedShape('grow');

    expect($field->getWrapperClasses())->toContain(
        'fff-bubble-choice-field',
        'fff-bubble-choice-field--sm',
        'fff-bubble-choice-field--outline',
        'fff-bubble-choice-field--shape-grow',
    );
});

it('includes a playground demo for bubble choice', function () {
    $playground = new BubbleChoicePlayground;

    expect($playground->defaultState()['bubble_choice__habits'])->toBe(['journal', 'workout', 'on_track']);

    $source = file_get_contents(__DIR__.'/../../src/Support/Playground/BubbleChoicePlayground.php');

    expect($source)->toContain('BubbleChoiceField::make')
        ->and($source)->toContain('layoutOptions')
        ->and($source)->toContain('fringeWidth');
});

it('renders svg selection stroke geometry for image background bubbles', function () {
    $blade = file_get_contents(__DIR__.'/../../resources/views/forms/components/bubble-choice-field.blade.php');
    $defs = file_get_contents(__DIR__.'/../../resources/views/forms/components/partials/bubble-choice-clip-defs.blade.php');

    expect($blade)->toContain('fff-bubble-choice__selection-stroke')
        ->and($blade)->toContain('selectionStrokePath(')
        ->and($blade)->toContain('selectionStrokeWidth(')
        ->and($blade)->toContain('selectionStrokeStyle(')
        ->and($blade)->not->toContain('<use')
        ->and($defs)->toContain('id="fff-bubble-choice-geom-scallop"')
        ->and($defs)->toContain('id="fff-bubble-choice-geom-circle"');
});
