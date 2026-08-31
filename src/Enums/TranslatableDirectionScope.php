<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum TranslatableDirectionScope: string
{
    /**
     * Apply `dir` to the editable control when the field supports input attributes;
     * otherwise fall back to the field wrapper (safe default, no regression).
     */
    case Auto = 'auto';

    /** Apply `dir` only to the native input / textarea (HasExtraInputAttributes). */
    case Input = 'input';

    /** Apply `dir` to the entire field wrapper (label, hint, and control). */
    case Field = 'field';
}
