<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SlugField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class SlugFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof SlugField);

        return $this->configureSlugField($field, $config);
    }

    public function configureSlugField(SlugField $field, array $config): SlugField
    {
        $field = $field
            ->size($config['size'] ?? config('filament-flex-fields.ui.slug_size', 'md'))
            ->variant($config['variant'] ?? 'primary');

        if (array_key_exists('source', $config) && filled($config['source'])) {
            $field->source($config['source']);
        }

        if (array_key_exists('source_live', $config)) {
            $field->sourceLive((bool) $config['source_live']);
        }

        if (array_key_exists('separator', $config)) {
            $field->slugSeparator($config['separator']);
        }

        if (array_key_exists('max_slug_length', $config)) {
            $field->maxSlugLength($config['max_slug_length']);
        }

        if (array_key_exists('url_host', $config)) {
            $field->urlHost($config['url_host']);
        }

        if (array_key_exists('url_path', $config)) {
            $field->urlPath($config['url_path']);
        }

        if (array_key_exists('url_host_visible', $config)) {
            $field->urlHostVisible((bool) $config['url_host_visible']);
        }

        if (array_key_exists('url_path_visible', $config)) {
            $field->urlPathVisible((bool) $config['url_path_visible']);
        }

        if (array_key_exists('permalink_preview', $config)) {
            $field->permalinkPreview((bool) $config['permalink_preview']);
        }

        if (array_key_exists('show_visit_link', $config)) {
            $field->showVisitLink((bool) $config['show_visit_link']);
        }

        if (array_key_exists('show_copy_button', $config)) {
            $field->showCopyButton((bool) $config['show_copy_button']);
        }

        if (array_key_exists('show_regenerate_button', $config)) {
            $field->showRegenerateButton((bool) $config['show_regenerate_button']);
        }

        if (array_key_exists('action_button_labels', $config)) {
            $field->actionButtonLabels((bool) $config['action_button_labels']);
        }

        if (array_key_exists('auto_generate', $config)) {
            $field->autoGenerate((bool) $config['auto_generate']);
        }

        if (array_key_exists('preserve_slug_on_edit', $config)) {
            $field->preserveSlugOnEdit((bool) $config['preserve_slug_on_edit']);
        }

        if (array_key_exists('inline_editing', $config)) {
            $field->inlineEditing((bool) $config['inline_editing']);
        }

        if (array_key_exists('allow_homepage_slug', $config)) {
            $field->allowHomepageSlug((bool) $config['allow_homepage_slug']);
        }

        if (array_key_exists('slug_unique', $config)) {
            $field->slugUnique((bool) $config['slug_unique']);
        }

        if (array_key_exists('slug_unique_parameters', $config) && is_array($config['slug_unique_parameters'])) {
            $field->slugUniqueParameters($config['slug_unique_parameters']);
        }

        if (array_key_exists('slug_readonly', $config)) {
            $field->slugReadOnly((bool) $config['slug_readonly']);
        }

        if (array_key_exists('slug_label_postfix', $config)) {
            $field->slugLabelPostfix($config['slug_label_postfix']);
        }

        if (array_key_exists('debounce', $config)) {
            $field->generationDebounce((int) $config['debounce']);
        }

        if (array_key_exists('spatie_model', $config) && filled($config['spatie_model'])) {
            $field->spatieModel($config['spatie_model']);
        }

        if (array_key_exists('spatie_slug_field', $config)) {
            $field->spatieSlugField($config['spatie_slug_field']);
        }

        if (array_key_exists('spatie_source_field', $config) && filled($config['spatie_source_field'])) {
            $field->spatieSourceField($config['spatie_source_field']);
        }

        if (array_key_exists('translatable_locales', $config) && is_array($config['translatable_locales'])) {
            $field->titleLocales($config['translatable_locales']);
        }

        if (array_key_exists('slug_source_locale', $config) && filled($config['slug_source_locale'])) {
            $field->slugSourceLocale($config['slug_source_locale']);
        }

        if (array_key_exists('spatie_translatable', $config)) {
            $field->spatieTranslatable((bool) $config['spatie_translatable']);
        }

        if (array_key_exists('translatable_title_field', $config) && filled($config['translatable_title_field'])) {
            $field->translatableTitleField($config['translatable_title_field']);
        }

        return $field;
    }
}
