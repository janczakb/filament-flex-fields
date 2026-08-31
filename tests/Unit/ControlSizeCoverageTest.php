<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Enums\ControlSize;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Str;

const CONTROL_SIZE_EXEMPT_FIELDS = [
    'ChoiceCards',
    'ChoiceCheckboxCards',
    'FlexChecklist',
    'FlexDateTimeField',
    'FlexRadiolist',
    'MapPickerField',
    'SignatureField',
];

it('defines control size enum cases', function (): void {
    expect(array_map(fn (ControlSize $size): string => $size->value, ControlSize::cases()))
        ->toBe(['sm', 'md', 'lg'])
        ->and(ControlSize::default())->toBe(ControlSize::Md);
});

it('uses HasControlSize on primary track-based fields', function (): void {
    expect(class_uses_recursive(SelectField::class))
        ->toContain(HasControlSize::class)
        ->and(class_uses_recursive(FlexTextInput::class))
        ->toContain(HasControlSize::class)
        ->and(class_uses_recursive(NumberStepper::class))
        ->toContain(HasControlSize::class);
});

it('covers every form field component with control size or an exemption', function (): void {
    $componentsPath = dirname(__DIR__, 2).'/src/Filament/Forms/Components';
    $missing = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($componentsPath)) as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $relativePath = Str::after($file->getPathname(), $componentsPath.DIRECTORY_SEPARATOR);

        if (str_contains($relativePath, DIRECTORY_SEPARATOR.'Concerns'.DIRECTORY_SEPARATOR)
            || str_contains($relativePath, DIRECTORY_SEPARATOR.'RichEditor'.DIRECTORY_SEPARATOR)
            || str_contains($relativePath, DIRECTORY_SEPARATOR.'UserSelect'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        if (str_contains($relativePath, 'Spatie'.DIRECTORY_SEPARATOR)
            && ! class_exists(SpatieMediaLibraryFileUpload::class)) {
            continue;
        }

        $class = 'Bjanczak\\FilamentFlexFields\\Filament\\Forms\\Components\\'
            .str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Field::class)) {
            continue;
        }

        $shortName = $reflection->getShortName();

        if (in_array($shortName, CONTROL_SIZE_EXEMPT_FIELDS, true)) {
            continue;
        }

        if (! in_array(HasControlSize::class, class_uses_recursive($class), true)) {
            $missing[] = $shortName;
        }
    }

    sort($missing);

    expect($missing)->toBe([]);
});
