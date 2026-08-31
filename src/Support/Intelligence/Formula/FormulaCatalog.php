<?php

declare(strict_types=1);

/**
 * @author Bartłomiej Janczak <barek122@gmail.com>
 * @copyright Copyright (c) 2026 Bartłomiej Janczak. All rights reserved.
 * @license Proprietary
 */

namespace Bjanczak\FilamentFlexFields\Support\Intelligence\Formula;

/**
 * Shared whitelist and patterns for the formula pipeline.
 *
 * @internal
 */
final class FormulaCatalog
{
    public const FIELD_REF_PATTERN = '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/';

    /**
     * @var list<string>
     */
    public const ALLOWED_FUNCTIONS = [
        'abs',
        'min',
        'max',
        'round',
        'floor',
        'ceil',
        'trunc',
        'clamp',
        'sum',
        'avg',
        'pct',
        'pow',
        'sqrt',
        'sign',
        'between',
        'and',
        'or',
        'not',
        'coalesce',
        'if',
        'nz',
    ];

    /**
     * Functions that must not evaluate unused arguments.
     *
     * @var list<string>
     */
    public const SHORT_CIRCUIT_FUNCTIONS = [
        'if',
        'and',
        'or',
        'coalesce',
        'nz',
    ];
}
