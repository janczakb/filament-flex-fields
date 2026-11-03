<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Media\Presentation;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use ReflectionMethod;

/** @internal Bridge optional per-item arguments introduced in native Filament 5.11. */
final class NativeImageConfiguration
{
    /** @param class-string|null $declaringClass */
    public static function resolve(object $component, string $method, bool $hasState, ?string $state, ?Model $relatedRecord, ?string $declaringClass = null): ?string
    {
        $native = new ReflectionMethod($declaringClass ?? $component::class, $method);
        $arguments = $hasState ? array_slice([$state, $relatedRecord], 0, $native->getNumberOfParameters()) : [];
        $value = $native->invokeArgs($component, $arguments);
        if ($value !== null && ! is_string($value)) {
            throw new LogicException('Native image configuration must resolve to a string or null.');
        }

        return $value;
    }
}
