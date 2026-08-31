<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\ChoiceCards;
use Bjanczak\FilamentFlexFields\Support\Choice\RichOptionSchemaV2;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators\Concerns\NormalizesStudioChoiceOptions;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class ChoiceCardsFieldConfigurator implements FieldConfigurator
{
    use NormalizesStudioChoiceOptions;

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof ChoiceCards);

        return $this->configureChoiceCardsField($field, $config);
    }

    public function configureChoiceCardsField(ChoiceCards $field, array $config): ChoiceCards
    {
        $gridColumns = $config['grid_columns'] ?? $config['columns'] ?? 1;

        if (is_string($gridColumns) && is_numeric($gridColumns)) {
            $gridColumns = (int) $gridColumns;
        }

        $field = $field
            ->options($this->normalizeChoiceCardsOptions($config['options'] ?? []))
            ->layout($config['layout'] ?? 'stack')
            ->gridColumns($gridColumns)
            ->size($config['size'] ?? config('filament-flex-fields.ui.choice_cards_size', 'md'))
            ->variant($config['variant'] ?? config('filament-flex-fields.ui.choice_cards_variant', 'default'))
            ->color(filled($config['color'] ?? null) ? $config['color'] : null)
            ->ripple((bool) ($config['ripple'] ?? false));

        if (isset($config['indicator'])) {
            $field->indicator($config['indicator']);
        }

        if (isset($config['disabled_options']) && is_array($config['disabled_options'])) {
            $field->disabledOptions($config['disabled_options']);
        }

        return $field;
    }

    /**
     * @return array<string|int, string|array<string, mixed>>
     */
    private function normalizeChoiceCardsOptions(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        if (! $this->optionsAreRichArrays($raw)) {
            return $raw;
        }

        $normalized = RichOptionSchemaV2::normalizeCards($raw);

        return $this->mergeChoiceCardExtras($normalized, $raw);
    }

    private function optionsAreRichArrays(array $raw): bool
    {
        if (array_is_list($raw)) {
            foreach ($raw as $row) {
                if (is_array($row)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($raw as $option) {
            if (is_array($option)) {
                return true;
            }
        }

        return false;
    }

    /**
     * RichOptionSchemaV2 covers Choice OS fields; merge card-only keys (price, badge_color, …).
     *
     * @param  array<string|int, string|array<string, mixed>>  $normalized
     * @param  array<int|string, array<string, mixed>|string|int>|list<array<string, mixed>|string|int>  $raw
     * @return array<string|int, string|array<string, mixed>>
     */
    private function mergeChoiceCardExtras(array $normalized, array $raw): array
    {
        $cardKeys = ['price', 'price_suffix', 'suffix', 'meta', 'badge_color'];
        $rawByValue = $this->indexRawOptionsByValue($raw);

        foreach ($normalized as $value => $option) {
            $extras = $rawByValue[(string) $value] ?? null;

            if ($extras === null) {
                continue;
            }

            $payload = is_array($option)
                ? $option
                : ['label' => is_string($option) ? $option : (string) $value];

            foreach ($cardKeys as $key) {
                if (! array_key_exists($key, $extras) || ! filled($extras[$key])) {
                    continue;
                }

                if ($key === 'suffix') {
                    $payload['price_suffix'] = (string) $extras['suffix'];

                    continue;
                }

                $payload[$key] = $extras[$key];
            }

            $normalized[$value] = $payload;
        }

        return $normalized;
    }

    /**
     * @param  array<int|string, array<string, mixed>|string|int>|list<array<string, mixed>|string|int>  $raw
     * @return array<string, array<string, mixed>>
     */
    private function indexRawOptionsByValue(array $raw): array
    {
        $indexed = [];

        if (array_is_list($raw)) {
            foreach ($raw as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $value = (string) ($row['value'] ?? $row['label'] ?? "option_{$index}");
                $indexed[$value] = $row;
            }

            return $indexed;
        }

        foreach ($raw as $value => $option) {
            if (is_array($option)) {
                $indexed[(string) ($option['value'] ?? $value)] = $option;

                continue;
            }

            $indexed[(string) $value] = ['label' => (string) $option, 'value' => $value];
        }

        return $indexed;
    }
}
