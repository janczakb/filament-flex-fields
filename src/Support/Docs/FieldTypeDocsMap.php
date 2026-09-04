<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Docs;

use Bjanczak\FilamentFlexFields\Enums\FieldType;

/**
 * Maps every {@see FieldType} schema value to a Mintlify doc path under `docs/`.
 */
final class FieldTypeDocsMap
{
    /**
     * @var array<string, string>
     */
    private const DOCS_BY_TYPE = [
        FieldType::SingleLineText->value => 'flextextinput.md',
        FieldType::MultiLineText->value => 'flextextareafield.md',
        FieldType::FlexTextarea->value => 'flextextareafield.md',
        FieldType::FlexTextInput->value => 'flextextinput.md',
        FieldType::RichText->value => 'flex-rich-editor.md',
        FieldType::Markdown->value => 'flex-rich-editor.md',
        FieldType::Email->value => 'flextextinput.md',
        FieldType::Url->value => 'flextextinput.md',
        FieldType::Phone->value => 'phonefield.md',
        FieldType::Country->value => 'countryfield.md',
        FieldType::Timezone->value => 'timezonefield.md',
        FieldType::Password->value => 'flextextinput.md',
        FieldType::Slug->value => 'slugfield-and-titleslugfield.md',
        FieldType::Search->value => 'flextextinput.md',
        FieldType::AddressAutocomplete->value => 'addressautocompletefield.md',
        FieldType::VerificationCode->value => 'flexverificationcode.md',
        FieldType::IconPicker->value => 'icon-picker-field.md',
        FieldType::Integer->value => 'numberstepper.md',
        FieldType::Decimal->value => 'numberstepper.md',
        FieldType::NumberStepper->value => 'numberstepper.md',
        FieldType::Currency->value => 'currencyfield.md',
        FieldType::Percentage->value => 'trackslider.md',
        FieldType::RangeSlider->value => 'trackslider.md',
        FieldType::RangeMinMax->value => 'trackslider.md',
        FieldType::FlexSlider->value => 'flexslider.md',
        FieldType::PriceRange->value => 'pricerangefield.md',
        FieldType::TrafficSplit->value => 'trafficsplit.md',
        FieldType::Toggle->value => 'switchfield.md',
        FieldType::Checkbox->value => 'switchfield.md',
        FieldType::CheckboxList->value => 'flexchecklist.md',
        FieldType::Radio->value => 'flexradiolist.md',
        FieldType::SegmentControl->value => 'segmentcontrol.md',
        FieldType::ChoiceCards->value => 'choicecards.md',
        FieldType::ChoiceCheckboxCards->value => 'choicecheckboxcards.md',
        FieldType::ImageChoiceCards->value => 'imagechoicecards.md',
        FieldType::FlexChecklist->value => 'flexchecklist.md',
        FieldType::FlexRadiolist->value => 'flexradiolist.md',
        FieldType::MatrixChoice->value => 'matrixchoicefield.md',
        FieldType::Select->value => 'selectfield.md',
        FieldType::UserSelect->value => 'userselect.md',
        FieldType::DualListbox->value => 'duallistboxfield.md',
        FieldType::BubbleChoice->value => 'bubblechoicefield.md',
        FieldType::TodoList->value => 'todolistfield.md',
        FieldType::Tags->value => 'tags-field.md',
        FieldType::Date->value => 'date-and-time-fields.md',
        FieldType::Time->value => 'date-and-time-fields.md',
        FieldType::DateTime->value => 'date-and-time-fields.md',
        FieldType::DateRange->value => 'date-and-time-fields.md',
        FieldType::Duration->value => 'date-and-time-fields.md',
        FieldType::TimeRange->value => 'date-and-time-fields.md',
        FieldType::Month->value => 'date-and-time-fields.md',
        FieldType::Year->value => 'date-and-time-fields.md',
        FieldType::Schedule->value => 'schedule-field.md',
        FieldType::Color->value => 'colorswatchfield.md',
        FieldType::ColorPresets->value => 'colorswatchfield.md',
        FieldType::FlexColorPicker->value => 'flexcolorpickerfield.md',
        FieldType::File->value => 'flexfileupload-and-fleximageupload.md',
        FieldType::Image->value => 'flexfileupload-and-fleximageupload.md',
        FieldType::Video->value => 'videofield.md',
        FieldType::Audio->value => 'audiofield.md',
        FieldType::VoiceNote->value => 'voicenoterecorderfield.md',
        FieldType::MapPicker->value => 'mappickerfield.md',
        FieldType::SocialLinks->value => 'social-links-field.md',
        FieldType::Signature->value => 'signaturefield.md',
        FieldType::CreditCard->value => 'creditcardfield.md',
        FieldType::Barcode->value => 'barcode-scanner-field.md',
        FieldType::Rating->value => 'ratingfield.md',
        FieldType::Nps->value => 'nps-field.md',
        FieldType::KeyValue->value => 'flex-field-groups.md',
        FieldType::Repeater->value => 'flex-field-groups.md',
        FieldType::Code->value => 'flex-field-groups.md',
        FieldType::Json->value => 'flex-field-groups.md',
        FieldType::Hidden->value => 'shared-concepts.md',
        FieldType::ReadOnly->value => 'shared-concepts.md',
    ];

    public static function docsPathFor(FieldType $type): string
    {
        return self::DOCS_BY_TYPE[$type->value];
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::DOCS_BY_TYPE;
    }

    public static function coversAllTypes(): bool
    {
        foreach (FieldType::cases() as $type) {
            if (! array_key_exists($type->value, self::DOCS_BY_TYPE)) {
                return false;
            }
        }

        return true;
    }
}
