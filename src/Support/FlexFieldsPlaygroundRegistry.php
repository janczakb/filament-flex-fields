<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldsPlaygroundComponentPage;
use Bjanczak\FilamentFlexFields\Support\Playground\AddressAutocompletePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\AudioFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ChoiceCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ChoiceCheckboxCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ColorSwatchPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CountryFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CoverCardPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CreditCardPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CurrencyFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\DateTimeFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\DualListboxPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexChecklistPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexColorPickerPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFileUploadPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexRadiolistPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexSliderPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexTextareaPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexTextInputPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexVerificationCodePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FocusOutlinePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FormLayoutPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ItemCardGroupPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\MapPickerPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\MatrixChoiceFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\NumberStepperPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PhoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PriceRangePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ProgressBarPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ProgressCirclePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\RatingColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\RatingPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SegmentControlPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SegmentTabsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SelectPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SignatureFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SlugFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SwitchPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TagsFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TimezoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TrackSliderPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TrafficSplitPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TranslatableFieldsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\UserColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\UserSelectPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\VideoFieldPlayground;
use Filament\Pages\PageConfiguration;

class FlexFieldsPlaygroundRegistry
{
    /**
     * @var array<string, array{label: string, playground: class-string, sort: int, icon: string}>|null
     */
    private static ?array $definitionsCache = null;

    /**
     * @var array<string, array{label: string, playground: class-string, sort: int, icon: string}>|null
     */
    private static ?array $orderedCache = null;

    /**
     * @return array<string, array{label: string, playground: class-string, sort: int, icon: string}>
     */
    public static function definitions(): array
    {
        if (self::$definitionsCache === null) {
            self::$definitionsCache = [
                'focus-outline' => ['label' => 'Focus outline', 'playground' => FocusOutlinePlayground::class, 'sort' => 10, 'icon' => GravityIcon::Eye],
                'phone-field' => ['label' => 'Phone field', 'playground' => PhoneFieldPlayground::class, 'sort' => 20, 'icon' => GravityIcon::Handset],
                'country-field' => ['label' => 'Country field', 'playground' => CountryFieldPlayground::class, 'sort' => 30, 'icon' => GravityIcon::Globe],
                'timezone-field' => ['label' => 'Timezone field', 'playground' => TimezoneFieldPlayground::class, 'sort' => 40, 'icon' => GravityIcon::Clock],
                'currency-field' => ['label' => 'Currency field', 'playground' => CurrencyFieldPlayground::class, 'sort' => 50, 'icon' => GravityIcon::CircleDollar],
                'number-stepper' => ['label' => 'Number Stepper', 'playground' => NumberStepperPlayground::class, 'sort' => 60, 'icon' => GravityIcon::Plus],
                'choice-cards' => ['label' => 'Choice Cards', 'playground' => ChoiceCardsPlayground::class, 'sort' => 70, 'icon' => GravityIcon::LayoutCells],
                'choice-checkbox-cards' => ['label' => 'Choice Checkbox Cards', 'playground' => ChoiceCheckboxCardsPlayground::class, 'sort' => 80, 'icon' => GravityIcon::Check],
                'segment-control' => ['label' => 'Segment Control', 'playground' => SegmentControlPlayground::class, 'sort' => 90, 'icon' => GravityIcon::ChartColumn],
                'segment-tabs' => ['label' => 'Segment Tabs', 'playground' => SegmentTabsPlayground::class, 'sort' => 100, 'icon' => GravityIcon::LayoutColumns],
                'form-layouts' => ['label' => 'Modern form layouts', 'playground' => FormLayoutPlayground::class, 'sort' => 110, 'icon' => GravityIcon::Display],
                'track-slider' => ['label' => 'Track Slider', 'playground' => TrackSliderPlayground::class, 'sort' => 120, 'icon' => GravityIcon::ChartBar],
                'flex-slider' => ['label' => 'Flex Slider', 'playground' => FlexSliderPlayground::class, 'sort' => 130, 'icon' => GravityIcon::SquareChartBar],
                'traffic-split' => ['label' => 'Traffic Split', 'playground' => TrafficSplitPlayground::class, 'sort' => 140, 'icon' => GravityIcon::ChartColumn],
                'switch' => ['label' => 'Switch', 'playground' => SwitchPlayground::class, 'sort' => 150, 'icon' => GravityIcon::Thunderbolt],
                'select-field' => ['label' => 'SelectField', 'playground' => SelectPlayground::class, 'sort' => 160, 'icon' => GravityIcon::CircleChevronDown],
                'user-select' => ['label' => 'UserSelect', 'playground' => UserSelectPlayground::class, 'sort' => 170, 'icon' => GravityIcon::Person],
                'user-column' => ['label' => 'UserColumn', 'playground' => UserColumnPlayground::class, 'sort' => 180, 'icon' => GravityIcon::Persons],
                'rating' => ['label' => 'Rating', 'playground' => RatingPlayground::class, 'sort' => 190, 'icon' => GravityIcon::Star],
                'rating-column' => ['label' => 'RatingColumn', 'playground' => RatingColumnPlayground::class, 'sort' => 200, 'icon' => GravityIcon::Star],
                'dual-listbox' => ['label' => 'Dual Listbox', 'playground' => DualListboxPlayground::class, 'sort' => 210, 'icon' => GravityIcon::ArrowRightArrowLeft],
                'price-range' => ['label' => 'Price Range', 'playground' => PriceRangePlayground::class, 'sort' => 220, 'icon' => GravityIcon::CircleDollar],
                'flex-textarea' => ['label' => 'Flex Textarea', 'playground' => FlexTextareaPlayground::class, 'sort' => 230, 'icon' => GravityIcon::FileText],
                'flex-text-input' => ['label' => 'Flex text input', 'playground' => FlexTextInputPlayground::class, 'sort' => 240, 'icon' => GravityIcon::PencilToSquare],
                'slug-field' => ['label' => 'Slug field', 'playground' => SlugFieldPlayground::class, 'sort' => 250, 'icon' => GravityIcon::Link],
                'translatable-fields' => ['label' => 'Translatable Fields', 'playground' => TranslatableFieldsPlayground::class, 'sort' => 260, 'icon' => GravityIcon::Globe],
                'date-time-fields' => ['label' => 'Date & time fields', 'playground' => DateTimeFieldPlayground::class, 'sort' => 270, 'icon' => GravityIcon::Calendar],
                'credit-card' => ['label' => 'Credit card', 'playground' => CreditCardPlayground::class, 'sort' => 280, 'icon' => GravityIcon::CreditCard],
                'color-swatch' => ['label' => 'Color swatch', 'playground' => ColorSwatchPlayground::class, 'sort' => 290, 'icon' => GravityIcon::Palette],
                'flex-color-picker' => ['label' => 'Flex color picker', 'playground' => FlexColorPickerPlayground::class, 'sort' => 300, 'icon' => GravityIcon::Palette],
                'file-upload' => ['label' => 'File upload', 'playground' => FlexFileUploadPlayground::class, 'sort' => 310, 'icon' => GravityIcon::CloudArrowUpIn],
                'video-field' => ['label' => 'Video field', 'playground' => VideoFieldPlayground::class, 'sort' => 320, 'icon' => GravityIcon::Video],
                'audio-field' => ['label' => 'Audio field', 'playground' => AudioFieldPlayground::class, 'sort' => 330, 'icon' => GravityIcon::VolumeFill],
                'map-picker' => ['label' => 'Map picker', 'playground' => MapPickerPlayground::class, 'sort' => 340, 'icon' => GravityIcon::MapPin],
                'address-autocomplete' => ['label' => 'Address autocomplete', 'playground' => AddressAutocompletePlayground::class, 'sort' => 350, 'icon' => GravityIcon::MapPin],
                'signature-field' => ['label' => 'Signature', 'playground' => SignatureFieldPlayground::class, 'sort' => 360, 'icon' => GravityIcon::Pencil],
                'verification-code' => ['label' => 'Verification Code', 'playground' => FlexVerificationCodePlayground::class, 'sort' => 370, 'icon' => GravityIcon::ShieldCheck],
                'flex-checklist' => ['label' => 'Flex Checklist', 'playground' => FlexChecklistPlayground::class, 'sort' => 380, 'icon' => GravityIcon::Check],
                'flex-radiolist' => ['label' => 'Flex Radiolist', 'playground' => FlexRadiolistPlayground::class, 'sort' => 390, 'icon' => GravityIcon::Circles3Plus],
                'matrix-choice' => ['label' => 'Matrix Choice', 'playground' => MatrixChoiceFieldPlayground::class, 'sort' => 395, 'icon' => GravityIcon::LayoutCells],
                'tags-field' => ['label' => 'Tags field', 'playground' => TagsFieldPlayground::class, 'sort' => 398, 'icon' => GravityIcon::make('tag')],
                'item-card-group' => ['label' => 'ItemCardGroup', 'playground' => ItemCardGroupPlayground::class, 'sort' => 400, 'icon' => GravityIcon::LayoutCells],
                'cover-card' => ['label' => 'Cover card', 'playground' => CoverCardPlayground::class, 'sort' => 410, 'icon' => GravityIcon::CopyPicture],
                'progress-bar' => ['label' => 'Progress bar', 'playground' => ProgressBarPlayground::class, 'sort' => 420, 'icon' => GravityIcon::ChartBar],
                'progress-circle' => ['label' => 'Progress circle', 'playground' => ProgressCirclePlayground::class, 'sort' => 430, 'icon' => GravityIcon::ChartColumn],
            ];
        }

        return self::$definitionsCache;
    }

    /**
     * @return array<string, array{label: string, playground: class-string, sort: int, icon: string}>
     */
    public static function ordered(): array
    {
        if (self::$orderedCache === null) {
            $definitions = static::definitions();

            uasort(
                $definitions,
                fn (array $left, array $right): int => $left['sort'] <=> $right['sort'],
            );

            self::$orderedCache = $definitions;
        }

        return self::$orderedCache;
    }

    /**
     * @return array{label: string, playground: class-string, sort: int, icon: string}|null
     */
    public static function find(?string $slug): ?array
    {
        if (blank($slug)) {
            return null;
        }

        return static::definitions()[$slug] ?? null;
    }

    public static function firstSlug(): ?string
    {
        return array_key_first(static::ordered());
    }

    /**
     * @return list<PageConfiguration>
     */
    public static function pageConfigurations(): array
    {
        if (! static::isEnabled()) {
            return [];
        }

        return array_map(
            fn (string $slug): PageConfiguration => PageConfiguration::make(
                FlexFieldsPlaygroundComponentPage::class,
                $slug,
            )->slug($slug),
            array_keys(static::ordered()),
        );
    }

    public static function isEnabled(): bool
    {
        return FlexFieldsConfig::isPlaygroundEnabled();
    }
}
