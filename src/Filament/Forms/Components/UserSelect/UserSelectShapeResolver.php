<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Illuminate\Database\Eloquent\Model;

class UserSelectShapeResolver
{
    public function __construct(
        protected UserSelect $select,
        protected UserSelectRecordMapper $mapper,
        protected UserSelectQueryEngine $queryEngine,
        protected UserSelectRuntimeState $state,
    ) {}

    /**
     * @return ?array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }
     */
    public function resolveOptionShapeForValue(mixed $value): ?array
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return $this->resolveOptionShapesForValues([$value])[(string) $value] ?? null;
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<string, array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     * }>
     */
    public function resolveOptionShapesForValues(array $values): array
    {
        $shapes = [];
        $missingValues = [];
        $options = $this->select->getOptions();

        foreach ($values as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $key = (string) $value;

            if (isset($this->state->resolvedOptionShapeCache[$key])) {
                $shapes[$key] = $this->state->resolvedOptionShapeCache[$key];

                continue;
            }

            $label = $this->select->findOptionLabelForState($options, $value);

            if (is_array($label)) {
                $shape = [
                    'label' => (string) ($label['label'] ?? $value),
                    'description' => filled($label['description'] ?? null) ? (string) $label['description'] : null,
                    'image' => filled($label['image'] ?? null) ? (string) $label['image'] : null,
                    'verified' => (bool) ($label['verified'] ?? false),
                ];

                $shapes[$key] = $shape;
                $this->state->resolvedOptionShapeCache[$key] = $shape;

                continue;
            }

            if (is_string($label) && filled($label)) {
                $shape = [
                    'label' => $label,
                    'description' => null,
                    'image' => null,
                    'verified' => false,
                ];

                $shapes[$key] = $shape;
                $this->state->resolvedOptionShapeCache[$key] = $shape;

                continue;
            }

            $missingValues[] = $value;
        }

        if ($missingValues !== [] && ($this->select->hasRelationship() || $this->select->getUserModel() !== null)) {
            $this->queryEngine->resolveRecordsForValues($missingValues);

            foreach ($missingValues as $value) {
                $key = (string) $value;
                $record = $this->state->resolvedRecordCache[$key] ?? null;

                if (! $record instanceof Model) {
                    continue;
                }

                $shape = $this->mapper->recordToOptionArray($record);
                $shapes[$key] = $shape;
                $this->state->resolvedOptionShapeCache[$key] = $shape;
            }
        }

        return $shapes;
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<int|string, string>
     */
    public function resolveOptionLabelsForValues(array $values): array
    {
        $labels = [];
        $shapes = $this->resolveOptionShapesForValues($values);

        foreach ($values as $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            $shape = $shapes[(string) $value] ?? null;

            if ($shape === null) {
                $labels[$value] = (string) $value;

                continue;
            }

            $labels[$value] = $shape['label'];
        }

        return $labels;
    }
}
