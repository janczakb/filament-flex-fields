---
title: Filament Flex Fields
description: Filament v5 form fields plugin for Laravel — 79 components, virtualized Select with async search, Spatie Media Library & disk/S3 uploads, JSON custom fields without EAV, surveys, maps, signatures, lazy assets.
icon: rocket
---

**Filament Flex Fields** is the all-in-one **Filament form fields / form components plugin** for [Filament v5](https://filamentphp.com) Laravel admin panels: **79 custom components**, a unified `--fff-*` design system, first-class **Spatie Media Library** and disk/S3 uploads (**Media Ingress**), and an optional **JSON custom-field layer** without EAV tables.

Use any field as a standalone drop-in, or wire dynamic schemas through `HasFlexFields` and `FlexFieldFormBuilder`. Pre-built CSS and JavaScript ship in the package — **no Node.js in production**. Built so you do not install a separate plugin for each field type.

**Standouts:** [`SelectField`](/docs/selectfield) — **virtualized lists**, **async Livewire search**, rich option rows, mobile bottom sheets · [`FlexSpatieMediaLibraryFileUpload`](/docs/flexfileupload-and-fleximageupload) / disk uploads · phone, currency, maps, signatures, rich editor, NPS/matrix surveys — one coherent kit.

> **Premium companion — [Filament Flex Forms](https://github.com/janczakb/filament-flex-forms)**  
> Drag-and-drop Studio, public fill & embed, submissions, Insights, and integrations — a **commercial** Filament plugin built on Flex Fields.  
> [**Buy**](https://shop.bjanczak.com/checkout/buy/3d3a0d72-c9d5-4cfd-90c9-26869c444bb0) · [Docs](https://flexforms.bjanczak.com) · [GitHub](https://github.com/janczakb/filament-flex-forms)

---

## Quick start

```bash
composer require janczakb/filament-flex-fields
php artisan filament:assets
```

Register the plugin on your panel:

```php
use Bjanczak\FilamentFlexFields\FilamentFlexFieldsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(FilamentFlexFieldsPlugin::make());
}
```

Drop a component into any form schema:

```php
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\MatrixChoiceField;

PhoneField::make('phone')->defaultCountry('PL')->required();

MatrixChoiceField::make('priorities')
    ->rows(['speed' => 'Speed', 'cost' => 'Cost'])
    ->columns(['low' => 'Low', 'high' => 'High']);
```

**Upgrading?** Run `composer update janczakb/filament-flex-fields` and `php artisan filament:assets`. Automate the second step with `filament:assets` in `post-autoload-dump` for a hands-off workflow.

---

## Two ways to use Flex Fields

| Approach | Best for | What you need |
| --- | --- | --- |
| **Standalone components** | Fixed forms — profiles, checkout, CMS pages | Import the field class and chain Filament's fluent API |
| **JSON flex fields** | CRM custom attributes, tenant settings, variable page types | `HasFlexFields` trait + schemas in config or `FlexFieldSchemaRegistry` |

Both approaches share the same component library, design tokens, and lazy asset loading. Start with [Shared concepts](/docs/shared-concepts) for sizing, assets, and conventions.

---

## Requirements

| Dependency | Minimum |
| --- | --- |
| PHP | 8.3+ |
| Laravel | 11+ |
| Filament | 5.x (`^5.0`) |

Optional Spatie packages (sluggable, translatable, media library, tags) integrate where documented — none are required for core usage.

---

## What's in the box

| Category | Count | Highlights |
| --- | ---: | --- |
| Form fields | 64 | `SelectField` (virtualized + async), `PhoneField`, `CurrencyField`, `MatrixChoiceField`, Media Ingress uploads, `MapPickerField`, `IconPickerField` |
| Layout & schema | 8 | `ItemCardGroup`, `SegmentTabs`, `CoverCard`, `TranslatableFields` |
| Table columns | 7 | `UserColumn`, `RatingColumn`, `IconColumn`, `ProgressColumn`, `StatusChipColumn`, `MapPinColumn`, `SignaturePreviewColumn` |
| **Total** | **79** | |

Every component loads **only its own CSS and JS** when rendered — including inside Filament modals and slide-overs. `HoldConfirmAction` is documented under Actions and is not counted in the 79.

---

## Explore the docs

### Getting started

| Page | What you'll learn |
| --- | --- |
| [Shared concepts](/docs/shared-concepts) | Design system, `sm` / `md` / `lg` sizing, lazy assets, playground |
| [Flex Field Groups](/docs/flex-field-groups) | Optional migrations (`flex_field_groups`, `flex_field_schema_versions`), DB admin CRUD, `SchemaRegistry` versioning — opt-in M8 |
| [Form layout patterns](/docs/form-layout-patterns) | Combine layout primitives into rich admin UIs |
| [Layout components — quick comparison](/docs/layout-components-quick-comparison) | Pick the right card or tab layout |
| [Deprecated class aliases](/docs/deprecated-class-aliases) | Legacy class names and migration paths |
| [Media Ingress](/docs/media-capture-os) | Disk vs Spatie, S3, conversions, AV/quarantine, FormBuilder, voice/signature/rich editor |
| [Media Ingress migration](/docs/media-ingress-migration) | Upgrade checklist from MediaCaptureOs / old Spatie adapter paths |

### Text & input

[FlexTextInput](/docs/flextextinput) · [FlexTextareaField](/docs/flextextareafield) · [FlexRichEditor](/docs/flex-rich-editor) · [PhoneField](/docs/phonefield) · [CountryField](/docs/countryfield) · [TimezoneField](/docs/timezonefield) · [SlugField & TitleSlugField](/docs/slugfield-and-titleslugfield) · [AddressAutocompleteField](/docs/addressautocompletefield) · [FlexVerificationCode](/docs/flexverificationcode) · [TagsField](/docs/tags-field) · [LinkPreviewField](/docs/link-preview-field) · [SocialLinksField](/docs/social-links-field)

### Number & range

[NumberStepper](/docs/numberstepper) · [CalculatorField](/docs/calculator-field) · [CurrencyField](/docs/currencyfield) · [FlexSlider](/docs/flexslider) · [TrackSlider](/docs/trackslider) · [PriceRangeField](/docs/pricerangefield) · [TrafficSplit](/docs/trafficsplit)

### Choice & selection

[SelectField](/docs/selectfield) *(virtualized · async search · mobile sheet)* · [UserSelect](/docs/userselect) · [TagsField](/docs/tags-field) · [IconPickerField](/docs/icon-picker-field) · [DualListboxField](/docs/duallistboxfield) · [SwitchField](/docs/switchfield) · [SegmentControl](/docs/segmentcontrol) · [ChoiceCards](/docs/choicecards) · [ChoiceCheckboxCards](/docs/choicecheckboxcards) · [ImageChoiceCards](/docs/imagechoicecards) · [FlexChecklist](/docs/flexchecklist) · [TodoListField](/docs/todolistfield) · [BubbleChoiceField](/docs/bubblechoicefield) · [FlexRadiolist](/docs/flexradiolist) · [MatrixChoiceField](/docs/matrixchoicefield) · [FlexMatrixTable](/docs/flex-matrix-table)

### Date & time

[Date & time fields](/docs/date-and-time-fields) · [ScheduleField](/docs/schedule-field)

### Media, color & location

[Media Ingress](/docs/media-capture-os) · [FlexFileUpload & FlexImageUpload](/docs/flexfileupload-and-fleximageupload) *(disk or Spatie)* · [ColorSwatchField](/docs/colorswatchfield) · [FlexColorPickerField](/docs/flexcolorpickerfield) · [VideoField](/docs/videofield) *(YouTube · Vimeo · HTML5)* · [AudioField](/docs/audiofield) · [VoiceNoteRecorderField](/docs/voicenoterecorderfield) · [MapPickerField](/docs/mappickerfield) · [SignatureField](/docs/signaturefield) · [CreditCardField](/docs/creditcardfield) · [BarcodeScannerField](/docs/barcode-scanner-field)

### Rating & tables

[RatingField](/docs/ratingfield) · [NpsField](/docs/nps-field) · [UserColumn](/docs/usercolumn) · [RatingColumn](/docs/ratingcolumn) · [IconColumn](/docs/iconcolumn) · [Admin columns](/docs/admin-columns) *(Progress · StatusChip · MapPin · SignaturePreview)*

### Layout & display

[ItemCard](/docs/itemcard) · [ItemCardGroup](/docs/itemcardgroup) · [ItemCardStack](/docs/itemcardstack) · [CoverCard](/docs/covercard) · [ProgressBar](/docs/progressbar) · [ProgressCircle](/docs/progresscircle) · [SegmentTabs](/docs/segmenttabs) · [TranslatableFields](/docs/translatablefields)

### Actions

[Hold confirm action](/docs/hold-confirm-action) — press-and-hold Filament actions for destructive or irreversible operations

---

## Playground

Enable the playground in your Filament panel to preview components interactively:

```dotenv
FLEX_FIELDS_PLAYGROUND=true
```

Open **Settings & Tools → Flex Fields Playground** in the admin panel. Use it to compare variants and test form behaviour before shipping to production.

---

## Links

- [GitHub repository](https://github.com/janczakb/filament-flex-fields)
- [Packagist](https://packagist.org/packages/janczakb/filament-flex-fields)
- [License](https://github.com/janczakb/filament-flex-fields/blob/main/LICENSE) · [Commercial plans](https://github.com/janczakb/filament-flex-fields/blob/main/COMMERCIAL.md)
- [Report an issue](https://github.com/janczakb/filament-flex-fields/issues)
