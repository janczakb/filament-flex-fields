<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

use Bjanczak\FilamentFlexFields\Enums\FieldTypeDefaults\FieldTypeDefaultConfigRegistry;
use Bjanczak\FilamentFlexFields\Support\Translations;

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
    case ImageChoiceCards = 'image_choice_cards';
    case FlexChecklist = 'flex_checklist';
    case FlexRadiolist = 'flex_radiolist';
    case MatrixChoice = 'matrix_choice';
    case Select = 'select';
    case UserSelect = 'user_select';
    case DualListbox = 'dual_listbox';
    case BubbleChoice = 'bubble_choice';
    case TodoList = 'todo_list';
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
    case Schedule = 'schedule';

    // Media & visual
    case Color = 'color';
    case ColorPresets = 'color_presets';
    case FlexColorPicker = 'flex_color_picker';
    case File = 'file';
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case VoiceNote = 'voice_note';
    case MapPicker = 'map_picker';
    case SocialLinks = 'social_links';
    case Signature = 'signature';
    case CreditCard = 'credit_card';
    case Barcode = 'barcode';

    // Advanced
    case Rating = 'rating';
    case Nps = 'nps';
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
            self::ImageChoiceCards,
            self::FlexChecklist,
            self::FlexRadiolist,
            self::MatrixChoice,
            self::Select,
            self::UserSelect,
            self::DualListbox,
            self::BubbleChoice,
            self::TodoList,
            self::Tags => FieldCategory::Choice,

            self::Date,
            self::Time,
            self::DateTime,
            self::DateRange,
            self::Duration,
            self::TimeRange,
            self::Month,
            self::Year,
            self::Schedule => FieldCategory::DateTime,

            self::Color,
            self::ColorPresets,
            self::FlexColorPicker,
            self::File,
            self::Image,
            self::Video,
            self::Audio,
            self::VoiceNote,
            self::MapPicker,
            self::SocialLinks,
            self::Signature,
            self::CreditCard,
            self::Barcode => FieldCategory::Media,

            self::Rating,
            self::Nps,
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
        return Translations::get("filament-flex-fields::default.field_types.{$this->value}");
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
            self::ImageChoiceCards => 'heroicon-o-photo',
            self::FlexChecklist => 'heroicon-o-clipboard-document-check',
            self::FlexRadiolist => 'heroicon-o-list-bullet',
            self::MatrixChoice => 'heroicon-o-table-cells',
            self::Select => 'heroicon-o-chevron-up-down',
            self::UserSelect => 'heroicon-o-user-circle',
            self::DualListbox => 'heroicon-o-arrows-right-left',
            self::BubbleChoice => 'heroicon-o-stop-circle',
            self::TodoList => 'heroicon-o-clipboard-document-list',
            self::Tags => 'heroicon-o-tag',

            self::Date => 'heroicon-o-calendar',
            self::Time => 'heroicon-o-clock',
            self::DateTime => 'heroicon-o-calendar-days',
            self::DateRange => 'heroicon-o-calendar-date-range',
            self::Duration => 'heroicon-o-hourglass',
            self::TimeRange => 'heroicon-o-arrows-right-left',
            self::Month => 'heroicon-o-calendar-days',
            self::Year => 'heroicon-o-calendar',
            self::Schedule => 'heroicon-o-calendar-days',

            self::Color => 'heroicon-o-swatch',
            self::ColorPresets => 'heroicon-o-paint-brush',
            self::FlexColorPicker => 'heroicon-o-eye-dropper',
            self::File => 'heroicon-o-paper-clip',
            self::Image => 'heroicon-o-photo',
            self::Video => 'heroicon-o-video-camera',
            self::Audio => 'heroicon-o-speaker-wave',
            self::VoiceNote => 'heroicon-o-microphone',
            self::MapPicker => 'heroicon-o-map-pin',
            self::SocialLinks => 'heroicon-o-share',
            self::Signature => 'heroicon-o-pencil-square',
            self::CreditCard => 'heroicon-o-credit-card',
            self::Barcode => 'heroicon-o-qr-code',

            self::Rating => 'heroicon-o-star',
            self::Nps => 'heroicon-o-chart-bar',
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
            self::ImageChoiceCards,
            self::FlexChecklist,
            self::FlexRadiolist,
            self::MatrixChoice,
            self::RangeSlider,
            self::FlexSlider,
            self::Toggle,
            self::TrafficSplit,
            self::Color,
            self::ColorPresets,
            self::FlexColorPicker,
            self::DualListbox,
            self::BubbleChoice,
            self::TodoList,
            self::Tags,
            self::PriceRange,
            self::FlexTextarea,
            self::FlexTextInput,
            self::VerificationCode,
            self::IconPicker,
            self::CreditCard,
            self::Barcode,
            self::Phone,
            self::Country,
            self::Timezone,
            self::AddressAutocomplete,
            self::Currency,
            self::Video,
            self::Audio,
            self::VoiceNote,
            self::MapPicker,
            self::SocialLinks,
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
            self::Schedule,
            self::Nps,
            self::Rating,
        ], true);
    }

    /**
     * Lazy Flex Fields CSS/JS component ids used when this field type is rendered
     * (including shared dependency roots such as phone-field → flex-text-input).
     *
     * Empty list = native Filament control with no flex-fields lazy bundle.
     * Expand deps via FlexFieldAssets::stylesheetsFor() / alpineChunksFor().
     *
     * @return list<string>
     */
    public function assetComponents(): array
    {
        return match ($this) {
            self::Phone => ['phone-field'],
            self::Country => ['country-field'],
            self::Timezone => ['timezone-field'],
            self::Slug => ['slug-field'],
            self::AddressAutocomplete => ['address-autocomplete'],
            self::VerificationCode => ['flex-verification-code'],
            self::IconPicker => ['icon-picker-field'],
            self::FlexTextInput,
            self::Email,
            self::Url,
            self::Integer,
            self::Decimal => ['flex-text-input'],
            self::FlexTextarea => ['flex-textarea'],
            self::RichText => ['rich-editor-field'],
            self::CreditCard => ['credit-card'],
            self::Barcode => ['barcode-scanner-field'],
            self::Currency => ['currency-field'],
            self::NumberStepper => ['number-stepper'],
            self::Percentage,
            self::RangeSlider,
            self::RangeMinMax => ['track-slider'],
            self::FlexSlider => ['flex-slider'],
            self::PriceRange => ['price-range'],
            self::TrafficSplit => ['traffic-split'],
            self::Toggle => ['switch'],
            self::SegmentControl => ['segment-control'],
            self::ChoiceCards,
            self::ChoiceCheckboxCards => ['choice-cards'],
            self::ImageChoiceCards => ['image-choice-cards'],
            self::FlexChecklist => ['flex-checklist'],
            self::FlexRadiolist => ['flex-radiolist'],
            self::MatrixChoice => ['matrix-choice-field'],
            self::Select => ['select-field'],
            self::UserSelect => ['user-select'],
            self::DualListbox => ['dual-listbox'],
            self::BubbleChoice => ['bubble-choice'],
            self::TodoList => ['todo-list-field'],
            self::Tags => ['tags-field'],
            self::Date,
            self::DateTime,
            self::DateRange,
            self::Month,
            self::Year,
            self::Duration,
            self::TimeRange => ['flex-date-time-field'],
            self::Time => ['flex-date-time-field', 'flex-time-segments'],
            self::Schedule => ['schedule-field'],
            self::Color,
            self::ColorPresets => ['color-swatch'],
            self::FlexColorPicker => ['flex-color-picker'],
            self::File,
            self::Image => ['flex-file-upload'],
            self::Video => ['video-field'],
            self::Audio => ['audio-field'],
            self::VoiceNote => ['voice-note-recorder-field'],
            self::MapPicker => ['map-picker'],
            self::SocialLinks => ['social-links-field'],
            self::Signature => ['signature-field'],
            self::Rating => ['rating-field'],
            self::Nps => ['nps-field'],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultConfig(): array
    {
        return FieldTypeDefaultConfigRegistry::for($this);
    }

    public function supportsUserDefinedOptions(): bool
    {
        return match ($this) {
            self::Select,
            self::Radio,
            self::CheckboxList,
            self::SegmentControl,
            self::ChoiceCards,
            self::ChoiceCheckboxCards,
            self::ImageChoiceCards,
            self::FlexChecklist,
            self::FlexRadiolist,
            self::DualListbox,
            self::BubbleChoice,
            self::TodoList,
            self::Tags => true,
            default => false,
        };
    }

    public function requiresConfiguredOptions(): bool
    {
        return $this->supportsUserDefinedOptions() && ! $this->usesSuggestionsInsteadOfOptions();
    }

    public function usesSuggestionsInsteadOfOptions(): bool
    {
        return $this === self::Tags;
    }

    public function supportsRichFieldOptions(): bool
    {
        return match ($this) {
            self::SegmentControl,
            self::ChoiceCards,
            self::ChoiceCheckboxCards,
            self::ImageChoiceCards,
            self::FlexChecklist,
            self::FlexRadiolist,
            self::BubbleChoice,
            self::TodoList => true,
            default => false,
        };
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
