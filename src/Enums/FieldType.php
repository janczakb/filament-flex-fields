<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

use Bjanczak\FilamentFlexFields\Enums\FieldTypeDefaults\FieldTypeDefaultConfigRegistry;

enum FieldType: string
{
    // Text
    case SingleLineText = 'single_line_text';
    case MultiLineText = 'multi_line_text';
    case FlexTextarea = 'flex_textarea';
    case FlexTextInput = 'flex_text_input';
    case RichText = 'rich_text';
    case Markdown = 'markdown';
    case Email = 'email';
    case Url = 'url';
    case Phone = 'phone';
    case Country = 'country';
    case Timezone = 'timezone';
    case Password = 'password';
    case Slug = 'slug';
    case Search = 'search';
    case AddressAutocomplete = 'address_autocomplete';
    case VerificationCode = 'verification_code';
    case IconPicker = 'icon_picker';

    // Number
    case Integer = 'integer';
    case Decimal = 'decimal';
    case NumberStepper = 'number_stepper';
    case Currency = 'currency';
    case Percentage = 'percentage';
    case RangeSlider = 'range_slider';
    case RangeMinMax = 'range_min_max';
    case FlexSlider = 'flex_slider';
    case PriceRange = 'price_range';
    case TrafficSplit = 'traffic_split';

    // Choice
    case Toggle = 'toggle';
    case Checkbox = 'checkbox';
    case CheckboxList = 'checkbox_list';
    case Radio = 'radio';
    case SegmentControl = 'segment_control';
    case ChoiceCards = 'choice_cards';
    case ChoiceCheckboxCards = 'choice_checkbox_cards';
    case FlexChecklist = 'flex_checklist';
    case FlexRadiolist = 'flex_radiolist';
    case MatrixChoice = 'matrix_choice';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case UserSelect = 'user_select';
    case DualListbox = 'dual_listbox';
    case Tags = 'tags';

    // Date & time
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'date_time';
    case DateRange = 'date_range';
    case Duration = 'duration';
    case TimeRange = 'time_range';
    case Month = 'month';
    case Year = 'year';

    // Media & visual
    case Color = 'color';
    case ColorPresets = 'color_presets';
    case FlexColorPicker = 'flex_color_picker';
    case File = 'file';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case MapPicker = 'map_picker';
    case Signature = 'signature';
    case CreditCard = 'credit_card';

    // Advanced
    case Rating = 'rating';
    case KeyValue = 'key_value';
    case Repeater = 'repeater';
    case Code = 'code';
    case Json = 'json';
    case Hidden = 'hidden';
    case ReadOnly = 'read_only';

    public function category(): FieldCategory
    {
        return match ($this) {
            self::SingleLineText,
            self::MultiLineText,
            self::FlexTextarea,
            self::FlexTextInput,
            self::RichText,
            self::Markdown,
            self::Email,
            self::Url,
            self::Phone,
            self::Country,
            self::Timezone,
            self::Password,
            self::Slug,
            self::Search,
            self::AddressAutocomplete,
            self::VerificationCode,
            self::IconPicker => FieldCategory::Text,

            self::Integer,
            self::Decimal,
            self::NumberStepper,
            self::Currency,
            self::Percentage,
            self::RangeSlider,
            self::RangeMinMax,
            self::FlexSlider,
            self::PriceRange,
            self::TrafficSplit => FieldCategory::Number,

            self::Toggle,
            self::Checkbox,
            self::CheckboxList,
            self::Radio,
            self::SegmentControl,
            self::ChoiceCards,
            self::ChoiceCheckboxCards,
            self::FlexChecklist,
            self::FlexRadiolist,
            self::MatrixChoice,
            self::Select,
            self::MultiSelect,
            self::UserSelect,
            self::DualListbox,
            self::Tags => FieldCategory::Choice,

            self::Date,
            self::Time,
            self::DateTime,
            self::DateRange,
            self::Duration,
            self::TimeRange,
            self::Month,
            self::Year => FieldCategory::DateTime,

            self::Color,
            self::ColorPresets,
            self::FlexColorPicker,
            self::File,
            self::Image,
            self::Video,
            self::Audio,
            self::MapPicker,
            self::Signature,
            self::CreditCard => FieldCategory::Media,

            self::Rating,
            self::KeyValue,
            self::Repeater,
            self::Code,
            self::Json,
            self::Hidden,
            self::ReadOnly => FieldCategory::Advanced,
        };
    }

    public function label(): string
    {
        return __("filament-flex-fields::default.field_types.{$this->value}");
    }

    public function icon(): string
    {
        return match ($this) {
            self::SingleLineText => 'heroicon-o-minus',
            self::MultiLineText => 'heroicon-o-bars-3-bottom-left',
            self::FlexTextarea => 'heroicon-o-chat-bubble-bottom-center-text',
            self::FlexTextInput => 'heroicon-o-bars-3-bottom-left',
            self::RichText => 'heroicon-o-document-text',
            self::Markdown => 'heroicon-o-hashtag',
            self::Email => 'heroicon-o-envelope',
            self::Url => 'heroicon-o-link',
            self::Phone => 'heroicon-o-phone',
            self::Country => 'heroicon-o-globe-alt',
            self::Timezone => 'heroicon-o-clock',
            self::Password => 'heroicon-o-lock-closed',
            self::Slug => 'heroicon-o-at-symbol',
            self::Search => 'heroicon-o-magnifying-glass',
            self::AddressAutocomplete => 'heroicon-o-map-pin',
            self::VerificationCode => 'heroicon-o-shield-check',
            self::IconPicker => 'heroicon-o-sparkles',

            self::Integer => 'heroicon-o-hashtag',
            self::Decimal => 'heroicon-o-variable',
            self::NumberStepper => 'heroicon-o-plus-circle',
            self::Currency => 'heroicon-o-currency-dollar',
            self::Percentage => 'heroicon-o-percent-badge',
            self::RangeSlider => 'heroicon-o-adjustments-horizontal',
            self::RangeMinMax => 'heroicon-o-arrows-right-left',
            self::FlexSlider => 'heroicon-o-bars-3',
            self::PriceRange => 'heroicon-o-chart-bar',
            self::TrafficSplit => 'heroicon-o-chart-pie',

            self::Toggle => 'heroicon-o-power',
            self::Checkbox => 'heroicon-o-check-circle',
            self::CheckboxList => 'heroicon-o-queue-list',
            self::Radio => 'heroicon-o-radio',
            self::SegmentControl => 'heroicon-o-view-columns',
            self::ChoiceCards => 'heroicon-o-rectangle-stack',
            self::ChoiceCheckboxCards => 'heroicon-o-squares-plus',
            self::FlexChecklist => 'heroicon-o-clipboard-document-check',
            self::FlexRadiolist => 'heroicon-o-list-bullet',
            self::MatrixChoice => 'heroicon-o-table-cells',
            self::Select => 'heroicon-o-chevron-up-down',
            self::MultiSelect => 'heroicon-o-squares-2x2',
            self::UserSelect => 'heroicon-o-user-circle',
            self::DualListbox => 'heroicon-o-arrows-right-left',
            self::Tags => 'heroicon-o-tag',

            self::Date => 'heroicon-o-calendar',
            self::Time => 'heroicon-o-clock',
            self::DateTime => 'heroicon-o-calendar-days',
            self::DateRange => 'heroicon-o-calendar-date-range',
            self::Duration => 'heroicon-o-hourglass',
            self::TimeRange => 'heroicon-o-arrows-right-left',
            self::Month => 'heroicon-o-calendar-days',
            self::Year => 'heroicon-o-calendar',

            self::Color => 'heroicon-o-swatch',
            self::ColorPresets => 'heroicon-o-paint-brush',
            self::FlexColorPicker => 'heroicon-o-eye-dropper',
            self::File => 'heroicon-o-paper-clip',
            self::Image => 'heroicon-o-photo',
            self::Video => 'heroicon-o-video-camera',
            self::Audio => 'heroicon-o-microphone',
            self::MapPicker => 'heroicon-o-map-pin',
            self::Signature => 'heroicon-o-pencil-square',
            self::CreditCard => 'heroicon-o-credit-card',

            self::Rating => 'heroicon-o-star',
            self::KeyValue => 'heroicon-o-table-cells',
            self::Repeater => 'heroicon-o-rectangle-stack',
            self::Code => 'heroicon-o-code-bracket',
            self::Json => 'heroicon-o-brackets-curly',
            self::Hidden => 'heroicon-o-eye-slash',
            self::ReadOnly => 'heroicon-o-eye',
        };
    }

    public function isCustomComponent(): bool
    {
        return in_array($this, [
            self::NumberStepper,
            self::SegmentControl,
            self::ChoiceCards,
            self::ChoiceCheckboxCards,
            self::FlexChecklist,
            self::FlexRadiolist,
            self::MatrixChoice,
            self::RangeSlider,
            self::FlexSlider,
            self::Toggle,
            self::TrafficSplit,
            self::ColorPresets,
            self::FlexColorPicker,
            self::DualListbox,
            self::Tags,
            self::PriceRange,
            self::FlexTextarea,
            self::FlexTextInput,
            self::VerificationCode,
            self::IconPicker,
            self::CreditCard,
            self::Phone,
            self::Country,
            self::Timezone,
            self::AddressAutocomplete,
            self::Currency,
            self::Video,
            self::Audio,
            self::MapPicker,
            self::UserSelect,
            self::Slug,
            self::Date,
            self::Time,
            self::DateTime,
            self::DateRange,
            self::Duration,
            self::TimeRange,
            self::Month,
            self::Year,
        ], true);
    }

    public function defaultConfig(): array
    {
        return FieldTypeDefaultConfigRegistry::for($this);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function forCategory(FieldCategory $category): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $type): bool => $type->category() === $category,
        ));
    }
}
