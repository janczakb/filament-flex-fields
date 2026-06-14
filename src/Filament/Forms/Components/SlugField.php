<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Concerns\InteractsWithSlugUnique;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\ConfiguresSlugActionButtons;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\ConfiguresSlugBehavior;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\ConfiguresSlugPermalink;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\ConfiguresSlugTitleField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\GeneratesSlugFromSource;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\InteractsWithSlugTranslatableTitle;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\ResolvesSlugStatePaths;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;

class SlugField extends Field
{
    use CanBeReadOnly;
    use ConfiguresSlugActionButtons;
    use ConfiguresSlugBehavior;
    use ConfiguresSlugPermalink;
    use ConfiguresSlugTitleField;
    use GeneratesSlugFromSource;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;
    use InteractsWithSlugTranslatableTitle;
    use InteractsWithSlugUnique;
    use ResolvesSlugStatePaths;

    protected string $view = 'filament-flex-fields::forms.components.slug-field';

    protected function setUp(): void
    {
        parent::setUp();

        $this->default('');

        $this->afterStateHydrated(function (SlugField $component, mixed $state): void {
            $component->state($component->normalizeSlug(is_string($state) ? $state : ''));
        });

        $this->dehydrateStateUsing(function (SlugField $component, mixed $state): ?string {
            if ($state === null || $state === '') {
                return null;
            }

            $normalized = $component->normalizeSlug((string) $state);

            return $normalized === '' ? null : $normalized;
        });

        $this->applySlugUniqueValidation();

        $this->afterStateUpdated(function (SlugField $component, mixed $state): void {
            if (! $component->slugAfterStateUpdated instanceof Closure) {
                return;
            }

            $resolvedState = is_string($state)
                ? $component->normalizeSlug($state)
                : $state;

            $component->evaluate($component->slugAfterStateUpdated, ['state' => $resolvedState]);
        });

        $this->rule(function (SlugField $component, Get $get): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component, $get): void {
                if ($component->shouldUseInlineEditing()) {
                    $pendingField = $component->getInlineEditPendingFieldName();

                    if ((bool) $get($pendingField)) {
                        $fail(__('filament-flex-fields::default.validation.slug.inline_edit_pending'));

                        return;
                    }
                }

                if ($value === null || $value === '') {
                    if ($component->isRequired()) {
                        $fail(__('validation.required', ['attribute' => $component->getLabel()]));
                    }

                    return;
                }

                if (! is_string($value)) {
                    $fail(__('filament-flex-fields::default.validation.slug.invalid'));

                    return;
                }

                $normalized = $component->normalizeSlug($value);

                if ($normalized === '' && ! ($component->allowsHomepageSlug() && trim($value) === '/')) {
                    $fail(__('filament-flex-fields::default.validation.slug.invalid'));

                    return;
                }

                if ($component->allowsHomepageSlug() && $normalized === '/') {
                    return;
                }

                $pattern = $component->getRegex();

                if ($pattern !== '' && ! preg_match($pattern, $normalized)) {
                    $fail(__('filament-flex-fields::default.validation.slug.pattern'));
                }
            };
        });
    }

    /**
     * Convenience schema: title field, auto-update flag, and slug field in one call.
     *
     * @param  array<int, string|Closure>|Closure  $titleRules
     * @param  array<int, string|Closure>|Closure  $slugRules
     */
    public static function withTitle(
        ?string $fieldTitle = null,
        ?string $fieldSlug = null,
        ?Field $titleField = null,
        array|Closure $titleRules = ['required', 'string'],
        array|Closure $slugRules = ['required', 'string'],
        bool $titleAutofocus = false,
        ?Closure $slugConfigurator = null,
    ): FusedGroup {
        return TitleSlugField::make(
            fieldTitle: $fieldTitle,
            fieldSlug: $fieldSlug,
            titleField: $titleField,
            titleRules: $titleRules,
            slugRules: $slugRules,
            titleAutofocus: $titleAutofocus,
            slugConfigurator: $slugConfigurator,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlpineConfiguration(): array
    {
        $recordSlug = $this->getRecordSlug();
        $currentSlug = null;

        if ($this->isRootMounted()) {
            $state = $this->getState();
            $currentSlug = is_string($state) ? $this->normalizeSlug($state) : null;
        }

        $resolvedVisitUrl = $this->canVisitLink() && (filled($currentSlug) || filled($recordSlug))
            ? $this->getFullPermalinkUrl($currentSlug ?? $recordSlug ?? '')
            : null;

        return [
            'sourcePath' => $this->getSourceStatePath(),
            'sourceLive' => $this->isSourceLive(),
            'separator' => $this->getSeparator(),
            'maxLength' => $this->getMaxSlugLength(),
            'urlHost' => $this->isUrlHostVisible() ? $this->getUrlHost() : null,
            'urlPath' => $this->isUrlPathVisible() ? $this->getUrlPath() : null,
            'urlPostfix' => $this->getSlugLabelPostfix(),
            'urlHostVisible' => $this->isUrlHostVisible(),
            'permalinkPreview' => $this->hasPermalinkPreview() && $this->hasUrlContext(),
            'visitUrl' => $resolvedVisitUrl,
            'showVisitLink' => $this->shouldShowVisitLink(),
            'canVisitLink' => $this->canVisitLink(),
            'showCopyButton' => $this->shouldShowCopyButton(),
            'showRegenerateButton' => $this->shouldShowRegenerateButton(),
            'showActionButtonLabels' => $this->shouldShowActionButtonLabels(),
            'autoUpdateDisabledPath' => $this->getAutoUpdateDisabledStatePath(),
            'inlineEditPendingPath' => $this->getInlineEditPendingStatePath(),
            'inlineEditing' => $this->shouldUseInlineEditing(),
            'autoGenerate' => $this->shouldAutoGenerate(),
            'preserveOnEdit' => $this->shouldPreserveSlugOnEdit(),
            'initialAutoSyncDisabled' => $this->getInitialAutoSyncDisabled(),
            'allowHomepage' => $this->allowsHomepageSlug(),
            'slugReadOnly' => $this->isSlugReadOnly(),
            'debounceMs' => $this->getDebounceMilliseconds(),
            'recordSlug' => $recordSlug,
            'selfHealingPermalink' => $this->usesSelfHealingPermalink(),
            'permalinkRecordKey' => $this->getPermalinkRecordKey(),
            'selfHealingSeparator' => $this->getSelfHealingSeparator(),
            'placeholder' => $this->getPlaceholder() ?? __('filament-flex-fields::default.slug.placeholder'),
            'labels' => $this->getUiLabels(),
            'serverGenerate' => $this->shouldUseServerSideGeneration(),
            'slugSourceLocale' => $this->usesTranslatableTitle() ? $this->getSlugSourceLocale() : null,
            'componentKey' => $this->isRootMounted() ? $this->getKey() : null,
            'liveUniqueValidation' => $this->shouldValidateSlugUniquenessLive(),
            'uniqueTakenMessage' => $this->getSlugUniqueValidationMessage(),
        ];
    }

    #[ExposedLivewireMethod]
    #[Renderless]
    public function generateSlugPreview(string $source): string
    {
        return $this->generateSlugFromSource($source);
    }

    /**
     * @return array{available: bool, message: string|null}
     */
    #[ExposedLivewireMethod]
    #[Renderless]
    public function checkSlugAvailability(string $slug): array
    {
        return $this->checkSlugUniqueness($slug);
    }

    /**
     * @return array<string, string>
     */
    public function getUiLabels(): array
    {
        return [
            'auto' => __('filament-flex-fields::default.slug.badge_auto'),
            'custom' => __('filament-flex-fields::default.slug.badge_custom'),
            'permalink' => $this->getPermalinkLabel(),
            'edit' => __('filament-flex-fields::default.slug.edit'),
            'confirm' => __('filament-flex-fields::default.slug.confirm'),
            'cancel' => __('filament-flex-fields::default.slug.cancel'),
            'reset' => __('filament-flex-fields::default.slug.reset'),
            'regenerate' => __('filament-flex-fields::default.slug.regenerate'),
            'copy' => __('filament-flex-fields::default.slug.copy'),
            'copied' => __('filament-flex-fields::default.slug.copied'),
            'visit' => $this->getVisitLinkLabel(),
            'changed' => __('filament-flex-fields::default.slug.changed'),
        ];
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-slug-field-field',
            'fff-flex-text-input-field',
            'fff-slug-field-field--'.$this->getSize(),
            'fff-flex-text-input-field--'.$this->getSize(),
            'fff-slug-field-field--'.$this->getVariant(),
        ];
    }
}
