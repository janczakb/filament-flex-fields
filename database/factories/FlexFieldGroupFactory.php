<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Database\Factories;

use Bjanczak\FilamentFlexFields\Models\FlexFieldGroup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FlexFieldGroup>
 */
class FlexFieldGroupFactory extends Factory
{
    protected $model = FlexFieldGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'fields' => [
                [
                    'slug' => 'company_name',
                    'label' => 'Company name',
                    'type' => 'single_line_text',
                    'sort' => 0,
                ],
            ],
            'order' => 0,
            'tenant_id' => '',
            'target_type' => 'App\\Models\\Model',
        ];
    }

    public function forTenant(string $tenantId): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $tenantId,
        ]);
    }
}
