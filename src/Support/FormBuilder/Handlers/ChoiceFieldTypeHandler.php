<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCheckboxCards;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\DualListboxField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexChecklist;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexRadiolist;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\RatingField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SegmentControl;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SwitchField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\ChoiceCardsFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\ChoiceCheckboxCardsFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\DualListboxFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexChecklistFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexRadiolistFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\MatrixChoiceFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\RatingFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SegmentControlFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SelectFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SwitchFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\TagsFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\UserSelectFieldConfigurator;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Component;
use Spatie\Tags\Tag;

final class ChoiceFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly SwitchFieldConfigurator $switch = new SwitchFieldConfigurator,
        private readonly SegmentControlFieldConfigurator $segmentControl = new SegmentControlFieldConfigurator,
        private readonly ChoiceCardsFieldConfigurator $choiceCards = new ChoiceCardsFieldConfigurator,
        private readonly ChoiceCheckboxCardsFieldConfigurator $choiceCheckboxCards = new ChoiceCheckboxCardsFieldConfigurator,
        private readonly FlexChecklistFieldConfigurator $flexChecklist = new FlexChecklistFieldConfigurator,
        private readonly FlexRadiolistFieldConfigurator $flexRadiolist = new FlexRadiolistFieldConfigurator,
        private readonly MatrixChoiceFieldConfigurator $matrixChoice = new MatrixChoiceFieldConfigurator,
        private readonly SelectFieldConfigurator $select = new SelectFieldConfigurator,
        private readonly UserSelectFieldConfigurator $userSelect = new UserSelectFieldConfigurator,
        private readonly DualListboxFieldConfigurator $dualListbox = new DualListboxFieldConfigurator,
        private readonly TagsFieldConfigurator $tags = new TagsFieldConfigurator,
        private readonly RatingFieldConfigurator $rating = new RatingFieldConfigurator,
    ) {}

    protected function supportedTypesList(): array
    {
        return [
            FieldType::Toggle,
            FieldType::Checkbox,
            FieldType::CheckboxList,
            FieldType::Radio,
            FieldType::SegmentControl,
            FieldType::ChoiceCards,
            FieldType::ChoiceCheckboxCards,
            FieldType::FlexChecklist,
            FieldType::FlexRadiolist,
            FieldType::MatrixChoice,
            FieldType::Select,
            FieldType::MultiSelect,
            FieldType::UserSelect,
            FieldType::DualListbox,
            FieldType::Tags,
            FieldType::Rating,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        $config = $definition->config;

        return match ($definition->type) {
            FieldType::Toggle => $this->switch->configure(SwitchField::make($statePath), $config),
            FieldType::Checkbox => Checkbox::make($statePath),
            FieldType::CheckboxList => CheckboxList::make($statePath)->options($config['options'] ?? []),
            FieldType::Radio => Radio::make($statePath)->options($config['options'] ?? []),
            FieldType::SegmentControl => $this->segmentControl->configure(SegmentControl::make($statePath), $config),
            FieldType::ChoiceCards => $this->choiceCards->configure(ChoiceCards::make($statePath), $config),
            FieldType::ChoiceCheckboxCards => $this->choiceCheckboxCards->configure(ChoiceCheckboxCards::make($statePath), $config),
            FieldType::FlexChecklist => $this->flexChecklist->configure(FlexChecklist::make($statePath), $config),
            FieldType::FlexRadiolist => $this->flexRadiolist->configure(FlexRadiolist::make($statePath), $config),
            FieldType::MatrixChoice => $this->matrixChoice->configure(MatrixChoiceField::make($statePath), $config),
            FieldType::Select => $this->select->configure(SelectField::make($statePath), $config),
            FieldType::MultiSelect => $this->select->configure(SelectField::make($statePath)->multiple(), $config),
            FieldType::UserSelect => $this->userSelect->configure(UserSelect::make($statePath), $config),
            FieldType::DualListbox => $this->dualListbox->configure(DualListboxField::make($statePath), $config),
            FieldType::Tags => $this->tags->configure(
                (($config['use_spatie_tags'] ?? false) && class_exists(Tag::class))
                    ? FlexSpatieTagsField::make($statePath)
                    : TagsField::make($statePath),
                $config,
            ),
            FieldType::Rating => $this->rating->configure(RatingField::make($statePath), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for choice handler."),
        };
    }
}
