<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexSlider;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\NumberStepper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentFlexFields\Support\FlexFieldFormBuilder;

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
