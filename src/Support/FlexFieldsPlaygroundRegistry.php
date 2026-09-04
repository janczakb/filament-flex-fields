<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Enums\PlaygroundCategory;
use Bjanczak\FilamentFlexFields\Filament\Pages\FlexFieldsPlaygroundComponentPage;
use Bjanczak\FilamentFlexFields\Support\Playground\AddressAutocompletePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\AdminColumnsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\AudioFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\BarcodeScannerFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\BubbleChoicePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CalculatorFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ChoiceCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ChoiceCheckboxCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ColorSwatchPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CompositionRecipesPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CountryFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CoverCardPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CreditCardPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CurrencyFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\DateTimeFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\DualListboxPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FieldIntelligencePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexChecklistPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexColorPickerPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFileUploadPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexMatrixTablePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexRadiolistPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexRichEditorPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexSliderPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexTextareaPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexTextInputPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexVerificationCodePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FocusOutlinePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FormLayoutPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\HoldConfirmPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\IconColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\IconPickerFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ImageChoiceCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ItemCardGroupPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\LinkPreviewFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\MapPickerPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\MatrixChoiceFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\NpsFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\NumberStepperPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PhoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PriceRangePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ProgressBarPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ProgressCirclePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\RatingColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\RatingPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ScheduleFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SchemaConditionsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SegmentControlPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SegmentTabsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SelectPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SignatureFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SlugFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SocialLinksFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SwitchPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TagsFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TimezoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TodoListFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TrackSliderPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TrafficSplitPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TranslatableFieldsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\UserColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\UserSelectPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\VideoFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\VoiceNoteRecorderFieldPlayground;
use Filament\Pages\PageConfiguration;

class FlexFieldsPlaygroundRegistry
{
    /**
     * @var array<string, PlaygroundCategory>
     */
    private const CATEGORIES_BY_SLUG = [
        'focus-outline' => PlaygroundCategory::Guides,
        'schema-conditions' => PlaygroundCategory::Guides,
        'field-intelligence' => PlaygroundCategory::Guides,
        'composition-recipes' => PlaygroundCategory::Guides,
        'segment-tabs' => PlaygroundCategory::Navigation,
        'form-layouts' => PlaygroundCategory::Navigation,
        'hold-confirm' => PlaygroundCategory::Buttons,
        'select-field' => PlaygroundCategory::Pickers,
        'user-select' => PlaygroundCategory::Pickers,
        'country-field' => PlaygroundCategory::Pickers,
        'timezone-field' => PlaygroundCategory::Pickers,
        'phone-field' => PlaygroundCategory::Pickers,
        'icon-picker-field' => PlaygroundCategory::Pickers,
        'dual-listbox' => PlaygroundCategory::Pickers,
        'bubble-choice' => PlaygroundCategory::Pickers,
        'todo-list-field' => PlaygroundCategory::Collections,
        'tags-field' => PlaygroundCategory::Pickers,
        'address-autocomplete' => PlaygroundCategory::Pickers,
        'date-time-fields' => PlaygroundCategory::DateAndTime,
        'schedule-field' => PlaygroundCategory::DateAndTime,
        'color-swatch' => PlaygroundCategory::Colors,
        'flex-color-picker' => PlaygroundCategory::Colors,
        'switch' => PlaygroundCategory::Controls,
        'segment-control' => PlaygroundCategory::Controls,
        'number-stepper' => PlaygroundCategory::Controls,
        'track-slider' => PlaygroundCategory::Controls,
        'flex-slider' => PlaygroundCategory::Controls,
        'traffic-split' => PlaygroundCategory::Controls,
        'rating' => PlaygroundCategory::Controls,
        'nps-field' => PlaygroundCategory::Controls,
        'price-range' => PlaygroundCategory::Controls,
        'choice-cards' => PlaygroundCategory::Collections,
        'image-choice-cards' => PlaygroundCategory::Collections,
        'choice-checkbox-cards' => PlaygroundCategory::Collections,
        'flex-checklist' => PlaygroundCategory::Collections,
        'flex-radiolist' => PlaygroundCategory::Collections,
        'matrix-choice' => PlaygroundCategory::Collections,
        'flex-matrix-table' => PlaygroundCategory::Collections,
        'item-card-group' => PlaygroundCategory::Collections,
        'cover-card' => PlaygroundCategory::Collections,
        'flex-text-input' => PlaygroundCategory::TextInput,
        'flex-textarea' => PlaygroundCategory::TextInput,
        'flex-rich-editor' => PlaygroundCategory::TextInput,
        'slug-field' => PlaygroundCategory::TextInput,
        'translatable-fields' => PlaygroundCategory::TextInput,
        'verification-code' => PlaygroundCategory::TextInput,
        'calculator-field' => PlaygroundCategory::TextInput,
        'currency-field' => PlaygroundCategory::TextInput,
        'credit-card' => PlaygroundCategory::TextInput,
        'social-links-field' => PlaygroundCategory::TextInput,
        'user-column' => PlaygroundCategory::DataDisplay,
        'rating-column' => PlaygroundCategory::DataDisplay,
        'icon-column' => PlaygroundCategory::DataDisplay,
        'admin-columns' => PlaygroundCategory::DataDisplay,
        'progress-bar' => PlaygroundCategory::DataDisplay,
        'progress-circle' => PlaygroundCategory::DataDisplay,
        'file-upload' => PlaygroundCategory::Media,
        'video-field' => PlaygroundCategory::Media,
        'audio-field' => PlaygroundCategory::Media,
        'voice-note-recorder-field' => PlaygroundCategory::Media,
        'link-preview-field' => PlaygroundCategory::Media,
        'barcode-scanner-field' => PlaygroundCategory::Media,
        'signature-field' => PlaygroundCategory::Media,
        'map-picker' => PlaygroundCategory::Location,
    ];

    /**
     * Mintlify doc paths (`docs/{path}.md`) for playground parity checks.
     *
     * @var array<string, string>
     */
    private const DOCS_BY_SLUG = [
        'focus-outline' => 'focus-outline.md',
        'schema-conditions' => 'schema-conditions.md',
        'field-intelligence' => 'field-intelligence.md',
        'admin-columns' => 'admin-columns.md',
        'phone-field' => 'phonefield.md',
        'country-field' => 'countryfield.md',
        'timezone-field' => 'timezonefield.md',
        'currency-field' => 'currencyfield.md',
        'calculator-field' => 'calculator-field.md',
        'number-stepper' => 'numberstepper.md',
        'choice-cards' => 'choicecards.md',
        'image-choice-cards' => 'imagechoicecards.md',
        'choice-checkbox-cards' => 'choicecheckboxcards.md',
        'segment-control' => 'segmentcontrol.md',
        'nps-field' => 'nps-field.md',
        'track-slider' => 'trackslider.md',
        'flex-slider' => 'flexslider.md',
        'traffic-split' => 'trafficsplit.md',
        'switch' => 'switchfield.md',
        'select-field' => 'selectfield.md',
        'user-select' => 'userselect.md',
        'user-column' => 'usercolumn.md',
        'rating-column' => 'ratingcolumn.md',
        'icon-column' => 'iconcolumn.md',
        'hold-confirm' => 'hold-confirm-action.md',
        'rating' => 'ratingfield.md',
        'dual-listbox' => 'duallistboxfield.md',
        'bubble-choice' => 'bubblechoicefield.md',
        'todo-list-field' => 'todolistfield.md',
        'price-range' => 'pricerangefield.md',
        'flex-textarea' => 'flextextareafield.md',
        'flex-rich-editor' => 'flex-rich-editor.md',
        'flex-text-input' => 'flextextinput.md',
        'slug-field' => 'slugfield-and-titleslugfield.md',
        'translatable-fields' => 'translatablefields.md',
        'date-time-fields' => 'date-and-time-fields.md',
        'credit-card' => 'creditcardfield.md',
        'color-swatch' => 'colorswatchfield.md',
        'flex-color-picker' => 'flexcolorpickerfield.md',
        'icon-picker-field' => 'icon-picker-field.md',
        'file-upload' => 'flexfileupload-and-fleximageupload.md',
        'video-field' => 'videofield.md',
        'audio-field' => 'audiofield.md',
        'voice-note-recorder-field' => 'voicenoterecorderfield.md',
        'map-picker' => 'mappickerfield.md',
        'link-preview-field' => 'link-preview-field.md',
        'barcode-scanner-field' => 'barcode-scanner-field.md',
        'social-links-field' => 'social-links-field.md',
        'schedule-field' => 'schedule-field.md',
        'address-autocomplete' => 'addressautocompletefield.md',
        'signature-field' => 'signaturefield.md',
        'verification-code' => 'flexverificationcode.md',
        'flex-checklist' => 'flexchecklist.md',
        'flex-radiolist' => 'flexradiolist.md',
        'flex-matrix-table' => 'flex-matrix-table.md',
        'matrix-choice' => 'matrixchoicefield.md',
        'tags-field' => 'tags-field.md',
        'item-card-group' => 'itemcardgroup.md',
        'cover-card' => 'covercard.md',
        'progress-bar' => 'progressbar.md',
        'progress-circle' => 'progresscircle.md',
        'segment-tabs' => 'segmenttabs.md',
        'form-layouts' => 'form-layout-patterns.md',
    ];

    /**
     * Playground hubs that intentionally ship without a dedicated doc page.
     *
     * @var list<string>
     */
    private const DOCS_EXEMPT_SLUGS = [
        'composition-recipes',
    ];

    /**
     * @var array<string, array{label: string, playground: class-string, sort: int, icon: string, category: PlaygroundCategory, badge?: string, badgeColor?: string, docs_path?: string}>|null
     */
    private static ?array $definitionsCache = null;

    /**
     * @var array<string, array{label: string, playground: class-string, sort: int, icon: string, category: PlaygroundCategory, badge?: string, badgeColor?: string, docs_path?: string}>|null
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
                'schema-conditions' => ['label' => 'Schema conditions', 'playground' => SchemaConditionsPlayground::class, 'sort' => 12, 'icon' => GravityIcon::FileText],
                'field-intelligence' => ['label' => 'Calculated formulas', 'playground' => FieldIntelligencePlayground::class, 'sort' => 14, 'icon' => GravityIcon::Thunderbolt],
                'composition-recipes' => ['label' => 'Composition recipes', 'playground' => CompositionRecipesPlayground::class, 'sort' => 17, 'icon' => GravityIcon::LayoutCells],
                'phone-field' => ['label' => 'Phone field', 'playground' => PhoneFieldPlayground::class, 'sort' => 20, 'icon' => GravityIcon::Handset],
                'country-field' => ['label' => 'Country field', 'playground' => CountryFieldPlayground::class, 'sort' => 30, 'icon' => GravityIcon::Globe],
                'timezone-field' => ['label' => 'Timezone field', 'playground' => TimezoneFieldPlayground::class, 'sort' => 40, 'icon' => GravityIcon::Clock],
                'currency-field' => ['label' => 'Currency field', 'playground' => CurrencyFieldPlayground::class, 'sort' => 50, 'icon' => GravityIcon::CircleDollar],
                'calculator-field' => ['label' => 'Calculator field', 'playground' => CalculatorFieldPlayground::class, 'sort' => 55, 'icon' => GravityIcon::make('calculator')],
                'number-stepper' => ['label' => 'Number Stepper', 'playground' => NumberStepperPlayground::class, 'sort' => 60, 'icon' => GravityIcon::Plus],
                'choice-cards' => ['label' => 'Choice Cards', 'playground' => ChoiceCardsPlayground::class, 'sort' => 70, 'icon' => GravityIcon::LayoutCells],
                'image-choice-cards' => ['label' => 'Image Choice Cards', 'playground' => ImageChoiceCardsPlayground::class, 'sort' => 75, 'icon' => GravityIcon::Camera],
                'choice-checkbox-cards' => ['label' => 'Choice Checkbox Cards', 'playground' => ChoiceCheckboxCardsPlayground::class, 'sort' => 80, 'icon' => GravityIcon::Check],
                'segment-control' => ['label' => 'Segment Control', 'playground' => SegmentControlPlayground::class, 'sort' => 90, 'icon' => GravityIcon::ChartColumn],
                'nps-field' => ['label' => 'NPS Field', 'playground' => NpsFieldPlayground::class, 'sort' => 95, 'icon' => GravityIcon::ChartColumn],
                'segment-tabs' => ['label' => 'Segment Tabs', 'playground' => SegmentTabsPlayground::class, 'sort' => 100, 'icon' => GravityIcon::LayoutColumns],
                'form-layouts' => ['label' => 'Modern form layouts', 'playground' => FormLayoutPlayground::class, 'sort' => 110, 'icon' => GravityIcon::Display],
                'track-slider' => ['label' => 'Track Slider', 'playground' => TrackSliderPlayground::class, 'sort' => 120, 'icon' => GravityIcon::ChartBar],
                'flex-slider' => ['label' => 'Flex Slider', 'playground' => FlexSliderPlayground::class, 'sort' => 130, 'icon' => GravityIcon::SquareChartBar],
                'traffic-split' => ['label' => 'Traffic Split', 'playground' => TrafficSplitPlayground::class, 'sort' => 140, 'icon' => GravityIcon::ChartColumn],
                'switch' => ['label' => 'Switch', 'playground' => SwitchPlayground::class, 'sort' => 150, 'icon' => GravityIcon::Thunderbolt],
                'select-field' => ['label' => 'SelectField', 'playground' => SelectPlayground::class, 'sort' => 160, 'icon' => GravityIcon::CircleChevronDown],
                'user-select' => ['label' => 'UserSelect', 'playground' => UserSelectPlayground::class, 'sort' => 170, 'icon' => GravityIcon::Person],
                'user-column' => ['label' => 'UserColumn', 'playground' => UserColumnPlayground::class, 'sort' => 180, 'icon' => GravityIcon::Persons],
                'admin-columns' => ['label' => 'Admin columns', 'playground' => AdminColumnsPlayground::class, 'sort' => 182, 'icon' => GravityIcon::ChartColumn],
                'hold-confirm' => ['label' => 'Hold confirm', 'playground' => HoldConfirmPlayground::class, 'sort' => 185, 'icon' => GravityIcon::ShieldCheck],
                'rating' => ['label' => 'Rating', 'playground' => RatingPlayground::class, 'sort' => 190, 'icon' => GravityIcon::Star],
                'rating-column' => ['label' => 'RatingColumn', 'playground' => RatingColumnPlayground::class, 'sort' => 200, 'icon' => GravityIcon::Star],
                'dual-listbox' => ['label' => 'Dual Listbox', 'playground' => DualListboxPlayground::class, 'sort' => 210, 'icon' => GravityIcon::ArrowRightArrowLeft],
                'bubble-choice' => ['label' => 'Bubble Choice', 'playground' => BubbleChoicePlayground::class, 'sort' => 215, 'icon' => GravityIcon::Circles3Plus],
                'price-range' => ['label' => 'Price Range', 'playground' => PriceRangePlayground::class, 'sort' => 220, 'icon' => GravityIcon::CircleDollar],
                'flex-textarea' => ['label' => 'Flex Textarea', 'playground' => FlexTextareaPlayground::class, 'sort' => 230, 'icon' => GravityIcon::FileText],
                'flex-rich-editor' => ['label' => 'Flex Rich Editor', 'playground' => FlexRichEditorPlayground::class, 'sort' => 232, 'icon' => GravityIcon::FileText],
                'flex-text-input' => ['label' => 'Flex text input', 'playground' => FlexTextInputPlayground::class, 'sort' => 240, 'icon' => GravityIcon::PencilToSquare],
                'slug-field' => ['label' => 'Slug field', 'playground' => SlugFieldPlayground::class, 'sort' => 250, 'icon' => GravityIcon::Link],
                'translatable-fields' => ['label' => 'Translatable Fields', 'playground' => TranslatableFieldsPlayground::class, 'sort' => 260, 'icon' => GravityIcon::Globe],
                'date-time-fields' => ['label' => 'Date & time fields', 'playground' => DateTimeFieldPlayground::class, 'sort' => 270, 'icon' => GravityIcon::Calendar],
                'credit-card' => ['label' => 'Credit card', 'playground' => CreditCardPlayground::class, 'sort' => 280, 'icon' => GravityIcon::CreditCard],
                'color-swatch' => ['label' => 'Color swatch', 'playground' => ColorSwatchPlayground::class, 'sort' => 290, 'icon' => GravityIcon::Palette],
                'flex-color-picker' => ['label' => 'Flex color picker', 'playground' => FlexColorPickerPlayground::class, 'sort' => 300, 'icon' => GravityIcon::Palette],
                'icon-picker-field' => ['label' => 'Icon picker', 'playground' => IconPickerFieldPlayground::class, 'sort' => 305, 'icon' => GravityIcon::Star],
                'icon-column' => ['label' => 'IconColumn', 'playground' => IconColumnPlayground::class, 'sort' => 306, 'icon' => GravityIcon::Star],
                'file-upload' => ['label' => 'File upload', 'playground' => FlexFileUploadPlayground::class, 'sort' => 310, 'icon' => GravityIcon::CloudArrowUpIn],
                'video-field' => ['label' => 'Video field', 'playground' => VideoFieldPlayground::class, 'sort' => 320, 'icon' => GravityIcon::Video],
                'audio-field' => ['label' => 'Audio player', 'playground' => AudioFieldPlayground::class, 'sort' => 330, 'icon' => GravityIcon::VolumeFill],
                'voice-note-recorder-field' => ['label' => 'Voice note recorder', 'playground' => VoiceNoteRecorderFieldPlayground::class, 'sort' => 331, 'icon' => GravityIcon::Microphone],
                'map-picker' => ['label' => 'Map picker', 'playground' => MapPickerPlayground::class, 'sort' => 340, 'icon' => GravityIcon::MapPin],
                'link-preview-field' => ['label' => 'Link preview', 'playground' => LinkPreviewFieldPlayground::class, 'sort' => 345, 'icon' => GravityIcon::Link],
                'barcode-scanner-field' => ['label' => 'Barcode scanner', 'playground' => BarcodeScannerFieldPlayground::class, 'sort' => 346, 'icon' => GravityIcon::make('qr-code')],
                'social-links-field' => ['label' => 'Social links', 'playground' => SocialLinksFieldPlayground::class, 'sort' => 347, 'icon' => GravityIcon::Persons],
                'schedule-field' => ['label' => 'Schedule field', 'playground' => ScheduleFieldPlayground::class, 'sort' => 348, 'icon' => GravityIcon::Calendar],
                'address-autocomplete' => ['label' => 'Address autocomplete', 'playground' => AddressAutocompletePlayground::class, 'sort' => 350, 'icon' => GravityIcon::MapPin],
                'signature-field' => ['label' => 'Signature', 'playground' => SignatureFieldPlayground::class, 'sort' => 360, 'icon' => GravityIcon::Pencil],
                'verification-code' => ['label' => 'Verification Code', 'playground' => FlexVerificationCodePlayground::class, 'sort' => 370, 'icon' => GravityIcon::ShieldCheck],
                'flex-checklist' => ['label' => 'Flex Checklist', 'playground' => FlexChecklistPlayground::class, 'sort' => 380, 'icon' => GravityIcon::Check],
                'todo-list-field' => ['label' => 'Todo list', 'playground' => TodoListFieldPlayground::class, 'sort' => 382, 'icon' => GravityIcon::Check],
                'flex-radiolist' => ['label' => 'Flex Radiolist', 'playground' => FlexRadiolistPlayground::class, 'sort' => 390, 'icon' => GravityIcon::Circles3Plus],
                'flex-matrix-table' => ['label' => 'Flex Matrix Table', 'playground' => FlexMatrixTablePlayground::class, 'sort' => 393, 'icon' => GravityIcon::LayoutCells],
                'matrix-choice' => ['label' => 'Matrix Choice', 'playground' => MatrixChoiceFieldPlayground::class, 'sort' => 395, 'icon' => GravityIcon::LayoutCells],
                'tags-field' => ['label' => 'Tags field', 'playground' => TagsFieldPlayground::class, 'sort' => 398, 'icon' => GravityIcon::make('tag')],
                'item-card-group' => ['label' => 'ItemCardGroup', 'playground' => ItemCardGroupPlayground::class, 'sort' => 400, 'icon' => GravityIcon::LayoutCells],
                'cover-card' => ['label' => 'Cover card', 'playground' => CoverCardPlayground::class, 'sort' => 410, 'icon' => GravityIcon::CopyPicture],
                'progress-bar' => ['label' => 'Progress bar', 'playground' => ProgressBarPlayground::class, 'sort' => 420, 'icon' => GravityIcon::ChartBar],
                'progress-circle' => ['label' => 'Progress circle', 'playground' => ProgressCirclePlayground::class, 'sort' => 430, 'icon' => GravityIcon::ChartColumn],
            ];

            foreach (self::$definitionsCache as $slug => $definition) {
                self::$definitionsCache[$slug] = self::enrichDefinition($slug, $definition);
            }
        }

        return self::$definitionsCache;
    }

    /**
     * @return array<string, array{label: string, playground: class-string, sort: int, icon: string, category: PlaygroundCategory, badge?: string, badgeColor?: string, docs_path?: string}>
     */
    public static function ordered(): array
    {
        if (self::$orderedCache === null) {
            $definitions = static::definitions();

            uasort(
                $definitions,
                function (array $left, array $right): int {
                    $categorySort = $left['category']->sort() <=> $right['category']->sort();

                    if ($categorySort !== 0) {
                        return $categorySort;
                    }

                    return $left['sort'] <=> $right['sort'];
                },
            );

            self::$orderedCache = $definitions;
        }

        return self::$orderedCache;
    }

    /**
     * @return array<string, array<string, array{label: string, playground: class-string, sort: int, icon: string, category: PlaygroundCategory, badge?: string, badgeColor?: string, docs_path?: string}>>
     */
    public static function groupedByCategory(): array
    {
        $grouped = [];

        foreach (PlaygroundCategory::cases() as $category) {
            $grouped[$category->value] = [];
        }

        foreach (static::ordered() as $slug => $definition) {
            $grouped[$definition['category']->value][$slug] = $definition;
        }

        return $grouped;
    }

    public static function categoryForSlug(string $slug): PlaygroundCategory
    {
        return self::CATEGORIES_BY_SLUG[$slug] ?? PlaygroundCategory::Pickers;
    }

    public static function docsPathFor(string $slug): ?string
    {
        return self::DOCS_BY_SLUG[$slug] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function docsExemptSlugs(): array
    {
        return self::DOCS_EXEMPT_SLUGS;
    }

    /**
     * @return array{label: string, playground: class-string, sort: int, icon: string, category: PlaygroundCategory, badge?: string, badgeColor?: string, docs_path?: string}|null
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

    /**
     * @param  array{label: string, playground: class-string, sort: int, icon: string, badge?: string, badgeColor?: string}  $definition
     * @return array{label: string, playground: class-string, sort: int, icon: string, category: PlaygroundCategory, badge?: string, badgeColor?: string, docs_path?: string}
     */
    private static function enrichDefinition(string $slug, array $definition): array
    {
        $definition['category'] = self::categoryForSlug($slug);

        if ($docsPath = self::docsPathFor($slug)) {
            $definition['docs_path'] = $docsPath;
        }

        return $definition;
    }
}
