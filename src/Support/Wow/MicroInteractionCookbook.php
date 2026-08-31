<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Wow;

/**
 * Named micro-interaction recipes for the Wow Product Layer (M12).
 *
 * Each recipe documents CSS hooks Playground hubs and field runtimes can reuse
 * instead of one-off transition strings.
 */
final class MicroInteractionCookbook
{
    public const CHECK_ENTER = 'check-enter';

    public const CHIP_ADD = 'chip-add';

    public const SHEET_SNAP = 'sheet-snap';

    public const HOLD_PULSE = 'hold-pulse';

    /**
     * @return list<array{
     *     id: string,
     *     description: string,
     *     classes: list<string>,
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'id' => self::CHECK_ENTER,
                'description' => 'Stroke-draw check enter/exit on list and grid Select options.',
                'classes' => [
                    'fff-wow-check-enter',
                    'fff-select-option-selected-check',
                    'fff-select-option-selected-check__svg',
                ],
            ],
            [
                'id' => self::CHIP_ADD,
                'description' => 'Scale/fade chip mount for multi-select badges and TagsField rows.',
                'classes' => [
                    'fff-wow-chip-add',
                    'fff-tags-field__tag',
                ],
            ],
            [
                'id' => self::SHEET_SNAP,
                'description' => 'Bottom sheet snap + drag dismiss for overlay runtime panels.',
                'classes' => [
                    'fff-wow-sheet-snap',
                    'fff-overlay-sheet',
                    'fff-overlay-panel',
                ],
            ],
            [
                'id' => self::HOLD_PULSE,
                'description' => 'Press-and-hold progress pulse for destructive HoldConfirm actions.',
                'classes' => [
                    'fff-wow-hold-pulse',
                    'fff-hold-confirm-action',
                    'fff-hold-confirm-action__overlay',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }

    /**
     * @return array{
     *     id: string,
     *     description: string,
     *     classes: list<string>,
     * }|null
     */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $recipe) {
            if ($recipe['id'] === $id) {
                return $recipe;
            }
        }

        return null;
    }
}
