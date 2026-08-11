<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NpsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexTextInputFieldConfigurator;

it('builds segment control and number stepper components from definitions', function () {
    $builder = new FlexFieldFormBuilder;

    $components = $builder->build([
        FlexFieldDefinition::fromArray([
            'slug' => 'plan',
            'label' => 'Plan',
            'type' => FieldType::SegmentControl->value,
            'config' => [
                'options' => ['basic' => 'Basic', 'pro' => 'Pro'],
                'size' => 'lg',
            ],
        ]),
        FlexFieldDefinition::fromArray([
            'slug' => 'quantity',
            'label' => 'Quantity',
            'type' => FieldType::NumberStepper->value,
            'config' => [
                'min' => 0,
                'max' => 10,
                'size' => 'sm',
            ],
        ]),
    ]);

    expect($components)->toHaveCount(2)
        ->and($components[0])->toBeInstanceOf(SegmentControl::class)
        ->and($components[0]->getSize())->toBe('lg')
        ->and($components[1])->toBeInstanceOf(NumberStepper::class)
        ->and($components[1]->getSize())->toBe('sm');
});

it('uses dedicated ui size defaults from config', function () {
    config()->set('filament-flex-fields.ui.number_stepper_size', 'lg');
    config()->set('filament-flex-fields.ui.segment_size', 'sm');

    $builder = new FlexFieldFormBuilder;

    $stepper = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'qty',
            'label' => 'Qty',
            'type' => FieldType::NumberStepper->value,
        ]),
    );

    $segment = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'mode',
            'label' => 'Mode',
            'type' => FieldType::SegmentControl->value,
            'config' => ['options' => ['a' => 'A']],
        ]),
    );

    expect($stepper)->toBeInstanceOf(NumberStepper::class)
        ->and($stepper->getSize())->toBe('lg')
        ->and($segment)->toBeInstanceOf(SegmentControl::class)
        ->and($segment->getSize())->toBe('sm');
});

it('builds verification code and flex slider components from definitions', function () {
    $builder = new FlexFieldFormBuilder;

    $verificationCode = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'otp',
            'label' => 'OTP',
            'type' => FieldType::VerificationCode->value,
            'config' => [
                'length' => 8,
                'groups' => [4, 4],
                'group_separator' => '-',
                'allowed_characters' => 'numeric',
                'size' => 'lg',
            ],
        ]),
    );

    $flexSlider = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'budget',
            'label' => 'Budget',
            'type' => FieldType::FlexSlider->value,
            'config' => [
                'min' => 0,
                'max' => 1000,
                'step' => 50,
                'prefix' => '$',
                'show_value' => true,
                'variant' => 'secondary',
            ],
        ]),
    );

    expect($verificationCode)->toBeInstanceOf(FlexVerificationCode::class)
        ->and($verificationCode->getLength())->toBe(8)
        ->and($verificationCode->getResolvedGroups())->toBe([4, 4])
        ->and($verificationCode->getSize())->toBe('lg')
        ->and($flexSlider)->toBeInstanceOf(FlexSlider::class)
        ->and($flexSlider->getMinValue())->toBe(0)
        ->and($flexSlider->getMaxValue())->toBe(1000)
        ->and($flexSlider->getStep())->toBe(50)
        ->and($flexSlider->getDisplayPrefix())->toBe('$')
        ->and($flexSlider->shouldShowValue())->toBeTrue()
        ->and($flexSlider->getVariant())->toBe('secondary');
});

it('builds a dual-handle flex slider when is_range is enabled', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'budget_range',
            'label' => 'Budget',
            'type' => FieldType::FlexSlider->value,
            'config' => [
                'min' => 0,
                'max' => 100,
                'step' => 10,
                'is_range' => true,
                'show_step_dots' => true,
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(FlexSlider::class)
        ->and($field->isRangeState())->toBeTrue()
        ->and($field->shouldUseRangeHandles())->toBeTrue()
        ->and($field->shouldShowStepDots())->toBeTrue()
        ->and($field->getNormalizedStateValues())->toHaveCount(2)
        ->and($field->getNormalizedStateValues()[0])->toBe(20.0)
        ->and($field->getNormalizedStateValues()[1])->toBe(80.0);
});

it('keeps dual handles when livewire state is still a scalar', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'budget_range_scalar',
            'label' => 'Budget',
            'type' => FieldType::FlexSlider->value,
            'config' => [
                'min' => 0,
                'max' => 100,
                'step' => 10,
                'is_range' => true,
            ],
            'default' => 0,
        ]),
    );

    expect($field)->toBeInstanceOf(FlexSlider::class)
        ->and($field->shouldUseRangeHandles())->toBeTrue()
        ->and($field->isRangeState())->toBeTrue()
        ->and($field->getNormalizedStateValues())->toHaveCount(2);
});

it('builds flex textarea toolbar selects and submit action from config', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'message',
            'label' => 'Message',
            'type' => FieldType::FlexTextarea->value,
            'config' => [
                'toolbar_select' => [
                    'state_path' => 'selected_model',
                    'options' => [
                        'gpt-5.4' => 'GPT-5.4',
                    ],
                    'placeholder' => 'Model',
                ],
                'submit_action' => [
                    'name' => 'send',
                    'label' => 'Send',
                    'icon' => 'heroicon-o-paper-airplane',
                ],
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(FlexTextareaField::class)
        ->and($field->getToolbarSelects())->toHaveCount(1)
        ->and($field->getToolbarSelects()[0]['statePath'])->toBe('selected_model')
        ->and($field->getSubmitActionNames())->toBe(['send']);
});

it('builds flex text input native affixes mask trim and read only from config', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'website',
            'label' => 'Website',
            'type' => FieldType::FlexTextInput->value,
            'config' => [
                'prefix' => 'https://',
                'suffix' => '.com',
                'suffix_icon' => 'heroicon-o-globe-alt',
                'suffix_icon_color' => '#16A34A',
                'mask_preset' => 'date',
                'trim' => true,
                'read_only' => true,
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(FlexTextInput::class)
        ->and($field->getPrefixLabel())->toBe('https://')
        ->and($field->getSuffixLabel())->toBe('.com')
        ->and($field->getSuffixIcon())->toBe('heroicon-o-globe-alt')
        ->and($field->getSuffixIconColor())->toBeArray()
        ->and($field->getMask())->toBe('99/99/9999')
        ->and($field->isTrimmed())->toBeTrue()
        ->and($field->isReadOnly())->toBeTrue();
});

it('applies hex suffix icon colors via Filament color palettes', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'brand',
            'label' => 'Brand',
            'type' => FieldType::FlexTextInput->value,
            'config' => [
                'suffix_icon' => 'heroicon-o-globe-alt',
                'suffix_icon_color' => '#16A34A',
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(FlexTextInput::class)
        ->and($field->getSuffixIconColor())->toBeArray()
        ->and($field->getSuffixIconColor()[500] ?? null)->not->toBeNull();
});

it('resolves custom flex text input masks from config', function () {
    expect(FlexTextInputFieldConfigurator::resolveMask([
        'mask_preset' => 'custom',
        'mask' => 'AAA-999',
    ]))->toBe('AAA-999')
        ->and(FlexTextInputFieldConfigurator::resolveMask([
            'mask_preset' => 'phone',
        ]))->toBe('(999) 999-9999')
        ->and(FlexTextInputFieldConfigurator::resolveMask([
            'mask_preset' => '',
            'mask' => '',
        ]))->toBeNull();
});

it('normalizes studio list options for choice cards', function () {
    $builder = new FlexFieldFormBuilder;

    $field = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'gender',
            'label' => 'Gender',
            'type' => FieldType::ChoiceCards->value,
            'config' => [
                'size' => 'md',
                'variant' => 'default',
                'options' => [
                    ['value' => 'male', 'label' => 'Male'],
                    ['value' => 'female', 'label' => 'Female', 'description' => 'Woman'],
                ],
            ],
        ]),
    );

    expect($field)->toBeInstanceOf(ChoiceCards::class)
        ->and($field->getOptionKeys())->toBe(['male', 'female'])
        ->and($field->getNormalizedOptions()['male']['label'])->toBe('Male')
        ->and($field->getNormalizedOptions()['female']['description'])->toBe('Woman');
});

it('builds nps field components from definitions', function () {
    $builder = new FlexFieldFormBuilder;

    $nps = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'score',
            'label' => 'NPS',
            'type' => FieldType::Nps->value,
            'config' => [
                'variant' => 'segments',
                'color_coded' => true,
                'options' => [
                    ['value' => '0', 'label' => '0'],
                    ['value' => '1', 'label' => '1'],
                    ['value' => '10', 'label' => '10'],
                ],
                'min_label' => 'Low',
                'max_label' => 'High',
                'size' => 'lg',
            ],
        ]),
    );

    expect($nps)->toBeInstanceOf(NpsField::class)
        ->and($nps->getVariant())->toBe('segments')
        ->and($nps->isColorCoded())->toBeTrue()
        ->and($nps->getSize())->toBe('lg')
        ->and($nps->getMinLabel())->toBe('Low')
        ->and($nps->getMaxLabel())->toBe('High')
        ->and($nps->getOptions())->toBe([
            '0' => '0',
            '1' => '1',
            '10' => '10',
        ]);
});

it('builds nps options from scale_max and forces five emoji moods', function () {
    $builder = new FlexFieldFormBuilder;

    $scaled = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'scaled',
            'label' => 'NPS',
            'type' => FieldType::Nps->value,
            'config' => [
                'variant' => 'pills',
                'scale_max' => 5,
                'options' => [],
            ],
        ]),
    );

    $emojis = $builder->makeComponent(
        FlexFieldDefinition::fromArray([
            'slug' => 'mood',
            'label' => 'Mood',
            'type' => FieldType::Nps->value,
            'config' => [
                'variant' => 'emojis',
                'scale_max' => 10,
                'options' => [
                    ['value' => '0', 'label' => 'Terrible'],
                    ['value' => '1', 'label' => 'Bad'],
                    ['value' => '2', 'label' => 'Okay'],
                    ['value' => '3', 'label' => 'Great'],
                    ['value' => '4', 'label' => 'Amazing'],
                ],
            ],
        ]),
    );

    expect($scaled->getOptions())->toBe([
        0 => 0,
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 5,
    ])
        ->and($emojis->getOptions())->toBe([
            0 => 'Terrible',
            1 => 'Bad',
            2 => 'Okay',
            3 => 'Great',
            4 => 'Amazing',
        ]);
});
