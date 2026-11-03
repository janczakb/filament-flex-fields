<?php

declare(strict_types=1);
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

/**
 * Read trusted installed declarations. Parse default expressions instead of
 * ReflectionParameter::getDefaultValue(), which can construct new objects.
 */
function apiType(?ReflectionType $type, ?ReflectionClass $context = null): ?string
{
    if ($type === null) {
        return null;
    }
    if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
        $parts = array_map(static fn (ReflectionType $part): ?string => apiType($part, $context), $type->getTypes());
        sort($parts, SORT_STRING);

        return '('.implode($type instanceof ReflectionUnionType ? '|' : '&', $parts).')';
    }

    if ($type instanceof ReflectionNamedType && $context !== null) {
        $resolved = match ($type->getName()) {
            'self' => $context->getName(),
            'parent' => ($parent = $context->getParentClass()) ? $parent->getName() : null,
            default => null,
        };
        if ($resolved !== null) {
            return ($type->allowsNull() ? '?' : '').$resolved;
        }
    }

    return (string) $type;
}

function apiValue(mixed $value): mixed
{
    if ($value instanceof UnitEnum) {
        return ['enum' => $value::class, 'case' => $value->name, ...($value instanceof BackedEnum ? ['value' => $value->value] : [])];
    }
    if (is_array($value)) {
        return array_map(apiValue(...), $value);
    }
    if ($value === null || is_scalar($value)) {
        if (is_float($value) && ! is_finite($value)) {
            return ['float' => (string) $value];
        }

        return $value;
    }
    throw new RuntimeException('Unsupported object in public API defaults.');
}

function apiSyntax(string $file): array
{
    static $cache = [];
    if (! isset($cache[$file])) {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);
        $cache[$file] = $traverser->traverse($parser->parse(file_get_contents($file)));
    }

    return $cache[$file];
}

function apiExpression(Expr $expression): string
{
    $finder = new NodeFinder;
    foreach ($finder->findInstanceOf([$expression], ConstFetch::class) as $constant) {
        $name = strtolower($constant->name->toString());
        if (in_array($name, ['null', 'true', 'false'], true)) {
            $constant->name = new Name($name);
        }
    }

    return (new Standard)->prettyPrintExpr($expression);
}

function apiParameterDefault(ReflectionMethod $method, ReflectionParameter $parameter): ?string
{
    if (! $parameter->isDefaultValueAvailable()) {
        return null;
    }
    $finder = new NodeFinder;
    $nodes = $finder->findInstanceOf(apiSyntax($method->getFileName()), ClassMethod::class);
    foreach ($nodes as $node) {
        if ($node->name->toString() !== $method->getName() || $node->getStartLine() > $method->getStartLine() || $node->getEndLine() < $method->getStartLine()) {
            continue;
        }
        foreach ($node->params as $param) {
            if ($param->var->name === $parameter->getName() && $param->default !== null) {
                return apiExpression($param->default);
            }
        }
    }
    throw new RuntimeException('Cannot identify the public parameter default expression.');
}

function apiPropertyDefault(ReflectionProperty $property): ?string
{
    if (! $property->hasDefaultValue()) {
        return null;
    }
    $finder = new NodeFinder;
    $nodes = $finder->findInstanceOf(apiSyntax($property->getDeclaringClass()->getFileName()), PropertyItem::class);
    foreach ($nodes as $node) {
        if ($node->name->toString() === $property->getName()) {
            return $node->default === null ? 'null' : apiExpression($node->default);
        }
    }
    throw new RuntimeException('Cannot identify the public property default expression.');
}

/** @return array<string, mixed> */
function publicApiClass(string $name): array
{
    $class = new ReflectionClass($name);
    $methods = [];
    foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
        if (! str_starts_with($method->getDeclaringClass()->getName(), 'Bjanczak\\FilamentFlexFields\\')) {
            continue;
        }
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameters[] = [
                'name' => $parameter->getName(), 'type' => apiType($parameter->getType(), $method->getDeclaringClass()),
                'optional' => $parameter->isOptional(), 'variadic' => $parameter->isVariadic(),
                'byReference' => $parameter->isPassedByReference(),
                'hasDefault' => $parameter->isDefaultValueAvailable(),
                'default' => apiParameterDefault($method, $parameter),
            ];
        }
        $methods[$method->getName()] = [
            'visibility' => $method->isPublic() ? 'public' : 'protected',
            'static' => $method->isStatic(), 'abstract' => $method->isAbstract(), 'final' => $method->isFinal(),
            'returnsReference' => $method->returnsReference(), 'return' => apiType($method->getReturnType(), $method->getDeclaringClass()),
            'parameters' => $parameters,
        ];
    }
    $properties = [];
    foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
        if (! str_starts_with($property->getDeclaringClass()->getName(), 'Bjanczak\\FilamentFlexFields\\')) {
            continue;
        }
        $properties[$property->getName()] = [
            'visibility' => $property->isPublic() ? 'public' : 'protected',
            'static' => $property->isStatic(), 'readonly' => $property->isReadOnly(),
            'type' => apiType($property->getType(), $property->getDeclaringClass()), 'hasDefault' => $property->hasDefaultValue(),
            'default' => apiPropertyDefault($property),
        ];
    }
    $constants = [];
    foreach ($class->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC | ReflectionClassConstant::IS_PROTECTED) as $constant) {
        if (! str_starts_with($constant->getDeclaringClass()->getName(), 'Bjanczak\\FilamentFlexFields\\')) {
            continue;
        }
        $constants[$constant->getName()] = [
            'visibility' => $constant->isPublic() ? 'public' : 'protected',
            'final' => $constant->isFinal(), 'value' => apiValue($constant->getValue()),
        ];
    }
    ksort($methods, SORT_STRING);
    ksort($properties, SORT_STRING);
    ksort($constants, SORT_STRING);
    $interfaces = $class->getInterfaceNames();
    sort($interfaces, SORT_STRING);

    return [
        'kind' => $class->isEnum() ? 'enum' : ($class->isInterface() ? 'interface' : ($class->isTrait() ? 'trait' : 'class')),
        'final' => $class->isFinal(), 'abstract' => $class->isAbstract(), 'readonly' => $class->isReadOnly(),
        'parent' => ($parent = $class->getParentClass()) ? $parent->getName() : null,
        'interfaces' => $interfaces, 'methods' => $methods, 'properties' => $properties, 'constants' => $constants,
    ];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $scope = json_decode(file_get_contents(__DIR__.'/contracts/public-api.json'), true, 32, JSON_THROW_ON_ERROR);
    $result = [];
    foreach ($scope['php'] as $name) {
        if (! is_string($name) || ! str_starts_with($name, 'Bjanczak\\FilamentFlexFields\\') || isset($result[$name])) {
            throw new RuntimeException('Invalid or duplicated PHP API scope.');
        }
        $result[$name] = publicApiClass($name);
    }
    echo json_encode(['format' => 1, 'classes' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR), PHP_EOL;
}
