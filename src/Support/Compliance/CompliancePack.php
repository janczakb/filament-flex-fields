<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Compliance;

use Bjanczak\FilamentFlexFields\Enums\FieldType;
use Bjanczak\FilamentFlexFields\Support\Locale\FlexFieldsLocale;

/**
 * Product compliance inventory + WCAG 2.2 AA matrix for Flex Fields lazy assets.
 */
final class CompliancePack
{
    public const string STATUS_PENDING = 'pending';

    public const string STATUS_PASS = 'pass';

    public const string STATUS_FAIL = 'fail';

    /** @var array<string, 'pending'|'pass'|'fail'> */
    private static array $overrides = [];

    public static function inventoryFields(): int
    {
        return count(FieldType::cases());
    }

    /**
     * Components that ship with automated keyboard / focus smoke coverage
     * (Playwright field-smoke + headless select E2E). Manual VPAT still required
     * for full AA sign-off — these are product baselines, not a legal claim.
     *
     * @return list<string>
     */
    public static function baselinePassComponents(): array
    {
        return [
            'select-field',
            'teleported-menu',
            'overlay-runtime',
            'phone-field',
            'country-field',
            'timezone-field',
            'schedule-field',
            'flex-file-upload',
        ];
    }

    /**
     * @return array<string, 'pending'|'pass'|'fail'>
     */
    public static function aaMatrix(): array
    {
        $matrix = [];

        foreach (FieldType::cases() as $type) {
            foreach ($type->assetComponents() as $component) {
                $matrix[$component] = self::STATUS_PENDING;
            }
        }

        foreach (self::baselinePassComponents() as $component) {
            if (array_key_exists($component, $matrix)) {
                $matrix[$component] = self::STATUS_PASS;
            }
        }

        foreach (self::$overrides as $component => $status) {
            $matrix[$component] = $status;
        }

        ksort($matrix);

        return $matrix;
    }

    /**
     * @deprecated Use {@see aaMatrix()} — kept for callers that still import the stub name.
     *
     * @return array<string, 'pending'|'pass'|'fail'>
     */
    public static function aaMatrixStub(): array
    {
        return self::aaMatrix();
    }

    /**
     * @param  'pending'|'pass'|'fail'  $status
     */
    public static function mark(string $component, string $status): void
    {
        if (! in_array($status, [self::STATUS_PENDING, self::STATUS_PASS, self::STATUS_FAIL], true)) {
            return;
        }

        self::$overrides[$component] = $status;
    }

    public static function clearOverrides(): void
    {
        self::$overrides = [];
    }

    /**
     * @return array{
     *     generated_at: string,
     *     standard: string,
     *     locales: list<string>,
     *     field_type_count: int,
     *     aa_matrix: array<string, 'pending'|'pass'|'fail'>,
     *     summary: array{pass: int, pending: int, fail: int},
     *     criteria: list<string>
     * }
     */
    public static function exportReport(): array
    {
        $matrix = self::aaMatrix();
        $summary = [
            'pass' => 0,
            'pending' => 0,
            'fail' => 0,
        ];

        foreach ($matrix as $status) {
            $summary[$status] = ($summary[$status] ?? 0) + 1;
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'standard' => 'WCAG 2.2 Level AA (product baseline)',
            'locales' => FlexFieldsLocale::supportedLocales(),
            'field_type_count' => self::inventoryFields(),
            'aa_matrix' => $matrix,
            'summary' => $summary,
            'criteria' => [
                'Keyboard operable (no mouse-only traps)',
                'Visible focus treatment on interactive controls',
                'Accessible name / role / value for custom widgets',
                'Programmatic labels for inputs',
                'Error identification for validation messages',
                'Contrast targets documented for theme tokens',
            ],
        ];
    }
}
