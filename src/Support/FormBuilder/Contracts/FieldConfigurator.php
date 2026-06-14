<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts;

use Filament\Schemas\Components\Component;

interface FieldConfigurator
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function configure(Component $field, array $config): Component;
}
