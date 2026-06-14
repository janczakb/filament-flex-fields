<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields;
use Bjanczak\FilamentFlexFields\Filament\Schemas\Components\TranslatableFields\TranslatableTab;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableFieldFactory;
use Bjanczak\FilamentFlexFields\Support\Translatable\TranslatableTitle;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

final class TitleSlugField
{
    public const AUTO_UPDATE_DISABLED_SUFFIX = '_auto_update_disabled';

    public const INLINE_EDIT_PENDING_SUFFIX = '_inline_edit_pending';

    /**
     * @param  array<int, string|Closure>|Closure  $titleRules
     * @param  array<int, string|Closure>|Closure  $slugRules
     * @param  array<string, mixed>  $slugUniqueParameters
     * @param  array<string, mixed>  $titleUniqueParameters
     * @param  array<string, mixed>  $titleExtraInputAttributes
     * @param  array<string, string>|list<string>|Closure|null  $translatableLocales
     * @param  'all'|list<string>|Closure|null  $requiredTitleLocales
     * @param  Closure(TranslatableFields): TranslatableFields|null  $translatableFieldsConfigurator
     */
    public static function make(
        ?string $fieldTitle = null,
        ?string $fieldSlug = null,
        ?Field $titleField = null,
        ?Closure $titleFieldWrapper = null,
        ?Closure $titleAfterStateUpdated = null,
        ?Closure $slugAfterStateUpdated = null,
        array|Closure $titleRules = ['required', 'string'],
        array|Closure $slugRules = ['required', 'string'],
        bool $titleAutofocus = false,
        bool|Closure $titleReadOnly = false,
        bool|Closure $slugReadOnly = false,
        ?string $titleLabel = null,
        ?string $titlePlaceholder = null,
        ?string $slugLabel = null,
        array $titleExtraInputAttributes = [],
        ?array $slugUniqueParameters = null,
        ?array $titleUniqueParameters = null,
        ?string $urlHost = null,
        ?string $urlPath = null,
        bool $urlHostVisible = true,
        ?string $visitLinkLabel = null,
        string|Closure|null $visitUrl = null,
        bool $showVisitLink = true,
        ?string $slugLabelPostfix = null,
        bool|Closure $preserveSlugOnEdit = true,
        string|Closure|null $spatieModel = null,
        array|Closure|null $translatableLocales = null,
        string|Closure|null $slugSourceLocale = null,
        bool|Closure $spatieTranslatable = false,
        array|string|Closure|null $requiredTitleLocales = null,
        ?Closure $titleLocaleConfigurator = null,
        ?Closure $slugConfigurator = null,
        ?Closure $translatableFieldsConfigurator = null,
    ): FusedGroup {
        $fieldTitle = $fieldTitle
            ?? $titleField?->getName()
            ?? (string) config('filament-flex-fields.slug.field_title', 'title');

        $fieldSlug = $fieldSlug
            ?? (string) config('filament-flex-fields.slug.field_slug', 'slug');

        $locales = TranslatableTitle::resolveLocales($translatableLocales);
        $isTranslatable = $locales !== [];
        $resolvedSlugSourceLocale = $isTranslatable
            ? TranslatableTitle::resolveSlugSourceLocale($slugSourceLocale, $locales)
            : null;

        $autoUpdateDisabledField = self::autoUpdateDisabledFieldName($fieldSlug);
        $inlineEditPendingField = self::inlineEditPendingFieldName($fieldSlug);

        $urlHost ??= config('filament-flex-fields.slug.url_host');

        $slugField = self::makeSlugField(
            fieldSlug: $fieldSlug,
            fieldTitle: $fieldTitle,
            slugLabel: $slugLabel,
            slugRules: $slugRules,
            slugUniqueParameters: $slugUniqueParameters,
            slugReadOnly: $slugReadOnly,
            urlHost: $urlHost,
            urlPath: $urlPath,
            urlHostVisible: $urlHostVisible,
            visitLinkLabel: $visitLinkLabel,
            visitUrl: $visitUrl,
            showVisitLink: $showVisitLink,
            slugLabelPostfix: $slugLabelPostfix,
            preserveSlugOnEdit: $preserveSlugOnEdit,
            spatieModel: $spatieModel,
            autoUpdateDisabledField: $autoUpdateDisabledField,
            inlineEditPendingField: $inlineEditPendingField,
            slugAfterStateUpdated: $slugAfterStateUpdated,
            slugConfigurator: $slugConfigurator,
            isTranslatable: $isTranslatable,
            locales: $locales,
            slugSourceLocale: $resolvedSlugSourceLocale,
            spatieTranslatable: $spatieTranslatable,
        );

        $titleInput = $isTranslatable
            ? self::makeTranslatableTitleField(
                fieldTitle: $fieldTitle,
                fieldSlug: $fieldSlug,
                locales: $locales,
                slugSourceLocale: $resolvedSlugSourceLocale,
                titleRules: $titleRules,
                titleAutofocus: $titleAutofocus,
                titleReadOnly: $titleReadOnly,
                titleLabel: $titleLabel,
                titlePlaceholder: $titlePlaceholder,
                titleExtraInputAttributes: $titleExtraInputAttributes,
                autoUpdateDisabledField: $autoUpdateDisabledField,
                preserveSlugOnEdit: $preserveSlugOnEdit,
                slugField: $slugField,
                titleAfterStateUpdated: $titleAfterStateUpdated,
                titleLocaleConfigurator: $titleLocaleConfigurator,
                requiredTitleLocales: $requiredTitleLocales,
                spatieTranslatable: $spatieTranslatable,
                translatableFieldsConfigurator: $translatableFieldsConfigurator,
            )
            : self::makeTitleField(
                fieldTitle: $fieldTitle,
                fieldSlug: $fieldSlug,
                titleField: $titleField,
                titleRules: $titleRules,
                titleUniqueParameters: $titleUniqueParameters,
                titleAutofocus: $titleAutofocus,
                titleReadOnly: $titleReadOnly,
                titleLabel: $titleLabel,
                titlePlaceholder: $titlePlaceholder,
                titleExtraInputAttributes: $titleExtraInputAttributes,
                autoUpdateDisabledField: $autoUpdateDisabledField,
                preserveSlugOnEdit: $preserveSlugOnEdit,
                slugField: $slugField,
                titleAfterStateUpdated: $titleAfterStateUpdated,
            );

        if ($titleFieldWrapper instanceof Closure) {
            $titleInput = $titleFieldWrapper($titleInput) ?? $titleInput;
        }

        $hiddenAutoUpdateDisabled = Hidden::make($autoUpdateDisabledField)
            ->dehydrated(false);

        $hiddenInlineEditPending = Hidden::make($inlineEditPendingField)
            ->default(false)
            ->dehydrated(false);

        return FusedGroup::make([
            $titleInput,
            $hiddenAutoUpdateDisabled,
            $hiddenInlineEditPending,
            $slugField,
        ])->extraAttributes([
            'class' => 'fff-title-slug-fused-group',
        ]);
    }

    public static function autoUpdateDisabledFieldName(string $fieldSlug): string
    {
        return $fieldSlug.self::AUTO_UPDATE_DISABLED_SUFFIX;
    }

    public static function inlineEditPendingFieldName(string $fieldSlug): string
    {
        return $fieldSlug.self::INLINE_EDIT_PENDING_SUFFIX;
    }

    /**
     * @param  array<int, string|Closure>|Closure  $slugRules
     * @param  array<string, mixed>|null  $slugUniqueParameters
     * @param  array<string, string>  $locales
     */
    protected static function makeSlugField(
        string $fieldSlug,
        string $fieldTitle,
        ?string $slugLabel,
        array|Closure $slugRules,
        ?array $slugUniqueParameters,
        bool|Closure $slugReadOnly,
        ?string $urlHost,
        ?string $urlPath,
        bool $urlHostVisible,
        ?string $visitLinkLabel,
        string|Closure|null $visitUrl,
        bool $showVisitLink,
        ?string $slugLabelPostfix,
        bool|Closure $preserveSlugOnEdit,
        string|Closure|null $spatieModel,
        string $autoUpdateDisabledField,
        string $inlineEditPendingField,
        ?Closure $slugAfterStateUpdated,
        ?Closure $slugConfigurator,
        bool $isTranslatable,
        array $locales,
        ?string $slugSourceLocale,
        bool|Closure $spatieTranslatable,
    ): SlugField {
        $slugField = SlugField::make($fieldSlug)
            ->label($slugLabel)
            ->autoGenerate($isTranslatable)
            ->hiddenLabel($slugLabel === null)
            ->slugRules($slugRules)
            ->slugReadOnly($slugReadOnly)
            ->preserveSlugOnEdit($preserveSlugOnEdit)
            ->size((string) config('filament-flex-fields.ui.slug_size', 'md'))
            ->variant((string) config('filament-flex-fields.ui.slug_variant', 'primary'));

        if ($isTranslatable && $slugSourceLocale !== null) {
            $slugField
                ->translatableTitle(true)
                ->titleLocales($locales)
                ->slugSourceLocale($slugSourceLocale)
                ->translatableTitleField($fieldTitle)
                ->spatieTranslatable($spatieTranslatable)
                ->source(TranslatableTitle::sourcePath($fieldTitle, $slugSourceLocale));
        } else {
            $slugField->source($fieldTitle);
        }

        if ($slugLabel !== null) {
            $slugField->label($slugLabel);
        }

        if ($urlHost !== null) {
            $slugField->urlHost($urlHost);
        }

        if ($urlPath !== null) {
            $slugField->urlPath($urlPath);
        }

        $slugField->urlHostVisible($urlHostVisible);

        if ($visitLinkLabel !== null) {
            $slugField->visitLinkLabel($visitLinkLabel);
        }

        if ($visitUrl !== null) {
            $slugField->visitUrl($visitUrl);
        }

        $slugField->showVisitLink($showVisitLink);

        if ($slugLabelPostfix !== null) {
            $slugField->slugLabelPostfix($slugLabelPostfix);
        }

        if ($spatieModel !== null) {
            $slugField->spatieModel($spatieModel);
        }

        if (is_array($slugUniqueParameters)) {
            $slugField->slugUniqueParameters($slugUniqueParameters);
        }

        if ($slugConfigurator instanceof Closure) {
            $slugField = $slugConfigurator($slugField) ?? $slugField;
        }

        if ($slugAfterStateUpdated instanceof Closure) {
            $slugField->slugAfterStateUpdated($slugAfterStateUpdated);
        }

        $slugField->autoUpdateDisabledField($autoUpdateDisabledField);
        $slugField->inlineEditPendingField($inlineEditPendingField);

        return $slugField;
    }

    /**
     * @param  array<int, string|Closure>|Closure  $titleRules
     * @param  array<string, mixed>|null  $titleUniqueParameters
     * @param  array<string, mixed>  $titleExtraInputAttributes
     */
    protected static function makeTitleField(
        string $fieldTitle,
        string $fieldSlug,
        ?Field $titleField,
        array|Closure $titleRules,
        ?array $titleUniqueParameters,
        bool $titleAutofocus,
        bool|Closure $titleReadOnly,
        ?string $titleLabel,
        ?string $titlePlaceholder,
        array $titleExtraInputAttributes,
        string $autoUpdateDisabledField,
        bool|Closure $preserveSlugOnEdit,
        SlugField $slugField,
        ?Closure $titleAfterStateUpdated,
    ): Field {
        if ($titleField instanceof Field) {
            $titleInput = $titleField->live();
        } else {
            $titleInput = FlexTextInput::make($fieldTitle)
                ->autofocus($titleAutofocus)
                ->live()
                ->autocomplete(false)
                ->extraInputAttributes(array_merge(
                    ['class' => 'fff-title-slug-field__title-input'],
                    $titleExtraInputAttributes,
                ))
                ->beforeStateDehydrated(fn (FlexTextInput $component, mixed $state): mixed => is_string($state) ? trim($state) : $state);

            $rules = is_array($titleRules) ? $titleRules : ['required', 'string'];
            $titleInput->rules($rules);

            if (in_array('required', $rules, true)) {
                $titleInput->required();
            }

            if ($titleLabel !== null) {
                $titleInput->label($titleLabel);
            } elseif ($titleLabel === null && $titleField === null) {
                $titleInput->label(Str::headline($fieldTitle));
            }

            if ($titlePlaceholder !== null) {
                $titleInput->placeholder($titlePlaceholder);
            } elseif ($titlePlaceholder === null && $titleField === null) {
                $titleInput->placeholder(Str::headline($fieldTitle));
            }

            if (is_array($titleUniqueParameters)) {
                $titleInput->unique(...$titleUniqueParameters);
            }
        }

        $titleInput
            ->readOnly($titleReadOnly)
            ->afterStateUpdated(self::titleSlugSyncCallback(
                fieldSlug: $fieldSlug,
                autoUpdateDisabledField: $autoUpdateDisabledField,
                preserveSlugOnEdit: $preserveSlugOnEdit,
                slugField: $slugField,
                titleAfterStateUpdated: $titleAfterStateUpdated,
            ));

        return $titleInput;
    }

    /**
     * @param  array<string, string>  $locales
     * @param  array<int, string|Closure>|Closure  $titleRules
     * @param  array<string, mixed>  $titleExtraInputAttributes
     * @param  'all'|list<string>|Closure|null  $requiredTitleLocales
     */
    protected static function makeTranslatableTitleField(
        string $fieldTitle,
        string $fieldSlug,
        array $locales,
        string $slugSourceLocale,
        array|Closure $titleRules,
        bool $titleAutofocus,
        bool|Closure $titleReadOnly,
        ?string $titleLabel,
        ?string $titlePlaceholder,
        array $titleExtraInputAttributes,
        string $autoUpdateDisabledField,
        bool|Closure $preserveSlugOnEdit,
        SlugField $slugField,
        ?Closure $titleAfterStateUpdated,
        ?Closure $titleLocaleConfigurator,
        array|string|Closure|null $requiredTitleLocales = null,
        bool|Closure $spatieTranslatable = false,
        ?Closure $translatableFieldsConfigurator = null,
    ): TranslatableFields {
        $requiredLocales = TranslatableTitle::resolveRequiredLocales(
            $requiredTitleLocales,
            $locales,
            $slugSourceLocale,
        );

        $titleTemplate = FlexTextInput::make($fieldTitle)
            ->hiddenLabel()
            ->live()
            ->autocomplete(false)
            ->readOnly($titleReadOnly)
            ->extraInputAttributes(array_merge(
                ['class' => 'fff-title-slug-field__title-input'],
                $titleExtraInputAttributes,
            ))
            ->beforeStateDehydrated(fn (FlexTextInput $component, mixed $state): mixed => is_string($state) ? trim($state) : $state);

        $autofocusAssigned = false;

        $translatableFields = TranslatableFields::make($titleLabel ?? Str::headline($fieldTitle))
            ->locales($locales)
            ->spatieTranslatable($spatieTranslatable)
            ->schema([$titleTemplate])
            ->size((string) config('filament-flex-fields.ui.segment_size', 'md'))
            ->variant((string) config('filament-flex-fields.ui.segment_variant', 'default'))
            ->activeTab(TranslatableTitle::activeTabIndex($locales, $slugSourceLocale))
            ->directionByLocale()
            ->emptyBadgeWhenAllFieldsAreEmpty()
            ->extraAttributes(['class' => 'fff-title-slug-field__translatable-tabs'], merge: true);

        if ($titleLocaleConfigurator instanceof Closure) {
            $translatableFields->localeFieldUsing(function (
                Field $template,
                string $locale,
                TranslatableTab $tab,
            ) use ($titleLocaleConfigurator, $translatableFields): Field {
                $field = TranslatableFieldFactory::make(
                    template: $template,
                    locale: $locale,
                    tab: $tab,
                    spatieTranslatable: $translatableFields->shouldUseSpatieTranslatable(),
                );

                return $titleLocaleConfigurator($field, $locale) ?? $field;
            });
        }

        $translatableFields->modifyFieldsUsing(function (Field $field, string $locale) use (
            $fieldTitle,
            $fieldSlug,
            $titleRules,
            $titleAutofocus,
            $titlePlaceholder,
            $autoUpdateDisabledField,
            $preserveSlugOnEdit,
            $slugField,
            $titleAfterStateUpdated,
            $requiredLocales,
            $slugSourceLocale,
            &$autofocusAssigned,
        ): void {
            $localeRules = TranslatableTitle::rulesForLocale($titleRules, $locale, $requiredLocales);

            $field
                ->placeholder($titlePlaceholder ?? TranslatableTitle::defaultPlaceholder($fieldTitle, $locale))
                ->rules($localeRules);

            if (TranslatableTitle::isLocaleRequired($locale, $requiredLocales)) {
                $field->required();
            }

            if ($titleAutofocus && ! $autofocusAssigned) {
                $field->autofocus($locale === $slugSourceLocale);
                $autofocusAssigned = true;
            }

            if ($locale === $slugSourceLocale) {
                $field->afterStateUpdated(self::titleSlugSyncCallback(
                    fieldSlug: $fieldSlug,
                    autoUpdateDisabledField: $autoUpdateDisabledField,
                    preserveSlugOnEdit: $preserveSlugOnEdit,
                    slugField: $slugField,
                    titleAfterStateUpdated: $titleAfterStateUpdated,
                ));
            } elseif ($titleAfterStateUpdated instanceof Closure) {
                $field->afterStateUpdated(function (
                    mixed $state,
                    Field $component,
                ) use ($titleAfterStateUpdated, $locale): void {
                    $component->evaluate($titleAfterStateUpdated, ['state' => $state, 'locale' => $locale]);
                });
            }
        });

        if ($translatableFieldsConfigurator instanceof Closure) {
            $translatableFields = $translatableFieldsConfigurator($translatableFields) ?? $translatableFields;
        }

        return $translatableFields;
    }

    /**
     * @return Closure(mixed, Set, Get, string, Field): void
     */
    protected static function titleSlugSyncCallback(
        string $fieldSlug,
        string $autoUpdateDisabledField,
        bool|Closure $preserveSlugOnEdit,
        SlugField $slugField,
        ?Closure $titleAfterStateUpdated,
    ): Closure {
        return function (
            mixed $state,
            Set $set,
            Get $get,
            string $operation,
            Field $component,
        ) use (
            $fieldSlug,
            $autoUpdateDisabledField,
            $preserveSlugOnEdit,
            $slugField,
            $titleAfterStateUpdated,
        ): void {
            $record = $component->getRecord();

            if ((bool) $get($autoUpdateDisabledField)) {
                if ($titleAfterStateUpdated instanceof Closure) {
                    $component->evaluate($titleAfterStateUpdated, ['state' => $state]);
                }

                return;
            }

            if (
                (bool) (is_bool($preserveSlugOnEdit) ? $preserveSlugOnEdit : $component->evaluate($preserveSlugOnEdit))
                && $operation === 'edit'
                && $record !== null
            ) {
                if ($titleAfterStateUpdated instanceof Closure) {
                    $component->evaluate($titleAfterStateUpdated, ['state' => $state]);
                }

                return;
            }

            if (filled($state)) {
                $set($fieldSlug, $slugField->generateSlugFromSource((string) $state, $get));
            }

            if ($titleAfterStateUpdated instanceof Closure) {
                $component->evaluate($titleAfterStateUpdated, ['state' => $state]);
            }
        };
    }
}
