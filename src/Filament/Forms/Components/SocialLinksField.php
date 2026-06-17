<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Data\SocialPlatform;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\SocialLinks\SocialLinksNormalizer;
use Bjanczak\FilamentFlexFields\Support\SocialLinks\SocialLinkValidator;
use Bjanczak\FilamentFlexFields\Support\SocialLinks\SocialPlatformDefinition;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Field;
use InvalidArgumentException;

class SocialLinksField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;

    protected string $view = 'filament-flex-fields::forms.components.social-links-field';

    protected string|Closure $variant = 'primary';

    /** @var list<string|SocialPlatform>|Closure|null */
    protected array|Closure|null $platforms = null;

    /** @var list<string|SocialPlatform>|Closure */
    protected array|Closure $excludePlatforms = [];

    /** @var list<array<string, mixed>>|Closure */
    protected array|Closure $customPlatforms = [];

    protected int|Closure|null $maxLinks = null;

    protected bool|Closure $reorderable = false;

    protected bool|Closure $autoFormatUrls = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->size('md');

        $this->afterStateHydrated(function (SocialLinksField $component, mixed $state): void {
            $component->state(SocialLinksNormalizer::normalize($state));
        });

        $this->dehydrateStateUsing(function (SocialLinksField $component, mixed $state): ?array {
            $links = SocialLinksNormalizer::normalize($state);

            return SocialLinksNormalizer::dehydrate($links);
        });

        $this->rule(function (SocialLinksField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($component->isRequired() && SocialLinksNormalizer::isEmpty($value)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                $links = SocialLinksNormalizer::normalize($value);
                $definitions = $component->getPlatformDefinitionMap();
                $allowed = array_keys($definitions);

                foreach (SocialLinkValidator::validateCollection($links, $allowed, $definitions) as $index => $message) {
                    $platform = $links[$index]['platform'] ?? (string) $index;
                    $platformLabel = $definitions[$platform]->label ?? $platform;

                    $fail(__('filament-flex-fields::default.social_links.validation.row', [
                        'platform' => $platformLabel,
                        'message' => $message,
                    ]));
                }
            };
        });
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'soft', 'flat', 'ghost'], true)) {
            throw new InvalidArgumentException("Invalid SocialLinksField variant [{$variant}].");
        }

        return $variant;
    }

    /**
     * @param  list<string|SocialPlatform>|Closure|null  $platforms
     */
    public function platforms(array|Closure|null $platforms): static
    {
        $this->platforms = $platforms;

        return $this;
    }

    /**
     * @param  list<string|SocialPlatform>|Closure  $platforms
     */
    public function excludePlatforms(array|Closure $platforms): static
    {
        $this->excludePlatforms = $platforms;

        return $this;
    }

    /**
     * @param  list<array<string, mixed>>|Closure  $platforms
     */
    public function customPlatforms(array|Closure $platforms): static
    {
        $this->customPlatforms = $platforms;

        return $this;
    }

    public function reorderable(bool|Closure $enabled = true): static
    {
        $this->reorderable = $enabled;

        return $this;
    }

    public function isReorderable(): bool
    {
        return (bool) $this->evaluate($this->reorderable);
    }

    public function autoFormatUrls(bool|Closure $enabled = true): static
    {
        $this->autoFormatUrls = $enabled;

        return $this;
    }

    public function shouldAutoFormatUrls(): bool
    {
        return (bool) $this->evaluate($this->autoFormatUrls);
    }

    /**
     * @return list<SocialPlatform>
     */
    public function getPlatforms(): array
    {
        return array_values(array_filter(array_map(
            fn (string $value): ?SocialPlatform => SocialPlatform::tryFrom($value),
            $this->getPlatformValues(),
        )));
    }

    /**
     * @return list<string>
     */
    public function getPlatformValues(): array
    {
        return array_keys($this->getPlatformDefinitionMap());
    }

    /**
     * @return array<string, SocialPlatformDefinition>
     */
    public function getPlatformDefinitionMap(): array
    {
        $customDefinitions = $this->resolveCustomPlatformDefinitions();
        $configured = $this->evaluate($this->platforms);

        if ($configured !== null && is_array($configured)) {
            $definitions = [];

            foreach ($configured as $platform) {
                if ($platform instanceof SocialPlatform) {
                    $definitions[$platform->value] = SocialPlatformDefinition::fromEnum($platform);

                    continue;
                }

                if (! is_string($platform)) {
                    continue;
                }

                $value = trim($platform);

                if ($value === '') {
                    continue;
                }

                if (isset($customDefinitions[$value])) {
                    $definitions[$value] = $customDefinitions[$value];

                    continue;
                }

                $enum = SocialPlatform::tryFrom($value);

                if ($enum !== null) {
                    $definitions[$value] = SocialPlatformDefinition::fromEnum($enum);
                }
            }

            return $definitions !== [] ? $definitions : $this->defaultPlatformDefinitionMap($customDefinitions);
        }

        return $this->defaultPlatformDefinitionMap($customDefinitions);
    }

    /**
     * @return array<string, SocialPlatformDefinition>
     */
    protected function defaultPlatformDefinitionMap(array $customDefinitions): array
    {
        $definitions = [];

        foreach (SocialPlatform::defaults() as $platform) {
            if (in_array($platform->value, $this->getExcludedPlatformValues(), true)) {
                continue;
            }

            $definitions[$platform->value] = SocialPlatformDefinition::fromEnum($platform);
        }

        foreach ($customDefinitions as $value => $definition) {
            $definitions[$value] = $definition;
        }

        return $definitions;
    }

    /**
     * @return array<string, SocialPlatformDefinition>
     */
    protected function resolveCustomPlatformDefinitions(): array
    {
        $configured = $this->evaluate($this->customPlatforms);

        if (! is_array($configured)) {
            return [];
        }

        $definitions = [];

        foreach ($configured as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $definition = SocialPlatformDefinition::fromArray($entry);
            $definitions[$definition->value] = $definition;
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    protected function getExcludedPlatformValues(): array
    {
        $configured = $this->evaluate($this->excludePlatforms);

        if (! is_array($configured)) {
            return [];
        }

        $values = [];

        foreach ($configured as $platform) {
            if ($platform instanceof SocialPlatform) {
                $values[] = $platform->value;

                continue;
            }

            if (is_string($platform) && trim($platform) !== '') {
                $values[] = trim($platform);
            }
        }

        return $values;
    }

    public function maxLinks(int|Closure|null $max): static
    {
        $this->maxLinks = $max;

        return $this;
    }

    public function getMaxLinks(): ?int
    {
        $max = $this->evaluate($this->maxLinks);

        return is_int($max) && $max > 0 ? $max : null;
    }

    /**
     * @return list<array{value: string, label: string, placeholder: string, brand: string, hosts: list<string>}>
     */
    public function getPlatformDefinitions(): array
    {
        return array_map(
            fn (SocialPlatformDefinition $definition): array => $definition->toAlpineArray(),
            array_values($this->getPlatformDefinitionMap()),
        );
    }

    /**
     * @return array<string, string>
     */
    public function getBrandIconSvgs(): array
    {
        $icons = [];

        foreach ($this->getPlatformDefinitionMap() as $value => $definition) {
            if ($definition->iconSvg !== null) {
                $icons[$value] = $definition->iconSvg;

                continue;
            }

            $icons[$value] = view('filament-flex-fields::partials.social-platform-icon', [
                'platform' => $value,
            ])->render();
        }

        return $icons;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlpineConfiguration(): array
    {
        return [
            'platforms' => $this->getPlatformDefinitions(),
            'brandIcons' => $this->getBrandIconSvgs(),
            'maxLinks' => $this->getMaxLinks(),
            'reorderable' => $this->isReorderable(),
            'autoFormatUrls' => $this->shouldAutoFormatUrls(),
            'labels' => [
                'add' => __('filament-flex-fields::default.social_links.add'),
                'addPlatform' => __('filament-flex-fields::default.social_links.add_platform'),
                'choosePlatform' => __('filament-flex-fields::default.social_links.choose_platform'),
                'remove' => __('filament-flex-fields::default.social_links.remove'),
                'moveUp' => __('filament-flex-fields::default.social_links.move_up'),
                'moveDown' => __('filament-flex-fields::default.social_links.move_down'),
                'empty' => __('filament-flex-fields::default.social_links.empty'),
                'maxReached' => __('filament-flex-fields::default.social_links.max_reached'),
                'url' => __('filament-flex-fields::default.social_links.url'),
                'required' => __('filament-flex-fields::default.social_links.validation.required'),
                'unknownPlatform' => __('filament-flex-fields::default.social_links.validation.unknown_platform'),
                'platformNotAllowed' => __('filament-flex-fields::default.social_links.validation.platform_not_allowed'),
                'invalidUrl' => __('filament-flex-fields::default.social_links.validation.invalid_url'),
                'platformMismatch' => __('filament-flex-fields::default.social_links.validation.platform_mismatch', ['platform' => ':platform']),
            ],
            'icons' => [
                'remove' => GravityIcon::TrashBin,
                'chevron' => GravityIcon::ChevronDown,
                'chevronUp' => GravityIcon::ChevronUp,
                'chevronDown' => GravityIcon::ChevronDown,
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-social-links-field' => true,
            'fff-flex-text-input' => true,
            'fff-social-links-field--'.$this->getSize() => true,
            'fff-flex-text-input--'.$this->getSize() => true,
            'fff-social-links-field--'.$this->getVariant() => true,
            'fff-flex-text-input--'.$this->getVariant() => true,
            'fff-social-links-field--reorderable' => $this->isReorderable(),
        ];
    }
}
