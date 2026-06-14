<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\FormBuilder\Handlers;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\FormBuilder\Contracts\FieldTypeHandler;

abstract class AbstractFieldTypeHandler implements FieldTypeHandler
{
    /**
     * @return list<FieldType>
     */
    abstract protected function supportedTypesList(): array;

    public function supportedTypes(): array
    {
        return $this->supportedTypesList();
    }

    public function supports(FieldType $type): bool
    {
        return in_array($type, $this->supportedTypesList(), true);
    }
}
