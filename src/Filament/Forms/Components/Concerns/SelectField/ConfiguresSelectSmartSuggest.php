<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\SelectField;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SelectField;
use Closure;

/**
 * @mixin SelectField
 */
trait ConfiguresSelectSmartSuggest
{
    protected bool|Closure $allowCreateOption = false;

    /**
     * @var array<int|string>|Closure
     */
    protected array|Closure $recentOptionValues = [];

    /**
     * @var array<int|string>|Closure
     */
    protected array|Closure $suggestedOptionValues = [];

    protected bool|Closure $entityMentionsEnabled = false;

    protected string|Closure $entityMentionTrigger = '@';

    public function allowCreateOption(bool|Closure $condition = true): static
    {
        $this->allowCreateOption = $condition;

        return $this;
    }

    public function allowsCreateOption(): bool
    {
        return (bool) $this->evaluate($this->allowCreateOption);
    }

    /**
     * @param  array<int|string>|Closure  $values
     */
    public function recentOptions(array|Closure $values): static
    {
        $this->recentOptionValues = $values;

        return $this;
    }

    /**
     * @return list<int|string>
     */
    public function getRecentOptionValues(): array
    {
        $values = $this->evaluate($this->recentOptionValues);

        return is_array($values) ? array_values($values) : [];
    }

    /**
     * @param  array<int|string>|Closure  $values
     */
    public function suggestedOptions(array|Closure $values): static
    {
        $this->suggestedOptionValues = $values;

        return $this;
    }

    /**
     * @return list<int|string>
     */
    public function getSuggestedOptionValues(): array
    {
        $values = $this->evaluate($this->suggestedOptionValues);

        return is_array($values) ? array_values($values) : [];
    }

    /**
     * Enable @-mention / entity picker mode (async search driven).
     */
    public function entityMentions(bool|Closure $condition = true, string|Closure $trigger = '@'): static
    {
        $this->entityMentionsEnabled = $condition;
        $this->entityMentionTrigger = $trigger;

        return $this;
    }

    public function hasEntityMentions(): bool
    {
        return (bool) $this->evaluate($this->entityMentionsEnabled);
    }

    public function getEntityMentionTrigger(): string
    {
        return (string) $this->evaluate($this->entityMentionTrigger);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     recent: list<int|string>,
     *     suggested: list<int|string>,
     *     allowCreate: bool,
     *     createLabel: string,
     *     entityMentions: bool,
     *     mentionTrigger: string,
     * }
     */
    public function getSmartSuggestConfigForJs(): array
    {
        return [
            'enabled' => $this->getRecentOptionValues() !== []
                || $this->getSuggestedOptionValues() !== []
                || $this->allowsCreateOption(),
            'recent' => $this->getRecentOptionValues(),
            'suggested' => $this->getSuggestedOptionValues(),
            'allowCreate' => $this->allowsCreateOption(),
            'createLabel' => (string) __('filament-flex-fields::default.select_field.smart_suggest.create'),
            'entityMentions' => $this->hasEntityMentions(),
            'mentionTrigger' => $this->getEntityMentionTrigger(),
        ];
    }
}
