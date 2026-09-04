<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\TodoListField;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class TodoListFieldConfigurator implements FieldConfigurator
{
    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof TodoListField);

        return $this->configureTodoListField($field, $config);
    }

    public function configureTodoListField(TodoListField $field, array $config): TodoListField
    {
        $field = $field
            ->options($config['options'] ?? [])
            ->size($config['size'] ?? 'md')
            ->color($config['color'] ?? 'primary')
            ->sounds((bool) ($config['sounds'] ?? true))
            ->strikethroughStyle((string) ($config['strikethrough_style'] ?? 'hand'))
            ->doneSettleMs((int) ($config['done_settle_ms'] ?? 500))
            ->allowCreate((bool) ($config['allow_create'] ?? false))
            ->allowDelete((bool) ($config['allow_delete'] ?? false))
            ->deletable((string) ($config['deletable'] ?? 'created'))
            ->searchable((bool) ($config['searchable'] ?? false))
            ->virtualizing((bool) ($config['virtualizing'] ?? false))
            ->persistCompletedOrder((bool) ($config['persist_completed_order'] ?? false));

        if (array_key_exists('celebration', $config)) {
            $field->celebration($config['celebration']);
        }

        if (array_key_exists('celebration_fullscreen', $config)) {
            $field->celebrationFullscreen((bool) $config['celebration_fullscreen']);
        }

        if (isset($config['celebration_duration_ms'])) {
            $field->celebrationDurationMs((int) $config['celebration_duration_ms']);
        }

        if (isset($config['check_sound'])) {
            $field->checkSound((string) $config['check_sound']);
        }

        if (isset($config['create_label'])) {
            $field->createLabel((string) $config['create_label']);
        }

        if (isset($config['create_placeholder'])) {
            $field->createPlaceholder((string) $config['create_placeholder']);
        }

        if (isset($config['search_prompt'])) {
            $field->searchPrompt((string) $config['search_prompt']);
        }

        if (array_key_exists('paginated', $config)) {
            $field->paginated($config['paginated']);
        }

        if (array_key_exists('infinite_scroll', $config)) {
            $field->infiniteScroll($config['infinite_scroll']);
        }

        if (isset($config['remote_loader'])) {
            $field->remoteLoader((string) $config['remote_loader']);
        }

        if (array_key_exists('max_items', $config)) {
            $field->maxItems($config['max_items']);
        }

        if (array_key_exists('min_done', $config)) {
            $field->minDone($config['min_done']);
        }

        if (array_key_exists('max_done', $config)) {
            $field->maxDone($config['max_done']);
        }

        if (array_key_exists('exact_done', $config)) {
            $field->exactDone($config['exact_done']);
        }

        return $field;
    }
}
