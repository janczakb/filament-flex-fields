<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\Spatie\FlexSpatieTagsField;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TagsField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class TagsFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof TagsField);

        return $this->configureTagsField($field, $config);
    }

    public function configureTagsField(TagsField $field, array $config): TagsField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.tags_size', 'md'))
            ->variant($config['variant'] ?? 'primary');

        if (array_key_exists('separator', $config) && filled($config['separator'])) {
            $field->separator($config['separator']);
        }

        if (isset($config['split_keys']) && is_array($config['split_keys'])) {
            $field->splitKeys($config['split_keys']);
        }

        if (isset($config['suggestions']) && is_array($config['suggestions'])) {
            $field->suggestions($config['suggestions']);
        }

        if (array_key_exists('tag_prefix', $config) && filled($config['tag_prefix'])) {
            $field->tagPrefix($config['tag_prefix']);
        }

        if (array_key_exists('tag_suffix', $config) && filled($config['tag_suffix'])) {
            $field->tagSuffix($config['tag_suffix']);
        }

        if (array_key_exists('reorderable', $config)) {
            $field->reorderable((bool) $config['reorderable']);
        }

        if (isset($config['color'])) {
            $field->color($config['color']);
        }

        if (array_key_exists('trim', $config) && (bool) $config['trim']) {
            $field->trim();
        }

        if (array_key_exists('max_tags', $config)) {
            $field->maxTags(is_numeric($config['max_tags']) ? (int) $config['max_tags'] : null);
        }

        if (array_key_exists('suggestions_only', $config)) {
            $field->suggestionsOnly((bool) $config['suggestions_only']);
        }

        if (array_key_exists('duplicate_insensitive', $config)) {
            $field->duplicateInsensitive((bool) $config['duplicate_insensitive']);
        }

        if (array_key_exists('show_tag_count', $config)) {
            $field->showTagCount((bool) $config['show_tag_count']);
        }

        if ($field instanceof FlexSpatieTagsField && array_key_exists('spatie_tag_type', $config)) {
            $field->type($config['spatie_tag_type']);
        }

        return $field;
    }
}
