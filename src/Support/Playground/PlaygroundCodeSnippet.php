<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Illuminate\Support\HtmlString;

/**
 * Terminal-style copyable code block for playground hubs (light + dark themes).
 */
final class PlaygroundCodeSnippet
{
    /**
     * @param  array<string, string>|null  $tabs  label => source (optional multi-tab)
     */
    public static function make(
        string $code,
        string $language = 'php',
        ?string $filename = null,
        ?array $tabs = null,
    ): View {
        if ($tabs !== null && $tabs !== []) {
            return self::tabs($tabs, $filename);
        }

        $code = self::normalizeCode($code);
        $filename ??= self::defaultFilename($language);

        return View::make('filament-flex-fields::partials.playground.code-snippet')
            ->viewData([
                'tabs' => [
                    [
                        'label' => strtoupper($language),
                        'language' => $language,
                        'code' => $code,
                        'highlighted' => new HtmlString(self::highlight($code, $language)),
                    ],
                ],
                'filename' => $filename,
                'hasTabs' => false,
            ]);
    }

    /**
     * @param  array<string, string>  $tabs  label => source
     */
    public static function tabs(array $tabs, ?string $filename = null): View
    {
        $normalized = [];

        foreach ($tabs as $label => $code) {
            $language = self::guessLanguage((string) $label, (string) $code);
            $code = self::normalizeCode((string) $code);

            $normalized[] = [
                'label' => (string) $label,
                'language' => $language,
                'code' => $code,
                'highlighted' => new HtmlString(self::highlight($code, $language)),
            ];
        }

        $primaryLanguage = $normalized[0]['language'] ?? 'php';

        return View::make('filament-flex-fields::partials.playground.code-snippet')
            ->viewData([
                'tabs' => $normalized,
                'filename' => $filename ?? self::defaultFilename($primaryLanguage),
                'hasTabs' => count($normalized) > 1,
            ]);
    }

    public static function highlight(string $code, string $language = 'php'): string
    {
        return match ($language) {
            'json' => self::highlightJson($code),
            default => self::highlightPhp($code),
        };
    }

    public static function highlightPhp(string $code): string
    {
        $placeholders = [];

        $store = function (string $html) use (&$placeholders): string {
            $token = '___FFF_TK_'.count($placeholders).'___';
            $placeholders[$token] = $html;

            return $token;
        };

        $wrap = function (string $class, string $raw) use ($store): string {
            return $store('<span class="'.$class.'">'.e($raw).'</span>');
        };

        $withPlaceholders = preg_replace_callback(
            '#(//[^\n]*|/\*.*?\*/)#s',
            fn (array $m): string => $wrap('fff-token-comment', $m[0]),
            $code,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            "/('(?:\\\\'|[^'])*'|\"(?:\\\\\"|[^\"])*\"|`(?:\\\\`|[^`])*`)/",
            fn (array $m): string => $wrap('fff-token-string', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/\b(0x[0-9a-fA-F]+|\d+(?:\.\d+)?)\b/',
            fn (array $m): string => $wrap('fff-token-number', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/\b(use|namespace|class|function|return|new|static|public|private|protected|fn|match|true|false|null|echo|array|string|int|float|bool|void|mixed|self|parent|const|if|else|elseif|foreach|for|while|try|catch|throw|extends|implements|as|default|case|break|continue|clone|instanceof|yield|finally|readonly|enum)\b/',
            fn (array $m): string => $wrap('fff-token-keyword', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/(\\\\|)([A-Z][A-Za-z0-9_]*(?:\\\\[A-Z][A-Za-z0-9_]*)+)/',
            fn (array $m): string => $wrap('fff-token-class', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/(::)([a-zA-Z_][a-zA-Z0-9_]*)/',
            fn (array $m): string => $m[1].$wrap('fff-token-method', $m[2]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/(->)([a-zA-Z_][a-zA-Z0-9_]*)/',
            fn (array $m): string => $m[1].$wrap('fff-token-method', $m[2]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/(\$)([a-zA-Z_][a-zA-Z0-9_]*)/',
            fn (array $m): string => $wrap('fff-token-variable', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), e($withPlaceholders));
    }

    public static function highlightJson(string $code): string
    {
        $placeholders = [];

        $store = function (string $html) use (&$placeholders): string {
            $token = '___FFF_TK_'.count($placeholders).'___';
            $placeholders[$token] = $html;

            return $token;
        };

        $wrap = function (string $class, string $raw) use ($store): string {
            return $store('<span class="'.$class.'">'.e($raw).'</span>');
        };

        $withPlaceholders = preg_replace_callback(
            '/"(?:\\\\.|[^"\\\\])*"(?=\s*:)/',
            fn (array $m): string => $wrap('fff-token-property', $m[0]),
            $code,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/"(?:\\\\.|[^"\\\\])*"/',
            fn (array $m): string => $wrap('fff-token-string', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/\b(true|false|null)\b/',
            fn (array $m): string => $wrap('fff-token-keyword', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        $withPlaceholders = preg_replace_callback(
            '/\b(-?\d+(?:\.\d+)?)\b/',
            fn (array $m): string => $wrap('fff-token-number', $m[0]),
            $withPlaceholders,
        );

        if (! is_string($withPlaceholders)) {
            return e($code);
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), e($withPlaceholders));
    }

    private static function normalizeCode(string $code): string
    {
        return trim($code)."\n";
    }

    private static function defaultFilename(string $language): string
    {
        return match ($language) {
            'json' => 'schema.json',
            'js', 'javascript' => 'example.js',
            'bash', 'shell' => 'terminal',
            default => 'usage.php',
        };
    }

    private static function guessLanguage(string $label, string $code): string
    {
        $normalized = strtolower(trim($label));

        if (in_array($normalized, ['json', 'js', 'javascript', 'bash', 'shell', 'php'], true)) {
            return $normalized === 'javascript' ? 'js' : $normalized;
        }

        $trimmed = ltrim($code);

        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            return 'json';
        }

        return 'php';
    }

    /**
     * Starter snippet for hubs that do not ship a hand-written example block.
     */
    public static function forHub(string $slug, string $playgroundClass): View
    {
        $playgroundClass = str_replace('\\', '\\\\', $playgroundClass);

        return self::make(<<<PHP
// Playground hub: {$slug}
// See {$playgroundClass} for the full demo schema.

use Bjanczak\\FilamentFlexFields\\Support\\FlexFieldsPlaygroundRegistry;

\$definition = FlexFieldsPlaygroundRegistry::find('{$slug}');
\$playground = app(\$definition['playground']);

// Reuse the same components() array rendered on this page:
\$components = \$playground->components();
PHP, filename: "{$slug}-usage.php");
    }

    /**
     * @param  list<Component>  $components
     */
    public static function componentsIncludeSnippet(array $components): bool
    {
        foreach ($components as $component) {
            if ($component instanceof View) {
                $view = method_exists($component, 'getView') ? $component->getView() : null;

                if ($view === 'filament-flex-fields::partials.playground.code-snippet') {
                    return true;
                }
            }
        }

        return false;
    }

    public static function playgroundDeclaresSnippet(string $playgroundClass): bool
    {
        if (! class_exists($playgroundClass)) {
            return false;
        }

        $reflection = new \ReflectionClass($playgroundClass);
        $source = file_get_contents($reflection->getFileName());

        return is_string($source) && str_contains($source, 'PlaygroundCodeSnippet::');
    }
}
