<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Configurators;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldConfigurator;
use Filament\Schemas\Components\Component;

final class UserSelectFieldConfigurator implements FieldConfigurator
{
    public function __construct(
        private readonly SelectFieldConfigurator $select = new SelectFieldConfigurator,
    ) {}

    public function configure(Component $field, array $config): Component
    {
        assert($field instanceof UserSelect);

        return $this->configureUserSelectField($field, $config);
    }

    public function configureUserSelectField(UserSelect $field, array $config): UserSelect
    {
        $this->select->configure($field, $config);

        $field->nameColumn($config['name_column'] ?? 'name');

        if (array_key_exists('email_column', $config) && filled($config['email_column'])) {
            $field->emailColumn((string) $config['email_column']);
        }

        if (array_key_exists('avatar_column', $config) && filled($config['avatar_column'])) {
            $field->avatarColumn((string) $config['avatar_column']);
        }

        if (array_key_exists('verification_column', $config) && filled($config['verification_column'])) {
            $field->verificationColumn($config['verification_column']);
        }

        if (isset($config['max_visible_avatars'])) {
            $field->maxVisibleAvatars((int) $config['max_visible_avatars']);
        }

        if ((bool) ($config['multiple'] ?? false)) {
            $field->multiple();
        }

        if (filled($config['option_model'] ?? $config['model'] ?? null)) {
            $field->optionModel((string) ($config['option_model'] ?? $config['model']));
        } elseif (! empty($config['options'])) {
            $field->options($config['options']);
        }

        return $field;
    }
}
