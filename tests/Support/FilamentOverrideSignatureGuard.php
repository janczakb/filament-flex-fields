<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Support;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use RuntimeException;

/**
 * Detects PHP LSP fatals early: a child override must accept every parent
 * parameter type (equal or wider union) and may only add optional trailing params.
 */
final class FilamentOverrideSignatureGuard
{
    /**
     * @param  class-string  $childClass
     * @param  class-string  $parentClass
     * @return list<string>
     */
    public static function assertCompatible(string $childClass, string $parentClass, string $method): array
    {
        $child = self::method($childClass, $method);
        $parent = self::method($parentClass, $method);

        $issues = [];

        $childParams = $child->getParameters();
        $parentParams = $parent->getParameters();

        if (count($childParams) < count($parentParams)) {
            $issues[] = sprintf(
                '%s::%s() has fewer parameters (%d) than %s::%s() (%d).',
                $childClass,
                $method,
                count($childParams),
                $parentClass,
                $method,
                count($parentParams),
            );
        }

        foreach ($parentParams as $index => $parentParam) {
            $childParam = $childParams[$index] ?? null;

            if ($childParam === null) {
                continue;
            }

            if ($parentParam->isPassedByReference() !== $childParam->isPassedByReference()) {
                $issues[] = sprintf(
                    '%s::%s() parameter $%s by-ref mismatch vs %s.',
                    $childClass,
                    $method,
                    $parentParam->getName(),
                    $parentClass,
                );
            }

            $parentTypes = self::normalizeTypes($parentParam->getType());
            $childTypes = self::normalizeTypes($childParam->getType());

            // Untyped parent is always satisfied. Untyped child is wider than a typed parent
            // for call sites that pass any value, but PHP still fatals if the child type
            // is *narrower* — only check when both sides declare types, or child declares.
            if ($parentTypes === [] || $childTypes === []) {
                continue;
            }

            $missing = array_values(array_diff($parentTypes, $childTypes));

            if ($missing !== []) {
                $issues[] = sprintf(
                    '%s::%s() parameter $%s is missing parent type(s) [%s] from %s (child has [%s]). This causes a fatal signature incompatibility.',
                    $childClass,
                    $method,
                    $parentParam->getName(),
                    implode(', ', $missing),
                    $parentClass,
                    implode(', ', $childTypes),
                );
            }
        }

        for ($index = count($parentParams); $index < count($childParams); $index++) {
            $extra = $childParams[$index];

            if (! $extra->isOptional()) {
                $issues[] = sprintf(
                    '%s::%s() adds required parameter $%s beyond %s — must be optional.',
                    $childClass,
                    $method,
                    $extra->getName(),
                    $parentClass,
                );
            }
        }

        $parentReturn = self::normalizeTypes($parent->getReturnType());
        $childReturn = self::normalizeTypes($child->getReturnType());

        if ($parentReturn !== [] && $childReturn !== []) {
            // Return types are covariant: child may narrow, but must not introduce unrelated types only.
            // Allow identical unions; if child removes a parent return type that is OK (narrower).
            // Flag only when child return has nothing in common with parent (except void/never edge cases).
            $overlap = array_intersect($parentReturn, $childReturn);

            if ($overlap === [] && ! self::isVoidish($parentReturn) && ! self::isVoidish($childReturn)) {
                $issues[] = sprintf(
                    '%s::%s() return type [%s] is incompatible with %s [%s].',
                    $childClass,
                    $method,
                    implode('|', $childReturn),
                    $parentClass,
                    implode('|', $parentReturn),
                );
            }
        }

        return $issues;
    }

    /**
     * @param  class-string  $class
     */
    private static function method(string $class, string $method): ReflectionMethod
    {
        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($method)) {
            throw new RuntimeException("{$class}::{$method}() does not exist.");
        }

        return $reflection->getMethod($method);
    }

    /**
     * @return list<string>
     */
    private static function normalizeTypes(?ReflectionType $type): array
    {
        if ($type === null) {
            return [];
        }

        if ($type instanceof ReflectionNamedType) {
            return [self::normalizeNamed($type)];
        }

        if ($type instanceof ReflectionUnionType) {
            $names = [];

            foreach ($type->getTypes() as $inner) {
                if ($inner instanceof ReflectionNamedType) {
                    $names[] = self::normalizeNamed($inner);
                }
            }

            sort($names);

            return array_values(array_unique($names));
        }

        return [(string) $type];
    }

    private static function normalizeNamed(ReflectionNamedType $type): string
    {
        $name = $type->getName();

        if ($name === 'true' || $name === 'false') {
            return 'bool';
        }

        return $name;
    }

    /**
     * @param  list<string>  $types
     */
    private static function isVoidish(array $types): bool
    {
        return $types === ['void'] || $types === ['never'];
    }

    /**
     * @internal used by diagnostics
     */
    public static function describeParameter(ReflectionParameter $parameter): string
    {
        $types = self::normalizeTypes($parameter->getType());

        return ($types === [] ? 'mixed' : implode('|', $types)).' $'.$parameter->getName();
    }
}
