<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\SocialLinksField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class SocialLinksFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof SocialLinksField);

        return $this->configureSocialLinksField($field, $config);
    }

    public function configureSocialLinksField(SocialLinksField $field, array $config): SocialLinksField
    {
        $field = $field
            ->size($config['size'] ?? 'md')
            ->variant($config['variant'] ?? 'primary');

        if (array_key_exists('platforms', $config) && is_array($config['platforms']) && $config['platforms'] !== []) {
            $field->platforms(array_values(array_filter(array_map(
                static fn (mixed $platform): string => strtolower(trim((string) $platform)),
                $config['platforms'],
            ))));
        }

        if (array_key_exists('exclude_platforms', $config) && is_array($config['exclude_platforms']) && $config['exclude_platforms'] !== []) {
            $field->excludePlatforms(array_values(array_filter(array_map(
                static fn (mixed $platform): string => strtolower(trim((string) $platform)),
                $config['exclude_platforms'],
            ))));
        }

        if (array_key_exists('max_links', $config)) {
            $maxLinks = $config['max_links'];
            $field->maxLinks($maxLinks === null || $maxLinks === '' ? null : (int) $maxLinks);
        }

        if (array_key_exists('reorderable', $config)) {
            $field->reorderable((bool) $config['reorderable']);
        }

        if (array_key_exists('auto_format_urls', $config)) {
            $field->autoFormatUrls((bool) $config['auto_format_urls']);
        }

        return $field;
    }
}
