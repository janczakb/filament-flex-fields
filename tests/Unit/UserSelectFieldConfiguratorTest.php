<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\UserSelectFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers\ChoiceFieldTypeHandler;
use Illuminate\Database\Eloquent\Model;

it('prefers optionModel over Studio static options via the form builder', function () {
    $handler = new ChoiceFieldTypeHandler;
    $definition = new FlexFieldDefinition(
        slug: 'assignee',
        label: 'Assignee',
        type: FieldType::UserSelect,
        config: [
            'option_model' => Model::class,
            'options' => [
                ['value' => 'static-1', 'label' => 'Static One'],
                ['value' => 'static-2', 'label' => 'Static Two'],
            ],
            'name_column' => 'name',
            'email_column' => 'email',
            'searchable' => true,
        ],
    );

    $field = $handler->make($definition, 'assignee');

    expect($field)->toBeInstanceOf(UserSelect::class)
        ->and($field->getUserModel())->toBe(Model::class)
        ->and($field->hasClientSideOptionList())->toBeFalse()
        ->and($field->hasDynamicOptions())->toBeTrue()
        ->and($field->isSearchable())->toBeTrue();
});

it('normalizes Studio list options when optionModel is absent', function () {
    $configurator = new UserSelectFieldConfigurator;
    $field = $configurator->configureUserSelectField(
        UserSelect::make('reviewer'),
        [
            'options' => [
                ['value' => 'jane', 'label' => 'Jane Cooper'],
                ['value' => 'john', 'label' => 'John Smith'],
            ],
            'name_column' => 'name',
        ],
    );

    expect($field->getUserModel())->toBeNull()
        ->and($field->getOptions())->toBe([
            'jane' => 'Jane Cooper',
            'john' => 'John Smith',
        ])
        ->and($field->hasClientSideOptionList())->toBeTrue();
});

it('accepts model alias for optionModel and does not double-enable multiple', function () {
    $handler = new ChoiceFieldTypeHandler;
    $definition = new FlexFieldDefinition(
        slug: 'team',
        label: 'Team',
        type: FieldType::UserSelect,
        config: [
            'model' => Model::class,
            'multiple' => true,
            'options' => [
                'ignored' => 'Should not drive client list',
            ],
            'max_visible_avatars' => 3,
        ],
    );

    $field = $handler->make($definition, 'team');

    expect($field)->toBeInstanceOf(UserSelect::class)
        ->and($field->getUserModel())->toBe(Model::class)
        ->and($field->isMultiple())->toBeTrue()
        ->and($field->getMaxVisibleAvatars())->toBe(3)
        ->and($field->hasClientSideOptionList())->toBeFalse();
});
