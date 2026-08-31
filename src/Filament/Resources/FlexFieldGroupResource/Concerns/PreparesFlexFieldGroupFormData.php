<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Resources\FlexFieldGroupResource\Concerns;

use Bjanczak\FilamentFlexFields\Support\Schema\FlexFieldGroupValidator;

trait PreparesFlexFieldGroupFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function prepareFlexFieldGroupFormData(array $data): array
    {
        if (isset($data['fields']) && is_array($data['fields'])) {
            $data['fields'] = app(FlexFieldGroupValidator::class)->prepareFieldsForForm($data['fields']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->prepareFlexFieldGroupFormData($data);
    }
}
