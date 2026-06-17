<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Data\FlexFieldDefinition;
use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\AddressAutocompleteField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CountryField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\CreditCardField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextareaField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexTextInput;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\FlexVerificationCode;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\IconPickerField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\PhoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TimezoneField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TitleSlugField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\AddressAutocompleteFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\CountryFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\CreditCardFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexTextareaFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexTextInputFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\FlexVerificationCodeFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\IconPickerFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\PhoneFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\SlugFieldConfigurator;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\TimezoneFieldConfigurator;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

final class TextFieldTypeHandler extends AbstractFieldTypeHandler
{
    public function __construct(
        private readonly FlexTextareaFieldConfigurator $flexTextarea = new FlexTextareaFieldConfigurator,
        private readonly FlexTextInputFieldConfigurator $flexTextInput = new FlexTextInputFieldConfigurator,
        private readonly CreditCardFieldConfigurator $creditCard = new CreditCardFieldConfigurator,
        private readonly PhoneFieldConfigurator $phone = new PhoneFieldConfigurator,
        private readonly CountryFieldConfigurator $country = new CountryFieldConfigurator,
        private readonly TimezoneFieldConfigurator $timezone = new TimezoneFieldConfigurator,
        private readonly SlugFieldConfigurator $slug = new SlugFieldConfigurator,
        private readonly AddressAutocompleteFieldConfigurator $addressAutocomplete = new AddressAutocompleteFieldConfigurator,
        private readonly FlexVerificationCodeFieldConfigurator $verificationCode = new FlexVerificationCodeFieldConfigurator,
        private readonly IconPickerFieldConfigurator $iconPicker = new IconPickerFieldConfigurator,
    ) {}

    protected function supportedTypesList(): array
    {
        return [
            FieldType::SingleLineText,
            FieldType::MultiLineText,
            FieldType::FlexTextarea,
            FieldType::FlexTextInput,
            FieldType::CreditCard,
            FieldType::RichText,
            FieldType::Markdown,
            FieldType::Email,
            FieldType::Url,
            FieldType::Phone,
            FieldType::Country,
            FieldType::Timezone,
            FieldType::Password,
            FieldType::Slug,
            FieldType::Search,
            FieldType::AddressAutocomplete,
            FieldType::VerificationCode,
            FieldType::IconPicker,
        ];
    }

    public function make(FlexFieldDefinition $definition, string $statePath): Component
    {
        $config = $definition->config;

        return match ($definition->type) {
            FieldType::SingleLineText => TextInput::make($statePath),
            FieldType::MultiLineText => Textarea::make($statePath),
            FieldType::FlexTextarea => $this->flexTextarea->configure(FlexTextareaField::make($statePath), $config),
            FieldType::FlexTextInput => $this->flexTextInput->configure(FlexTextInput::make($statePath), $config),
            FieldType::CreditCard => $this->creditCard->configure(CreditCardField::make($statePath), $config),
            FieldType::RichText => RichEditor::make($statePath),
            FieldType::Markdown => MarkdownEditor::make($statePath),
            FieldType::Email => TextInput::make($statePath)->email(),
            FieldType::Url => TextInput::make($statePath)->url(),
            FieldType::Phone => $this->phone->configure(PhoneField::make($statePath), $config),
            FieldType::Country => $this->country->configure(CountryField::make($statePath), $config),
            FieldType::Timezone => $this->timezone->configure(TimezoneField::make($statePath), $config),
            FieldType::Password => TextInput::make($statePath)->password()->revealable(),
            FieldType::Slug => filled($config['title_slug'] ?? null)
                ? TitleSlugField::make(
                    fieldTitle: $config['title_field'] ?? null,
                    fieldSlug: $statePath,
                    spatieModel: $config['spatie_model'] ?? null,
                    translatableLocales: $config['translatable_locales'] ?? null,
                    slugSourceLocale: $config['slug_source_locale'] ?? null,
                    spatieTranslatable: (bool) ($config['spatie_translatable'] ?? false),
                    requiredTitleLocales: $config['required_title_locales'] ?? null,
                )
                : $this->slug->configure(SlugField::make($statePath), $config),
            FieldType::Search => TextInput::make($statePath)->prefixIcon('heroicon-o-magnifying-glass'),
            FieldType::AddressAutocomplete => $this->addressAutocomplete->configure(AddressAutocompleteField::make($statePath), $config),
            FieldType::VerificationCode => $this->verificationCode->configure(FlexVerificationCode::make($statePath), $config),
            FieldType::IconPicker => $this->iconPicker->configure(IconPickerField::make($statePath), $config),
            default => throw new \InvalidArgumentException("Unsupported field type [{$definition->type->value}] for text handler."),
        };
    }
}
