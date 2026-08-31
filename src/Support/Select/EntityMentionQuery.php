<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Select;

/**
 * Parses @mention triggers from SelectField search queries.
 */
final class EntityMentionQuery
{
    /**
     * @return array{
     *     active: bool,
     *     trigger: string,
     *     term: string,
     *     search: string,
     * }
     */
    public static function parse(string $query, string $trigger = '@'): array
    {
        $text = $query;
        $normalizedTrigger = $trigger === '' ? '@' : $trigger;

        if ($normalizedTrigger === '' || ! str_contains($text, $normalizedTrigger)) {
            return [
                'active' => false,
                'trigger' => $normalizedTrigger,
                'term' => '',
                'search' => trim($text),
            ];
        }

        $atIndex = strrpos($text, $normalizedTrigger);

        if ($atIndex === false) {
            return [
                'active' => false,
                'trigger' => $normalizedTrigger,
                'term' => '',
                'search' => trim($text),
            ];
        }

        $afterTrigger = substr($text, $atIndex + strlen($normalizedTrigger));

        if (preg_match('/\s/u', $afterTrigger) === 1 && trim($afterTrigger) !== preg_replace('/\s+.*/u', '', $afterTrigger)) {
            return [
                'active' => false,
                'trigger' => $normalizedTrigger,
                'term' => '',
                'search' => trim($text),
            ];
        }

        $term = trim($afterTrigger);

        return [
            'active' => true,
            'trigger' => $normalizedTrigger,
            'term' => $term,
            'search' => $term,
        ];
    }
}
