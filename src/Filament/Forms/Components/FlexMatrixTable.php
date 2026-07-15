<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldRounding;
use Closure;
use Filament\Forms\Components\Repeater;

class FlexMatrixTable extends Repeater
{
    use HasControlSize;
    use HasFieldRounding;

    protected string $view = 'filament-flex-fields::forms.components.flex-matrix-table';

    /**
     * @var array<string | int, string | array<string, mixed>> | Closure
     */
    protected array|Closure $rows = [];

    /**
     * @var array<string, string> | Closure
     */
    protected array|Closure $columnWidths = [];

    /**
     * @var array<string, array{
     *     label: string,
     *     description: ?string,
     *     disabled: bool,
     * }>|null
     */
    protected ?array $normalizedRowsCache = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addable(false);
        $this->deletable(false);
        $this->reorderable(false);
    }

    public function hydrateItems(): void
    {
        $state = $this->getRawState();
        if (! is_array($state)) {
            $state = [];
        }

        $normalized = [];

        foreach ($this->getRowKeys() as $key) {
            $normalized[$key] = $state[$key] ?? [];
        }

        $this->state($normalized);
    }

    public function dehydrateItems(?array $state): array
    {
        return $state ?? [];
    }

    /**
     * @param  array<string | int, string | array<string, mixed>> | Closure  $rows
     */
    public function rows(array|Closure $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * @param  array<string, string> | Closure  $widths
     */
    public function columnWidths(array|Closure $widths): static
    {
        $this->columnWidths = $widths;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getColumnWidths(): array
    {
        return $this->evaluate($this->columnWidths) ?? [];
    }

    /**
     * @return list<string>
     */
    public function getRowKeys(): array
    {
        return array_keys($this->getNormalizedRows());
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     description: ?string,
     *     disabled: bool,
     * }>
     */
    public function getNormalizedRows(): array
    {
        if ($this->normalizedRowsCache !== null) {
            return $this->normalizedRowsCache;
        }

        $normalized = [];

        foreach ($this->evaluate($this->rows) as $value => $row) {
            $key = (string) $value;

            if (is_string($row)) {
                $normalized[$key] = [
                    'label' => $row,
                    'description' => null,
                    'disabled' => false,
                ];

                continue;
            }

            if (is_array($row)) {
                $normalized[$key] = array_merge($row, [
                    'label' => (string) ($row['label'] ?? $key),
                    'description' => filled($row['description'] ?? $row['desc'] ?? null)
                        ? (string) ($row['description'] ?? $row['desc'])
                        : null,
                    'disabled' => (bool) ($row['disabled'] ?? false),
                ]);
            }
        }

        return $this->normalizedRowsCache = $normalized;
    }

    /**
     * @return list<string>
     */
    public function getWrapperClasses(): array
    {
        $classes = [
            'fff-matrix-choice',
            'fff-matrix-choice--'.$this->getSize(),
            'fff-rounding-'.$this->getRounding(),
        ];

        return $classes;
    }

    /**
     * @return array<string, string>
     */
    public function getMatrixSizeStyles(): array
    {
        return match ($this->getSize()) {
            'sm' => [
                '--fff-matrix-choice-radius' => '0.875rem',
                '--fff-matrix-choice-frame-padding' => '0.3125rem',
                '--fff-matrix-choice-header-py' => '0.625rem',
                '--fff-matrix-choice-header-px' => '0.5rem',
                '--fff-matrix-choice-row-py' => '0.625rem',
                '--fff-matrix-choice-row-px' => '0.75rem',
                '--fff-matrix-choice-row-label-size' => '0.8125rem',
                '--fff-matrix-choice-column-label-size' => '0.75rem',
            ],
            'lg' => [
                '--fff-matrix-choice-radius' => '1.125rem',
                '--fff-matrix-choice-frame-padding' => '0.5rem',
                '--fff-matrix-choice-header-py' => '0.875rem',
                '--fff-matrix-choice-header-px' => '0.75rem',
                '--fff-matrix-choice-row-py' => '0.875rem',
                '--fff-matrix-choice-row-px' => '1rem',
                '--fff-matrix-choice-row-label-size' => '1rem',
                '--fff-matrix-choice-column-label-size' => '0.875rem',
            ],
            default => [
                '--fff-matrix-choice-radius' => '1rem',
                '--fff-matrix-choice-frame-padding' => '0.375rem',
                '--fff-matrix-choice-header-py' => '0.75rem',
                '--fff-matrix-choice-header-px' => '0.625rem',
                '--fff-matrix-choice-row-py' => '0.75rem',
                '--fff-matrix-choice-row-px' => '0.875rem',
                '--fff-matrix-choice-row-label-size' => '0.875rem',
                '--fff-matrix-choice-column-label-size' => '0.8125rem',
            ],
        };
    }
}
